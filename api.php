<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

// Função para registrar logs
function logMessage($message) {
    file_put_contents("log.txt", date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

logMessage("Requisição recebida: " . $_SERVER['REQUEST_METHOD']);

// Permitir requisições OPTIONS
if ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
    header("HTTP/1.1 200 OK");
    exit();
}

function getProducts($id = null) {
    global $pdo;
    try {
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($product ?: ["error" => "Produto não encontrado"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM produtos");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($products);
        }
    } catch (Exception $e) {
        logMessage("Erro ao buscar produtos: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao buscar produtos"]);
    }
}

function addProduct($data) {
    global $pdo;
    if (!isset($data['nome'], $data['descricao'], $data['preco'], $data['quantidade']) || 
        !is_numeric($data['preco']) || !is_numeric($data['quantidade'])) {
        echo json_encode(["error" => "Campos obrigatórios ausentes ou inválidos"]);
        return;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['descricao'], $data['preco'], $data['quantidade']]);
        echo json_encode(["success" => "Produto adicionado com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao adicionar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao adicionar produto"]);
    }
}

function saidaProduto($data) {
    global $pdo;
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válidos são obrigatórios"]);
        return;
    }
    try {
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$produto) {
            echo json_encode(["error" => "Produto não encontrado"]);
            return;
        }
        if ($produto['quantidade'] < $data['quantidade']) {
            echo json_encode(["error" => "Estoque insuficiente"]);
            return;
        }
        
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);
        echo json_encode(["success" => "Saída de estoque realizada com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao processar saída de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar saída de estoque"]);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$requestData = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            getProducts($_GET['id']);
        } else {
            getProducts();
        }
        break;
    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] == 'saida') {
            saidaProduto($requestData);
        } else {
            addProduct($requestData);
        }
        break;
    default:
        echo json_encode(["error" => "Método não permitido"]);
        break;
}
