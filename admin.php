<?php
require 'includes/admin_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Dirección | ITSM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .search-bar { margin-bottom: 20px; position: relative; }
        .search-bar input { width: 100%; padding: 12px 15px; padding-left: 40px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px; }
        .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 15px; }
        
        /* Estilos del Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 8px; }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        
        /* Botones del modal */
        .modal-actions { display: flex; gap: 10px; margin-top: 20px; justify-content: flex-end; }
        .btn-reject { background-color: #dc3545; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        .btn-approve { background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
        textarea { width: 100%; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; resize: vertical; }

        /* --- NUEVO: Estilos para la Tarjeta de Configuración Global --- */
        .config-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .config-info h3 { margin: 0 0 5px 0; font-size: 1.1rem; color: #495057; }
        .config-info p { margin: 0; font-size: 0.9rem; color: #6c757d; }
        .config-controls { display: flex; gap: 10px; align-items: center; }
        .btn-save-config { background-color: #6610f2; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: 500; transition: background 0.3s; }
        .btn-save-config:hover { background-color: #520dc2; }
        .btn-save-config:disabled { background-color: #a5a5a5; cursor: not-allowed; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <header class="main-header">
        <h1>Panel de Dirección y Planeación</h1>
        <nav>
            <span style="margin-right: 15px;"><i class="fa-solid fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?></span>
            <a href="api/logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <h2>Bitácora de Requisiciones</h2>
                
                <select id="filtro-anio" onchange="cargarDatos()" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc; font-weight: bold; cursor: pointer;">
                    <?php 
                    $anio_actual = date('Y');
                    for($i = $anio_actual - 1; $i <= $anio_actual + 1; $i++) {
                        $selected = ($i == $anio_actual) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <a href="admin_poa.php" class="btn-imprimir" style="background-color: #28a745; text-decoration:none; color:white; padding:10px 15px; border-radius:5px;">📂 Cargar POA</a>
                <a href="admin_usuarios.php" class="btn-imprimir" style="background-color: #17a2b8; text-decoration:none; color:white; padding:10px 15px; border-radius:5px;">👥 Usuarios</a>
            </div>
        </div>

        <div class="config-card">
            <div class="config-info">
                <h3><i class="fa-solid fa-gears"></i> Configuración Global del Sistema</h3>
                <p>Define qué año fiscal del POA está activo para los usuarios (Búsqueda y Creación).</p>
            </div>
            <div class="config-controls">
                <label for="config-anio-sistema" style="font-weight:bold; color:#555;">Año Activo:</label>
                <input type="number" id="config-anio-sistema" placeholder="Ej. 2025" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; width: 100px; text-align: center;">
                <button id="btn-guardar-config" class="btn-save-config">Guardar</button>
            </div>
        </div>

        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="buscador" placeholder="Buscar por solicitante, folio o área..." autocomplete="off">
        </div>

        <div class="table-container">
            <table id="tabla-requisicion" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Solicitante</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="cuerpo-tabla-admin"></tbody>
            </table>
        </div>
    </main>

    <input type="hidden" id="id-seleccionado">

    <script src="assets/js/admin.js?v=<?php echo time(); ?>"></script>
</body>
</html>