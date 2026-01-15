<?php
require 'includes/db_connect.php';

// Lógica de Procesamiento del Formulario
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    // 1. Validaciones básicas
    if (empty($usuario) || empty($pass1)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif ($pass1 !== $pass2) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        // 2. Verificar (Doble seguridad) que NO exista ya un admin
        $check = $conn->query("SELECT COUNT(*) as total FROM usuarios");
        $row = $check->fetch_assoc();
        
        if ($row['total'] > 0) {
            $mensaje = "Error de seguridad: Ya existen usuarios en el sistema. Debe borrar este archivo.";
        } else {
            // 3. Crear el usuario Admin
            // Hasheamos la contraseña (IMPORTANTE)
            $passwordHash = password_hash($pass1, PASSWORD_DEFAULT);
            
            // Rol según tu base de datos: 'director_planeacion'
            $rol = 'director_planeacion'; 

            $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, password, rol, activo, created_at) VALUES (?, ?, ?, 1, NOW())");
            $stmt->bind_param("sss", $usuario, $passwordHash, $rol);

            if ($stmt->execute()) {
                // Éxito: Redirigir al login con mensaje
                echo "<script>
                        alert('¡Administrador creado con éxito! Ahora puedes iniciar sesión.');
                        window.location.href='index.php';
                      </script>";
                exit;
            } else {
                $mensaje = "Error SQL: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instalación Inicial del Sistema</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: #c0c0c0; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: sans-serif; }
        .install-card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        .install-card h2 { color: #2c3e50; margin-bottom: 20px; }
        .alert { background: #ffeeba; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 0.9em; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #1f219b; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        button:hover { background: #2d1b7e; }
    </style>
</head>
<body>

    <div class="install-card">
        <h2>Configuración Inicial</h2>
        <p>Bienvenido. No se han detectado usuarios en el sistema.</p>
        <p style="margin-bottom:20px; color:#666;">Cree la cuenta del <strong>Director de Planeación (Admin)</strong> para comenzar.</p>

        <?php if($mensaje): ?>
            <div class="alert"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label style="display:block; text-align:left; font-weight:bold;">Nombre de Usuario</label>
            <input type="text" name="usuario" placeholder="Ej. admin" required>

            <label style="display:block; text-align:left; font-weight:bold;">Contraseña</label>
            <input type="password" name="pass1" placeholder="Nueva contraseña" required>

            <label style="display:block; text-align:left; font-weight:bold;">Confirmar Contraseña</label>
            <input type="password" name="pass2" placeholder="Repita la contraseña" required>

            <button type="submit">Crear Administrador e Iniciar</button>
        </form>
    </div>

</body>
</html>