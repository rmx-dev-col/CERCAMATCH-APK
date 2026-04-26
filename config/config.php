<?php
session_start();

// ============================================
// CONFIGURACIÓN SEGURA CON VARIABLES DE ENTORNO
// ============================================
// En local: crea un archivo .env con tus credenciales
// En producción (Render/Railway): agrega las variables en el panel
// ============================================

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'cercamatch_db';

// ============================================
// CONEXIÓN A LA BASE DE DATOS
// ============================================
$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    die(json_encode(['error' => 'Error de conexión: ' . mysqli_connect_error()]));
}

// ============================================
// SSL PARA AIVEN (solo en producción)
// ============================================
$isAiven = strpos($host, 'aivencloud.com') !== false;
if ($isAiven && file_exists(__DIR__ . '/ca.pem')) {
    mysqli_ssl_set($conn, NULL, NULL, __DIR__ . '/ca.pem', NULL, NULL);
}

// ============================================
// VERIFICAR COLUMNA 'foto_perfil'
// ============================================
$check = "SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'";
$result = mysqli_query($conn, $check);
if (mysqli_num_rows($result) == 0) {
    $sql = "ALTER TABLE usuarios ADD COLUMN foto_perfil VARCHAR(255) DEFAULT 'default-avatar.png'";
    mysqli_query($conn, $sql);
}

// No mostrar salida para no romper JSON
?>
