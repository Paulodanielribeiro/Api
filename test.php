<?php
$host = "localhost";
$db = "seu_banco";
$user = "usuario";
$pass = "senha";
$port = 3307;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    echo "✅ Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage();
}
?>
