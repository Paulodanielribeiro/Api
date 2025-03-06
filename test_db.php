<?php
$host = 'localhost';
$port = '3306'; // ou 3307, dependendo do seu MySQL
$db = 'estoque';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
