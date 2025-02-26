<?php
$host = "127.0.0.1"; // ou "localhost"
$port = "3307"; // ou "3306", se voltou ao padrão
$dbname = "seu_banco";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

?>
