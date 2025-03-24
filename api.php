<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Methods, Authorization");

// Incluindo a conexão do banco de dados
include 'db.php';

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
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'stock_entry':
                    entradaProduto($pdo);
                    break;
                case 'stock_exit':
                    saidaProduto($pdo);
                    break;
                default:
                    echo json_encode(["error" => "Ação inválida"]);
            }
        } else {
            updateProduto($pdo);
        }
        break;
    
    case 'DELETE':
        deleteProduto($pdo);
        break;
    
    default:
        echo json_encode(["message" => "Método não permitido"]);
        break;
}

// Funções para manipular o banco de dados
function getProdutos($pdo) {
    $stmt = $pdo->query("SELECT * FROM produtos");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}

function addProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['nome'], $data['quantidade'], $data['preco'], $data['descricao'])) {
        echo json_encode(["error" => "Todos os campos são obrigatórios"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco, descricao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$data['nome'], $data['quantidade'], $data['preco'], $data['descricao']]);
        echo json_encode(["message" => "Produto adicionado com sucesso!", "id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao adicionar produto: " . $e->getMessage()]);
    }
}

function updateProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['id'], $data['nome'], $data['preco'], $data['descricao'])) {
        echo json_encode(["error" => "ID, nome, preço e descrição são obrigatórios"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, preco = ?, descricao = ? WHERE id = ?");
        $stmt->execute([$data['nome'], $data['preco'], $data['descricao'], $data['id']]);
        echo json_encode(["message" => "Produto atualizado com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao atualizar produto: " . $e->getMessage()]);
    }
}

function deleteProduto($pdo) {
    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID é obrigatório"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode(["message" => "Produto removido com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao remover produto: " . $e->getMessage()]);
    }
}
?>
