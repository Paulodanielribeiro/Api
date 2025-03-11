<?php
$host = "localhost"; // Endereço do servidor MySQL
$port = "3306"; // Porta do MySQL (se for 3307, altere aqui também)
$dbname = "estoque"; // Nome do banco de dados
$username = "root"; // Usuário do MySQL
$password = ""; // Senha do MySQL

try {
    // Criação da conexão com o banco de dados
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    // Configuração para exibir erros de conexão
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Caso a conexão seja bem-sucedida, você pode usar este código para teste
    // echo "Conexão bem-sucedida!";
} catch (PDOException $e) {
    // Exibindo erro caso a conexão falhe
    echo json_encode(["error" => $e->getMessage()]);
}
?>
