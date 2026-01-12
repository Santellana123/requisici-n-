<?php
require 'includes/session_check.php';
require 'includes/db_connect.php'; 

// Seguridad
if ($_SESSION['rol'] == 'director_planeacion') {
    header("Location: admin.php");
    exit;
}

$area_id = $_SESSION['area_id'];

// --- 1. OBTENER EL NOMBRE DEL PROGRAMA (ÁREA) ---
$nombre_programa_auto = "Sin Asignar";
$sql_area = "SELECT nombre_programa FROM areas WHERE id = ? LIMIT 1";
$stmt_area = $conn->prepare($sql_area);
$stmt_area->bind_param("i", $area_id);
if ($stmt_area->execute()) {
    $res_area = $stmt_area->get_result();
    if ($fila = $res_area->fetch_assoc()) {
        $nombre_programa_auto = $fila['nombre_programa'];
    }
}

// --- 2. OBTENER PROYECTOS (PROCESOS) DISPONIBLES ---
// Consultamos los procesos únicos disponibles en la tabla poa_items
$proyectos_db = [];

$sql_proy = "SELECT DISTINCT proceso FROM poa_items WHERE area_id = ? AND cantidad_disponible > 0 ORDER BY proceso ASC";
$stmt = $conn->prepare($sql_proy);
$stmt->bind_param("i", $area_id);
if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['proceso'])) {
            $proyectos_db[] = $row['proceso'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Requisición | <?php echo htmlspecialchars($_SESSION['nombre_area']); ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="assets/css/modern_form.css?v=<?php echo time(); ?>">
</head>
<body>

    <header class="main-header">
        <h1><i class="fa-solid fa-file-invoice"></i> Sistema Requisiciones</h1>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div style="text-align: right; line-height: 1.2;">
                <span style="display:block; font-size: 0.75rem; opacity: 0.8;">Área</span>
                <strong><?php echo htmlspecialchars($_SESSION['nombre_area']); ?></strong>
            </div>
            <a href="api/logout.php" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
    </header>

    <main class="container">
        
        <div class="page-title">
            <h2>Generar Nueva Requisición (POA 2025)</h2>
            <p>Complete los campos basándose en el formato oficial FO-COM-06.</p>
        </div>

        <form id="form-requisicion">
            
            <div class="section-card">
                <div class="section-title"><i class="fa-regular fa-id-card"></i> 1. Datos Generales</div>
                
                <div class="form-grid">
                    <div class="input-wrapper form-full">
                        <label for="motivo">Justificación de la solicitud:</label>
                        <textarea id="motivo" rows="2" required placeholder="Ej: Material necesario para el mantenimiento preventivo de..."></textarea>
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="programa">Programa (POA):</label>
                        <input type="text" id="programa" 
                               value="<?php echo htmlspecialchars($nombre_programa_auto); ?>" 
                               readonly 
                               style="background-color: #e2e8f0; color: #475569; cursor: not-allowed;">
                    </div>
                    
                    <div class="input-wrapper">
                        <label for="proyecto">Proyecto / Proceso:</label>
                        <select id="proyecto" required>
                            <?php if(empty($proyectos_db)): ?>
                                <option value="">-- No hay proyectos con presupuesto --</option>
                            <?php else: ?>
                                <option value="">-- Seleccione un Proyecto --</option>
                                <?php foreach($proyectos_db as $proy): ?>
                                    <option value="<?php echo htmlspecialchars($proy); ?>">
                                        <?php echo htmlspecialchars($proy); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fa-solid fa-cart-shopping"></i> 2. Selección de Materiales</div>
                
                <div class="search-hero">
                    <label style="display:block; margin-bottom: 8px; color: var(--primary); font-weight:600;">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar en Presupuesto Autorizado:
                    </label>
                    <input type="text" id="buscar-material" autocomplete="off" placeholder="Escribe nombre, código o partida..." style="border: 2px solid #bfdbfe;">
                    <ul id="resultados-busqueda"></ul>
                </div>

                <div class="selection-panel">
                    <div class="input-wrapper">
                        <label>Concepto:</label>
                        <div id="material-seleccionado" class="display-value">--</div>
                    </div>
                    
                    <div class="input-wrapper">
                        <label>Unidad:</label>
                        <div id="unidad-medida" class="display-value">--</div>
                    </div>

                    <div class="input-wrapper">
                        <label>Stock:</label>
                        <div id="stock-disponible" class="display-value" style="color: var(--success);">--</div>
                    </div>

                    <div class="input-wrapper">
                        <label>Cantidad:</label>
                        <input type="number" id="cantidad-pedir" min="1" disabled placeholder="0">
                    </div>
                    
                    <button type="button" id="btn-agregar" class="btn btn-add" disabled>
                        <i class="fa-solid fa-plus"></i> Agregar
                    </button>
                </div>

                <div class="table-responsive">
                    <table id="tabla-requisicion">
                        <thead>
                            <tr>
                                <th style="width: 10%;">Cant.</th>
                                <th style="width: 15%;">Unidad</th>
                                <th style="width: 60%;">Descripción / Partida</th>
                                <th style="width: 15%; text-align: center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla">
                            <tr class="empty-row">
                                <td colspan="4" style="text-align:center; color: #94a3b8; padding: 2rem;">
                                    <i class="fa-regular fa-folder-open" style="font-size: 2rem; margin-bottom: 10px; display:block;"></i>
                                    Lista vacía
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title"><i class="fa-regular fa-comment-dots"></i> 3. Notas Adicionales</div>
                <div class="input-wrapper">
                    <textarea id="observaciones" rows="2" placeholder="Notas para compras (opcional)..."></textarea>
                </div>
            </div>

            <div style="margin-bottom: 3rem;">
                <button type="button" id="btn-generar-req" class="btn btn-primary" disabled>
                    <i class="fa-solid fa-check-circle"></i> Generar Requisición y Guardar
                </button>
                <p id="form-error" style="color: var(--danger); text-align: center; margin-top: 10px; display: none;"></p>
            </div>

        </form>
    </main>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="assets/js/requisicion.js?v=<?php echo time(); ?>"></script>
</body>
</html>