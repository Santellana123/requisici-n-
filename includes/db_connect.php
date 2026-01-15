<?php
// Ajusta con tus credenciales
$host = 'localhost';
$user = 'root';
$pass = ''; 
$db   = 'sistema_requisiciones_itsm';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>