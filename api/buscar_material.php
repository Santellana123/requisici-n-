<?php
session_start();
require '../includes/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['area_id'])) { echo json_encode([]); exit; }

$term = "%" . ($_GET['term'] ?? '') . "%";
$area_id = $_SESSION['area_id'];

// 1. OBTENER AÑO ACTIVO DE LA CONFIGURACIÓN
$sqlConf = "SELECT valor FROM configuracion WHERE clave = 'anio_fiscal_activo' LIMIT 1";
$resConf = $conn->query($sqlConf);
$rowConf = $resConf->fetch_assoc();
$anioActivo = $rowConf ? $rowConf['valor'] : date('Y');

// 2. BUSCAR ITEMS (Filtrando por Área Y por Año Activo)
// Hacemos JOIN con poa_archivos para validar el anio_fiscal
$sql = "SELECT pi.id, pi.partida_presupuestal, pi.concepto, pi.unidad_medida, pi.cantidad_disponible, pi.precio_unitario 
        FROM poa_items pi
        JOIN poa_archivos pa ON pi.poa_archivo_id = pa.id
        WHERE pi.area_id = ? 
          AND pa.anio_fiscal = ?
          AND pi.cantidad_disponible > 0 
          AND (pi.concepto LIKE ? OR pi.partida_presupuestal LIKE ?)
        LIMIT 20";

$stmt = $conn->prepare($sql);
// "iiss" = integer (area), integer (anio), string (term), string (term)
// Nota: pa.anio_fiscal en BD suele ser int, pero si guardaste como string, usa "s"
$stmt->bind_param("iiss", $area_id, $anioActivo, $term, $term);

$stmt->execute();
$result = $stmt->get_result();

$items = [];
while($row = $result->fetch_assoc()) {
    $row['descripcion'] = $row['concepto'];
    $items[] = $row;
}
echo json_encode($items);
?>