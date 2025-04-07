<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

// Lê a entrada uma única vez
$input = file_get_contents("php://input");
$requestData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Erro no formato JSON recebido"]);
    exit;
}

// Se estiver em ambiente de desenvolvimento, pode usar debug condicional
// if (defined('DEBUG') && DEBUG === true) {
//     error_log(print_r($requestData, true));
// }

// Função para registrar logs
function logMessage($message) {
    // Certifique-se de que a pasta "logs" existe e tem permissão de escrita
    file_put_contents("logs/api.log", date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

// Função para entrada de estoque
function entradaEstoque($data) {
    global $pdo;

    if (!isset($data['id'], $data['quantidade']) || 
        !is_numeric($data['id']) || 
        !is_numeric($data['quantidade']) || 
        $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válidos são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + :quantidade WHERE id = :id");
        $stmt->bindParam(':quantidade', $data['quantidade'], PDO::PARAM_INT);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Entrada de estoque realizada com sucesso!"]);
        } else {
            echo json_encode(["error" => "Nenhuma alteração realizada. Produto pode não existir."]);
        }
    } catch (Exception $e) {
        logMessage("Erro ao processar entrada de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar entrada de estoque"]);
    }
}

// Função para saída de estoque
function saidaEstoque($data) {
    global $pdo;

    if (empty($data) || !is_array($data)) {
        logMessage("Erro: JSON inválido ou requisição vazia.");
        echo json_encode(["error" => "Erro no formato JSON recebido"]);
        return;
    }

    if (!isset($data['id'], $data['quantidade'])) {
        logMessage("Erro: Campos obrigatórios ausentes. Dados recebidos: " . json_encode($data));
        echo json_encode(["error" => "ID e quantidade são obrigatórios"]);
        return;
    }

    $id = filter_var($data['id'], FILTER_VALIDATE_INT);
    $quantidade = filter_var($data['quantidade'], FILTER_VALIDATE_INT);

    if (!$id || !$quantidade || $quantidade <= 0) {
        echo json_encode(["error" => "ID e quantidade devem ser números válidos e positivos"]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verifica se o produto existe e obtém a quantidade atual (trava a linha para evitar condições de corrida)
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = :id FOR UPDATE");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            echo json_encode(["error" => "Produto não encontrado"]);
            $pdo->rollBack();
            return;
        }

        if ($produto['quantidade'] < $quantidade) {
            echo json_encode(["error" => "Estoque insuficiente"]);
            $pdo->rollBack();
            return;
        }

        // Atualiza a quantidade
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - :quantidade WHERE id = :id");
        $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $pdo->commit();
        echo json_encode([
            "success" => "Saída de estoque realizada com sucesso!", 
            "quantidade_restante" => $produto['quantidade'] - $quantidade
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        logMessage("Erro ao processar saída de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar saída de estoque"]);
    }
}

// Função para excluir produto
function deleteProduct($id) {
    global $pdo;
    
    if (!is_numeric($id)) {
        echo json_encode(["error" => "ID do produto inválido"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Produto excluído com sucesso"]);
        } else {
            echo json_encode(["error" => "Produto não encontrado ou já excluído"]);
        }
    } catch (Exception $e) {
        logMessage("Erro ao excluir produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao excluir produto"]);
    }
}

// Stub para updateProduct – implemente conforme necessário
function updateProduct($id, $data) {
    global $pdo;
    
    if (!isset($data['nome']) || empty($data['nome'])) {
        echo json_encode(["error" => "Nome do produto é obrigatório"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET nome = :nome WHERE id = :id");
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Produto atualizado com sucesso"]);
        } else {
            echo json_encode(["error" => "Nenhuma alteração realizada, produto pode não existir"]);
        }
    } catch (Exception $e) {
        logMessage("Erro ao atualizar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao atualizar produto"]);
    }
}

// Stub para editProduct – por enquanto, podemos reaproveitar a função updateProduct
function editProduct($id, $data) {
    updateProduct($id, $data);
}

// Tratamento das requisições
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'POST':
        if ($action === 'entrada') {
            entradaEstoque($requestData);
        } elseif ($action === 'saida') {
            saidaEstoque($requestData);
        } else {
            echo json_encode(["error" => "Ação inválida"]);
        }
        break;
    case 'PUT':
        if ($action === 'update' && $id) {
            updateProduct($id, $requestData);
        } else {
            echo json_encode(["error" => "ID e ação são obrigatórios"]);
        }
        break;
    case 'PATCH':
        if ($action === 'edit' && $id) {
            editProduct($id, $requestData);
        } else {
            echo json_encode(["error" => "ID e ação são obrigatórios"]);
        }
        break;
    case 'DELETE':
        if ($id) {
            deleteProduct($id);
        } else {
            echo json_encode(["error" => "ID obrigatório para exclusão"]);
        }
        break;
    default:
        echo json_encode(["error" => "Método não permitido"]);
        break;
}
?>