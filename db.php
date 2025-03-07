<?php
$host = "localhost"; // ou "localhost"
$port = "3306"; // ou "3306", se voltou ao padrão
$dbname = "estoque";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=localhost;port=3307;dbname=estoque", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

?>
