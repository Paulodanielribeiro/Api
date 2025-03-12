<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
        if (isset($_GET['action']) && $_GET['action'] === 'login') {
            loginUser($pdo);
        } else {
            addProduto($pdo);
        }
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

// Funções para manipular o banco de dados (adicionar, atualizar, excluir, etc.)
function getProdutos($pdo) {
    $stmt = $pdo->query("SELECT * FROM produtos");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}

function addProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    error_log(json_encode($data)); // Depuração: Verifique os dados no log do servidor

    if (!isset($data['nome']) || !isset($data['quantidade']) || !isset($data['preco']) || !isset($data['descricao'])) {
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
    echo json_encode($produto ?: ["error" => "Produto não encontrado"]);
}


function getProdutoByNome($pdo, $nome) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->execute(["%" . $nome . "%"]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}

function loginUser($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['email']) || !isset($data['senha'])) {
        echo json_encode(["error" => "E-mail e senha são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($data['senha'], $user['senha'])) {
            $token = bin2hex(random_bytes(32)); // Gerando um token de sessão simples
            echo json_encode(["message" => "Login bem-sucedido!", "token" => $token]);
        } else {
            echo json_encode(["error" => "Credenciais inválidas"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao realizar login: " . $e->getMessage()]);
    }
}
?>
