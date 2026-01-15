<?php
require_once 'db_connect.php'; 

$check_sql = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'director_planeacion'";
$check_res = $conn->query($check_sql);
$check_row = $check_res->fetch_assoc();

if ($check_row['total'] == 0) {
    // Verificamos que no estemos ya en la página de instalación para evitar bucle infinito
    if (basename($_SERVER['PHP_SELF']) != 'crear_admin_inicial.php') {
        header("Location: crear_admin_inicial.php");
        exit();
    }
}
?>