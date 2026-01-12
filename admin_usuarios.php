<?php
require 'includes/admin_check.php';
// No necesitamos cargar áreas aquí, ya que solo listaremos y editaremos passwords
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios y Contraseñas | ITSM</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilos del Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 450px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        
        .user-info { background: #e9ecef; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-weight: bold; color: #333; }
        .btn-password { background-color: #ffc107; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-password:hover { background-color: #e0a800; }
    </style>
</head>
<body>

    <header class="main-header">
        <h1>Control de Usuarios (POA)</h1>
        <nav>
            <a href="admin.php" style="color: white; margin-right: 15px; text-decoration: none;">⬅ Volver al Panel</a>
            <a href="api/logout.php" class="btn-logout">Cerrar Sesión</a>
        </nav>
    </header>

    <main class="container">
        <div class="container-header">
            <h2><i class="fa-solid fa-users-gear"></i> Usuarios Registrados</h2>
            <p>Estos usuarios se generaron automáticamente al cargar el POA. La contraseña por defecto es <strong>123456</strong>.</p>
        </div>

        <div class="table-container">
            <table id="tabla-usuarios" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario / Programa</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th style="text-align: center;">Seguridad</th>
                    </tr>
                </thead>
                <tbody id="lista-usuarios">
                    <tr><td colspan="5" style="text-align:center; padding: 20px;">Cargando usuarios...</td></tr>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalPassword" class="modal">
        <div class="modal-content">
            <span class="close" onclick="cerrarModal()">&times;</span>
            <h2 style="margin-top:0; color: #0055a4;">Cambiar Contraseña</h2>
            
            <form id="form-password">
                <input type="hidden" id="user_id" name="id">
                
                <p style="font-size: 0.9rem; color: #666;">Estás cambiando la contraseña para:</p>
                <div id="user_name_display" class="user-info"></div>

                <div class="input-group">
                    <label for="new_password">Nueva Contraseña:</label>
                    <input type="text" name="new_password" id="new_password" placeholder="Escribe la nueva clave" required>
                    <small style="display:block; margin-top:5px; color:#888;">* Se guardará encriptada automáticamente.</small>
                </div>

                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" onclick="cerrarModal()" style="padding: 10px 15px; cursor: pointer; border:1px solid #ccc; background:white; border-radius:4px;">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; margin-left:10px;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/usuarios.js?v=<?php echo time(); ?>"></script>
</body>
</html>