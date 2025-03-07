<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$host = 'localhost';
$db = 'estoque';
$user = 'root';
$pass = '';
$port = '3307'; // Alterar para 3307 se necessário

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erro na conexão: " . $e->getMessage()]);
    exit();
}

$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getProdutoById($pdo, $_GET['id']);
        } elseif (isset($_GET['nome'])) {
            getProdutoByNome($pdo, $_GET['nome']);
        } else {
            getProdutos($pdo);
        }
        break;
    case 'POST':
        addProduto($pdo);
        break;
    case 'PUT':
        updateQuantidade($pdo);
        break;
    case 'DELETE':
        deleteProduto($pdo);
        break;
    default:
        echo json_encode(["message" => "Método não permitido"]);
        break;
}

function getProdutos($pdo) {
    $stmt = $pdo->query("SELECT * FROM produtos");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}

function addProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['name']) || !isset($data['quantity']) || !isset($data['price'])) {
        echo json_encode(["error" => "Todos os campos são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco) VALUES (?, ?, ?)");
        $stmt->execute([$data['name'], $data['quantity'], $data['price']]);
        echo json_encode(["message" => "Produto adicionado com sucesso!", "id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao adicionar produto: " . $e->getMessage()]);
    }
}

function updateQuantidade($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['quantity'])) {
        echo json_encode(["error" => "ID e quantidade são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?");
        $stmt->execute([$data['quantity'], $data['id']]);
        echo json_encode(["message" => "Quantidade atualizada com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao atualizar quantidade: " . $e->getMessage()]);
    }
}

function deleteProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id'])) {
        echo json_encode(["error" => "ID é obrigatório"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        echo json_encode(["message" => "Produto removido com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao remover produto: " . $e->getMessage()]);
    }
}

function getProdutoById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($produto);
}

function getProdutoByNome($pdo, $nome) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->execute(["%" . $nome . "%"]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}
?>
