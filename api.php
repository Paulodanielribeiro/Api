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
        !is_numeric($data['preco']) || !is_numeric($data['quantidade']) || $data['quantidade'] < 0) {
        echo json_encode(["error" => "Campos obrigatórios ausentes ou inválidos"]);
        return;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, quantidade) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['descricao'], $data['preco'], $data['quantidade']]);
        echo json_encode(["success" => "Produto adicionado com sucesso!", "id" => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        logMessage("Erro ao adicionar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao adicionar produto"]);
    }
}

function updateProduct($id, $data) {
    global $pdo;
    if (!is_numeric($id)) {
        echo json_encode(["error" => "ID inválido"]);
        return;
    }
    
    try {
        // Verificar se o produto existe
        $stmt = $pdo->prepare("SELECT id FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            echo json_encode(["error" => "Produto não encontrado"]);
            return;
        }
        
        // Construir a query dinamicamente com os campos fornecidos
        $fields = [];
        $values = [];
        
        if (isset($data['nome'])) {
            $fields[] = "nome = ?";
            $values[] = $data['nome'];
        }
        if (isset($data['descricao'])) {
            $fields[] = "descricao = ?";
            $values[] = $data['descricao'];
        }
        if (isset($data['preco'])) {
            if (!is_numeric($data['preco'])) {
                echo json_encode(["error" => "Preço inválido"]);
                return;
            }
            $fields[] = "preco = ?";
            $values[] = $data['preco'];
        }
        if (isset($data['quantidade'])) {
            if (!is_numeric($data['quantidade']) || $data['quantidade'] < 0) {
                echo json_encode(["error" => "Quantidade inválida"]);
                return;
            }
            $fields[] = "quantidade = ?";
            $values[] = $data['quantidade'];
        }
        
        if (empty($fields)) {
            echo json_encode(["error" => "Nenhum campo válido para atualizar"]);
            return;
        }
        
        $values[] = $id;
        $sql = "UPDATE produtos SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        echo json_encode(["success" => "Produto atualizado com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao atualizar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao atualizar produto"]);
    }
}

function deleteProduct($id) {
    global $pdo;
    if (!is_numeric($id)) {
        echo json_encode(["error" => "ID inválido"]);
        return;
    }
    
    try {
        // Verificar se o produto existe
        $stmt = $pdo->prepare("SELECT id FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            echo json_encode(["error" => "Produto não encontrado"]);
            return;
        }
        
        // Excluir o produto
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Produto excluído com sucesso!"]);
        } else {
            echo json_encode(["error" => "Nenhum produto foi excluído"]);
        }
    } catch (Exception $e) {
        logMessage("Erro ao excluir produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao excluir produto"]);
    }
}

function entradaEstoque($data) {
    global $pdo;
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || 
        !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válidos são obrigatórios"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(["error" => "Produto não encontrado"]);
            return;
        }
        
        echo json_encode(["success" => "Entrada de estoque realizada com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao processar entrada de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar entrada de estoque"]);
    }
}

function saidaEstoque($data) {
    global $pdo;
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || 
        !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válidos são obrigatórios"]);
        return;
    }
    
    try {
        // Verificar estoque suficiente
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ? FOR UPDATE");
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
        
        // Atualizar estoque
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
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'entrada':
                    entradaEstoque($requestData);
                    break;
                case 'saida':
                    saidaEstoque($requestData);
                    break;
                default:
                    echo json_encode(["error" => "Ação inválida"]);
                    break;
            }
        } else {
            addProduct($requestData);
        }
        break;
    case 'PUT':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            updateProduct($_GET['id'], $requestData);
        } else {
            echo json_encode(["error" => "ID do produto é obrigatório"]);
        }
        break;
    case 'DELETE':
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            deleteProduct($_GET['id']);
        } else {
            echo json_encode(["error" => "ID do produto é obrigatório"]);
        }
        break;
    default:
        echo json_encode(["error" => "Método não permitido"]);
        break;
}