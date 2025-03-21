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
    
        case 'PUT':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'stock_entry':
                        entradaProduto($pdo); // Registrar entrada
                        break;
                    case 'stock_exit':
                        saidaProduto($pdo); // Registrar saída
                        break;
                    default:
                        echo json_encode(["error" => "Ação inválida"]);
                }
            } else {
                updateProduto($pdo); // Atualizar produto
            }
            break;
    
        case 'PUT':
            updateProduto($pdo);
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



function updateProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['nome']) || !isset($data['preco']) || !isset($data['descricao'])) {
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
function registrarMovimentacao($pdo, $produto_id, $tipo, $quantidade) {
    try {
        $stmt = $pdo->prepare("INSERT INTO movimentacoes_estoque (produto_id, tipo, quantidade) VALUES (?, ?, ?)");
        $stmt->execute([$produto_id, $tipo, $quantidade]);
    } catch (PDOException $e) {
        
        error_log("Erro ao registrar movimentação: " . $e->getMessage()); // Apenas log
    }
}


function entradaProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['quantidade'])) {
        echo json_encode(["error" => "ID do produto e quantidade são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);

        registrarMovimentacao($pdo, $data['id'], 'entrada', $data['quantidade']);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao adicionar entrada: " . $e->getMessage()]);
    }
}

function saidaProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['id']) || !isset($data['quantidade'])) {
        echo json_encode(["error" => "ID do produto e quantidade são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto || $produto['quantidade'] < $data['quantidade']) {
            echo json_encode(["error" => "Estoque insuficiente"]);
            return; // <-- Impede que a atualização continue
        }

        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);

        registrarMovimentacao($pdo, $data['id'], 'saida', $data['quantidade']);

        echo json_encode(["message" => "Saída registrada com sucesso!"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao registrar saída: " . $e->getMessage()]);
    }
}


?>
