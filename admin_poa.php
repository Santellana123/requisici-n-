<?php require 'includes/admin_check.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar POA (CSV Múltiples) | ITSM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>
</head>
<body>
    <header class="main-header">
        <h1>Carga Masiva por CSV</h1>
        <nav>
            <a href="admin.php">Volver al Dashboard</a>
        </nav>
    </header>

    <main class="container">
        <form id="form-poa">
            <div class="container-header">
                <h2>Subir Presupuesto (POA)</h2>
            </div>
            
            <div style="background-color: #e2e3e5; color: #383d41; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #d6d8db;">
                <strong>⚠️ Instrucciones Importantes:</strong>
                <ol style="margin: 5px 0 0 20px; line-height: 1.6;">
                    <li>Guarde cada hoja de su Excel como un archivo <strong>.csv (Delimitado por comas)</strong>.</li>
                    <li><strong>El nombre del archivo debe ser EXACTAMENTE el nombre del Área.</strong> <br>Ejemplo: <em>BECAS.csv</em>, <em>RECURSOS MATERIALES.csv</em></li>
                    <li>El archivo debe tener las columnas en este orden exacto (sin encabezados o ignorando la primera fila): <br><strong>PARTIDA | PROGRAMA | CONCEPTO | UNIDAD | CANTIDAD | PRECIO</strong></li>
                    <li>No incluya símbolos de moneda ($) ni comas en los precios.</li>
                </ol>
            </div>

            <div class="input-group">
                <label for="anio" style="font-weight: bold; display: block; margin-bottom: 5px;">Seleccione Año Fiscal:</label>
                <select id="anio" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 16px; background-color: white;">
                    <?php 
                    $anio_actual = date('Y');
                    // Generar opciones: Año actual y el siguiente (ej: 2025 y 2026)
                    echo "<option value='" . $anio_actual . "'>" . $anio_actual . "</option>";
                    echo "<option value='" . ($anio_actual + 1) . "' selected>" . ($anio_actual + 1) . "</option>";
                    ?>
                </select>
                <small style="color: #666;">Selecciona el año al que pertenece este presupuesto.</small>
            </div>
            
            <div class="input-group" style="margin-top: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Seleccione los archivos CSV:</label>
                <input type="file" id="archivos_csv" accept=".csv" multiple required style="padding: 10px; border: 1px solid #ccc; width: 100%; box-sizing: border-box;">
                <small style="color: #666;">Puedes seleccionar varios archivos a la vez manteniendo presionada la tecla Ctrl.</small>
            </div>

            <div id="progreso-contenedor" style="display:none; margin-top:20px;">
                <p id="texto-progreso" style="margin-bottom: 5px; font-weight: bold;">Procesando archivos...</p>
                <div style="background:#eee; height:25px; border-radius:5px; overflow:hidden; border: 1px solid #ddd;">
                    <div id="barra-progreso" style="background:#28a745; width:0%; height:100%; transition:width 0.3s; text-align:center; color:white; line-height:25px; font-size:0.8rem; font-weight: bold;">0%</div>
                </div>
            </div>

            <button type="submit" id="btn-subir" class="btn-primary" style="margin-top:25px; width: 100%; padding: 12px; font-size: 16px;">
                🚀 Procesar Archivos al Sistema
            </button>
        </form>
    </main>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script src="assets/js/admin_poa.js?v=<?php echo time(); ?>"></script>
</body>
</html>