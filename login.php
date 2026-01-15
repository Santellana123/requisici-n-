<?php
session_start();
// --- LÓGICA DE INSTALACIÓN ---
// Si ya hay sesión iniciada, redirigir
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['rol'] == 'director_planeacion') {
        header("Location: admin.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Verificar si existe la base de datos y usuarios (Instalador)
if (file_exists('includes/check_install.php')) {
    require_once 'includes/check_install.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Sistema POA</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Un pequeño fix para que el mensaje de error se vea bien */
        .alert-error {
            display: none;
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #ef9a9a;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-container">
        <form id="login-form">
            <div class="login-header">
                <h2>Iniciar Sesión</h2>
                <p>Sistema de Requisiciones POA</p>
            </div>
            
            <div id="mensaje-error" class="alert-error"></div>

            <div class="input-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej: sistemas" required autocomplete="username">
            </div>
            
            <div class="input-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" id="login-button">Entrar</button>
        </form>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>