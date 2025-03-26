<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

var_dump($_POST);
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("HTTP/1.1 200 OK");
    exit();
}

require_once "db.php";
file_put_contents("log.txt", print_r($_POST, true));

function getProducts() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM produtos");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($products);
    } catch (Exception $e) {
        echo json_encode(["error" => "Erro ao buscar produtos: " . $e->getMessage()]);
    }
}

function addProduct($data) {
    global $pdo;
    if (!isset($data['nome'], $data['descricao'], $data['preco'], $data['quantidade']) || 
        !is_numeric($data['preco']) || !is_numeric($data['quantidade'])) {
        echo json_encode(["error" => "Todos os campos são obrigatórios e devem ser numéricos, quando necessário."]);
        return;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['descricao'], $data['preco'], $data['quantidade']]);
        echo json_encode(["success" => "Produto adicionado com sucesso!"]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Erro ao adicionar produto: " . $e->getMessage()]);
    }
}

function entradaProduto($data) {
    global $pdo;
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válida são obrigatórios."]);
        return;
    }
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);
        echo json_encode(["success" => "Estoque atualizado com sucesso!"]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Erro ao atualizar estoque: " . $e->getMessage()]);
    }
}

function saidaProduto($data) {
    global $pdo;
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válida são obrigatórios."]);
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$produto) {
            echo json_encode(["error" => "Produto não encontrado."]);
            return;
        }
        if ($produto['quantidade'] < $data['quantidade']) {
            echo json_encode(["error" => "Estoque insuficiente."]);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);
        echo json_encode(["success" => "Saída de estoque realizada com sucesso!"]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Erro ao processar saída de estoque: " . $e->getMessage()]);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$requestData = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        getProducts();
        break;
    case 'POST':
        addProduct($requestData);
        break;
    case 'PUT':
        if (isset($_GET['action']) && $_GET['action'] == 'entrada') {
            entradaProduto($requestData);
        } elseif (isset($_GET['action']) && $_GET['action'] == 'saida') {
            saidaProduto($requestData);
        }
        break;
    default:
        echo json_encode(["error" => "Método não permitido"]);
        break;
}
