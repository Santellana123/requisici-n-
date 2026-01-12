<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'director_planeacion') {
    header("Location: index.php");
    exit;
}
?>