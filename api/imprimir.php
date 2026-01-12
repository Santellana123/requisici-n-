<?php
ob_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/fpdf.php';

// --- 1. VALIDACIÓN Y CONSULTA ---
if (!isset($_GET['id'])) die("Error: Falta ID.");
$req_id = intval($_GET['id']);

// Datos Generales
// Nota: Usamos r.* para traer todo, incluyendo 'proyecto_poa' y 'folio'
$sql = "SELECT r.*, u.nombre_usuario, a.nombre_programa 
        FROM requisiciones r 
        JOIN usuarios u ON r.usuario_id = u.id 
        JOIN areas a ON r.area_id = a.id 
        WHERE r.id = $req_id";
$res = $conn->query($sql);
$data = $res->fetch_assoc();
if (!$data) die("Requisición no encontrada.");

// Items (CORREGIDO: nombre de tabla y alias)
$sql_items = "SELECT rd.cantidad_solicitada, pi.partida_presupuestal, pi.concepto, pi.unidad_medida 
              FROM requisicion_detalles rd 
              JOIN poa_items pi ON rd.poa_item_id = pi.id 
              WHERE rd.requisicion_id = $req_id";
$res_items = $conn->query($sql_items);

if (!$res_items) {
    die("Error en consulta de items: " . $conn->error);
}

$todos_los_items = [];
while($row = $res_items->fetch_assoc()) {
    $todos_los_items[] = $row;
}

// --- 2. CONFIGURACIÓN DEL PDF ---
class PDF extends FPDF {
    function Header() {
        // --- CAJA SUPERIOR (Logos y Títulos) ---
        $x = 10; $y = 10;
        
        // 1. DIBUJAR EL CUADRO DEL LOGO
        $this->Rect($x, $y, 30, 25); 

        // 2. LÓGICA INTELIGENTE PARA EL LOGO
        $logo = __DIR__ . '/../assets/img/logo.jpg'; 
        
        if(file_exists($logo)) {
            list($ancho_orig, $alto_orig) = getimagesize($logo);
            $max_w = 28; 
            $max_h = 23; 
            
            $ratio_w = $max_w / $ancho_orig;
            $ratio_h = $max_h / $alto_orig;
            $ratio = min($ratio_w, $ratio_h);
            
            $new_w = $ancho_orig * $ratio;
            $new_h = $alto_orig * $ratio;
            
            $off_x = ($max_w - $new_w) / 2;
            $off_y = ($max_h - $new_h) / 2;
            
            $this->Image($logo, $x + 1 + $off_x, $y + 1 + $off_y, $new_w, $new_h);
        }

        // Títulos
        $this->SetXY($x + 30, $y);
        $this->SetFont('Arial', '', 10);
        $this->Cell(115, 12.5, utf8_decode('INSTITUTO TECNOLOGICO SUPERIOR DE MONCLOVA'), 1, 0, 'C');
        
        $this->SetXY($x + 30, $y + 12.5);
        $this->SetFont('Arial', '', 11);
        $this->Cell(115, 12.5, utf8_decode('REQUISICIÓN DE MATERIAL'), 1, 0, 'C');

        // Cuadro ISO
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x + 145, $y);
        $this->Cell(40, 8.3, 'FO-COM-06', 1, 1, 'C');
        $this->SetX($x + 145);
        $this->Cell(40, 8.3, utf8_decode('SEPTIEMBRE 2021'), 1, 1, 'C');
        $this->SetX($x + 145);
        $this->Cell(40, 8.4, utf8_decode('Página ') . $this->PageNo() . ' de {nb}', 1, 1, 'C');

        $this->Ln(15); 
    }

    function Footer() {
        // Accedemos a la variable global $data para poner el folio si existe
        global $data; 
        $folio_texto = isset($data['folio']) && !empty($data['folio']) ? $data['folio'] : '______________________';

        $this->SetY(-15);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 10, utf8_decode('Folio y fecha de Recepción: ' . $folio_texto), 0, 0, 'R');
    }
}

// Inicializar PDF
$pdf = new PDF();
$pdf->AliasNbPages();

// --- 3. LÓGICA DE PAGINACIÓN (12 items por hoja) ---
$limite_por_hoja = 12;
$total_items = count($todos_los_items);
$total_paginas = ceil($total_items / $limite_por_hoja);
if ($total_paginas == 0) $total_paginas = 1; 

for ($i = 0; $i < $total_paginas; $i++) {
    $pdf->AddPage();
    
    // A. IMPRIMIR DATOS (Se repite en cada hoja)
    imprimirDatosSuperiores($pdf, $data);

    // B. IMPRIMIR TABLA (Slice de items)
    $inicio = $i * $limite_por_hoja;
    $items_pagina = array_slice($todos_los_items, $inicio, $limite_por_hoja);
    
    imprimirTabla($pdf, $items_pagina, $limite_por_hoja);

    // C. IMPRIMIR PIE DE PÁGINA (Solo en la última hoja)
    if ($i == ($total_paginas - 1)) {
        imprimirSeccionFinal($pdf, $data);
    }
}

// --- FUNCIONES AUXILIARES ---

function imprimirDatosSuperiores($pdf, $data) {
    $pdf->SetFont('Arial', '', 9);
    
    // Preparar Fecha
    $fecha = date("d/F/Y", strtotime($data['fecha_creacion']));
    $meses = ['January'=>'Enero','February'=>'Febrero','March'=>'Marzo','April'=>'Abril','May'=>'Mayo','June'=>'Junio','July'=>'Julio','August'=>'Agosto','September'=>'Septiembre','October'=>'Octubre','November'=>'Noviembre','December'=>'Diciembre'];
    $fecha = strtr($fecha, $meses);

    dibujarLineaForm($pdf, 'FECHA', $fecha);
    dibujarLineaForm($pdf, 'DEPARTAMENTO', $data['nombre_programa']);
    dibujarLineaForm($pdf, 'MOTIVO POR EL CUAL LO SOLICITA', $data['motivo_solicitud']);
    dibujarLineaForm($pdf, 'PROGRAMA', $data['nombre_programa']);
    
    // CORREGIDO: En la BD la columna es 'proyecto_poa'
    $proy = isset($data['proyecto_poa']) ? $data['proyecto_poa'] : '';
    dibujarLineaForm($pdf, 'PROYECTO', $proy);

    $pdf->Ln(5);
}

function dibujarLineaForm($pdf, $label, $valor) {
    $pdf->SetFont('Arial', '', 9);
    $ancho_label = $pdf->GetStringWidth($label) + 2;
    $pdf->Cell($ancho_label, 6, utf8_decode($label), 0, 0, 'L');
    $ancho_valor = 190 - $ancho_label;
    $pdf->Cell($ancho_valor, 6, utf8_decode($valor), 'B', 1, 'L');
}

function imprimirTabla($pdf, $items, $max_filas) {
    // Encabezados
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(35, 8, utf8_decode('CANTIDAD'), 1, 0, 'C');
    $pdf->Cell(35, 8, utf8_decode('TIPO DE UNIDAD'), 1, 0, 'C');
    $pdf->Cell(0, 8, utf8_decode('DESCRIPCIÓN'), 1, 1, 'C');
    
    $pdf->SetFont('Arial', '', 9);
    $alto_fila = 7; 

    // Filas con datos
    foreach ($items as $item) {
        $pdf->Cell(35, $alto_fila, $item['cantidad_solicitada'], 1, 0, 'C');
        $pdf->Cell(35, $alto_fila, utf8_decode(substr($item['unidad_medida'],0,15)), 1, 0, 'C');
        $pdf->Cell(0, $alto_fila, utf8_decode($item['concepto']), 1, 1, 'L');
    }

    // Filas vacías
    $filas_restantes = $max_filas - count($items);
    for ($j = 0; $j < $filas_restantes; $j++) {
        $pdf->Cell(35, $alto_fila, '', 1, 0, 'C');
        $pdf->Cell(35, $alto_fila, '', 1, 0, 'C');
        $pdf->Cell(0, $alto_fila, '', 1, 1, 'L');
    }
}

function imprimirSeccionFinal($pdf, $data) {
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 7);
    $nota = "Nota: El cumplimiento de los requisitos ambientales de los productos, materiales, consumibles y servicios de esta requisición han sido revisados para cumplir con lo que marca el sistema de gestión ambiental de las Institución.";
    $pdf->MultiCell(0, 3, utf8_decode($nota), 0, 'J');

    // Observaciones
    $pdf->Ln(2);
    $pdf->SetFont('Arial', '', 8);
    
    $obs_user = !empty($data['observaciones_usuario']) ? $data['observaciones_usuario'] : '';
    $pdf->Cell(35, 5, utf8_decode('Observaciones Usuario:'), 0, 0);
    $pdf->Cell(0, 5, utf8_decode($obs_user), 'B', 1);

    $pdf->Cell(35, 5, utf8_decode('Observaciones'), 0, 1);
    $pdf->Cell(35, 5, utf8_decode('Generales:'), 0, 0);
    $pdf->Cell(0, 5, '', 'B', 1);

    $pdf->Cell(35, 5, utf8_decode('Observaciones Compras:'), 0, 0);
    $pdf->Cell(0, 5, '', 'B', 1);

    // --- FIRMAS FIJAS EN EL FONDO ---
    $y_firmas = 225; 
    
    $pdf->SetXY(10, $y_firmas);
    
    // Líneas
    $pdf->Line(15, $y_firmas, 85, $y_firmas);   // Izq
    $pdf->Line(125, $y_firmas, 195, $y_firmas); // Der

    // Textos Fila 1
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(15, $y_firmas + 1);
    $pdf->Cell(70, 4, utf8_decode('Solicita'), 0, 0, 'C');
    
    $pdf->SetXY(125, $y_firmas + 1);
    $pdf->Cell(70, 4, utf8_decode('Vo. Bo. Planeación y Presupuesto'), 0, 0, 'C');

    // Segunda fila de firmas
    $y_firmas_2 = $y_firmas + 25;
    
    $pdf->Line(15, $y_firmas_2, 85, $y_firmas_2);   // Izq
    $pdf->Line(125, $y_firmas_2, 195, $y_firmas_2); // Der

    // Textos Fila 2
    $pdf->SetXY(15, $y_firmas_2 + 1);
    $pdf->Cell(70, 4, utf8_decode('Vo. Bo. Dirección General'), 0, 0, 'C');

    $pdf->SetXY(125, $y_firmas_2 + 1);
    $pdf->Cell(70, 4, utf8_decode('Vo. Bo. Subdirección de Servicios'), 0, 1, 'C');
    $pdf->SetX(125);
    $pdf->Cell(70, 3, utf8_decode('Administrativos'), 0, 0, 'C');
}

ob_end_clean();
$pdf->Output('I', 'Requisicion_'.$req_id.'.pdf');
?>