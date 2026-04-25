<?php
session_start();

$host = 'mysql-3946982-yeinerenrique2007-cd2e.i.aivencloud.com';
$port = '27520';
$user = 'avnadmin';
$pass = 'AVNS_D2Hom75qqeQ2uwrMicb';
$dbname = 'cercamatch_db';

// Crear conexión
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Configurar SSL
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Verificar si la columna foto_perfil existe, si no, agregarla
$check = "SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'";
$result = mysqli_query($conn, $check);
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) DEFAULT 'default-avatar.png'";
    if (mysqli_query($conn, $sql)) {
        // Columna creada exitosamente
    }
}

// No hay echo aquí para no romper JSON
?>