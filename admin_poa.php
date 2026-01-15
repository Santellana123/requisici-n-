<?php require 'includes/admin_check.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cargar POA (CSV Múltiples) | ITSM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js"></script>

    <style>
        /* Estilos para el Selector de Años Moderno */
        .year-selector-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .year-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .year-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .year-card.active {
            border-color: #2563eb; /* Azul bonito */
            background-color: #eff6ff;
            color: #1e40af;
            font-weight: bold;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
        }

        .year-card i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #94a3b8;
        }

        .year-card.active i {
            color: #2563eb;
        }

        .year-number {
            font-size: 1.2rem;
            display: block;
        }

        .year-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Checkmark decorativo */
        .check-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.8rem;
            opacity: 0;
            transform: scale(0);
            transition: all 0.2s;
            color: #2563eb;
        }

        .year-card.active .check-icon {
            opacity: 1;
            transform: scale(1);
        }
    </style>
</head>
<body>
    <header class="main-header">
        <h1><i class="fa-solid fa-cloud-arrow-up"></i> Carga Masiva por CSV</h1>
        <nav>
            <a href="admin.php" class="btn-logout" style="background: transparent; border: 1px solid white;">
                <i class="fa-solid fa-arrow-left"></i> Volver al Dashboard
            </a>
        </nav>
    </header>

    <main class="container">
        <form id="form-poa">
            <div class="container-header">
                <h2>Subir Presupuesto (POA)</h2>
            </div>
            
            <div style="background-color: #fff3cd; color: #856404; padding: 20px; border-radius: 8px; margin-bottom: 25px; border-left: 5px solid #ffc107; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <h4 style="margin-top:0; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-circle-exclamation"></i> Instrucciones Importantes
                </h4>
                <ul style="margin: 10px 0 0 20px; line-height: 1.6;">
                    <li>Guarde cada hoja de su Excel como un archivo <strong>.csv (UTF-8)</strong>.</li>
                    <li><strong>El nombre del archivo será el nombre del Área.</strong> (Ej: <em>RECURSOS MATERIALES.csv</em>).</li>
                    <li>Columnas requeridas (en orden): <br><code style="background:#fff; padding:2px 5px; border-radius:4px;">PARTIDA | PROGRAMA | CONCEPTO | UNIDAD | CANTIDAD | PRECIO</code></li>
                </ul>
            </div>

            <div class="input-group">
                <label style="font-weight: bold; display: block; margin-bottom: 10px; color: #334155;">
                    <i class="fa-regular fa-calendar-check"></i> Seleccione Año Fiscal:
                </label>
                
                <input type="hidden" id="anio" value="<?php echo date('Y') + 1; ?>">

                <div class="year-selector-container">
                    <?php 
                    $anio_actual = date('Y');
                    // Generamos un rango: Desde el año pasado hasta 2 años en el futuro
                    $anios = range($anio_actual - 1, $anio_actual + 2);
                    $anio_seleccionado = $anio_actual + 1; // Por defecto sugerimos el siguiente

                    foreach($anios as $year) {
                        $activeClass = ($year == $anio_seleccionado) ? 'active' : '';
                        
                        // Etiqueta descriptiva
                        $label = 'Futuro';
                        if($year == $anio_actual) $label = 'Actual';
                        if($year == $anio_actual - 1) $label = 'Anterior';
                        if($year == $anio_actual + 1) $label = 'Próximo';

                        echo "
                        <div class='year-card $activeClass' onclick='seleccionarAnio(this, $year)'>
                            <i class='fa-solid fa-circle-check check-icon'></i>
                            <i class='fa-regular fa-calendar'></i>
                            <span class='year-number'>$year</span>
                            <span class='year-label'>$label</span>
                        </div>
                        ";
                    }
                    ?>
                </div>
            </div>
            
            <div class="input-group" style="margin-top: 25px;">
                <label style="font-weight: bold; display: block; margin-bottom: 10px; color: #334155;">
                    <i class="fa-solid fa-file-csv"></i> Seleccione los archivos CSV:
                </label>
                
                <div style="position: relative; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center; background: #f8fafc; transition: all 0.3s;">
                    <input type="file" id="archivos_csv" accept=".csv" multiple required 
                           style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;">
                    
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2rem; color: #94a3b8; margin-bottom: 10px;"></i>
                    <p style="margin: 0; font-weight: 500; color: #475569;">Arrastra tus archivos aquí o haz clic para buscar</p>
                    <small style="color: #64748b;">(Puedes seleccionar varios manteniendo Ctrl)</small>
                </div>
                <div id="file-list" style="margin-top: 10px; font-size: 0.9rem; color: #2563eb;"></div>
            </div>

            <div id="progreso-contenedor" style="display:none; margin-top:20px;">
                <p id="texto-progreso" style="margin-bottom: 5px; font-weight: bold; color: #334155;">Procesando archivos...</p>
                <div style="background:#e2e8f0; height:20px; border-radius:10px; overflow:hidden;">
                    <div id="barra-progreso" style="background: linear-gradient(90deg, #2563eb, #3b82f6); width:0%; height:100%; transition:width 0.3s; text-align:center; color:white; font-size:0.75rem; line-height:20px; font-weight: bold; box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);">0%</div>
                </div>
            </div>

            <button type="submit" id="btn-subir" class="btn-primary" style="margin-top:30px; width: 100%; padding: 15px; font-size: 1.1rem; border-radius: 8px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                <i class="fa-solid fa-rocket"></i> Procesar Archivos al Sistema
            </button>
        </form>
    </main>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <script>
        function seleccionarAnio(elemento, anio) {
            // 1. Quitar clase active de todos
            document.querySelectorAll('.year-card').forEach(card => card.classList.remove('active'));
            
            // 2. Activar el clicado
            elemento.classList.add('active');
            
            // 3. Actualizar el input oculto (que es el que lee tu admin_poa.js)
            document.getElementById('anio').value = anio;
        }

        // Script visual para input file
        document.getElementById('archivos_csv').addEventListener('change', function(e) {
            const count = e.target.files.length;
            const display = document.getElementById('file-list');
            if(count > 0) {
                display.innerHTML = `<i class="fa-solid fa-check"></i> <strong>${count}</strong> archivo(s) seleccionado(s)`;
            } else {
                display.innerHTML = '';
            }
        });
    </script>

    <script src="assets/js/admin_poa.js?v=<?php echo time(); ?>"></script>
</body>
</html>