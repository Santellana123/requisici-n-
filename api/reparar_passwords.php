<?php
require '../includes/db_connect.php';

// Generar la encriptación REAL para "123456"
$pass = password_hash('123456', PASSWORD_DEFAULT);

// Actualizar la tabla de usuarios completa
$sql = "UPDATE usuarios SET password = '$pass'";

if ($conn->query($sql)) {
    echo "<h1>¡Contraseñas Actualizadas!</h1>";
    echo "<p>Ahora TODOS los usuarios (Admin, Becas, Deportes, etc.) tienen la clave: <strong>123456</strong></p>";
    echo "<p>Usuarios actualizados: " . $conn->affected_rows . "</p>";
    echo "<a href='../login.html'>--> IR AL LOGIN <--</a>";
} else {
    echo "Error: " . $conn->error;
}
?>