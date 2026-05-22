<?php
// config.php - Conexión PDO a MySQL
$host = 
$dbname = 
$username = 
$password = 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Se encuentran todas las claves necesarias para autenticarse con S3 
define('AWS_ACCESS_KEY', );
define('AWS_SECRET_KEY', );
define('AWS_BUCKET', );
define('AWS_REGION',);
?>


