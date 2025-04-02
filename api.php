<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";

// Função para registrar logs
function logMessage($message) {
    file_put_contents("logs/api.log", date("Y-m-d H:i:s") . " - " . $message . "\n", FILE_APPEND);
}

// Função para entrada de estoque
function entradaEstoque($data) {
    global $pdo;
    
    if (!isset($data['id'], $data['quantidade']) || !is_numeric($data['id']) || !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade válidos são obrigatórios"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade + ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);
        echo json_encode(["success" => "Entrada de estoque realizada com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao processar entrada de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar entrada de estoque"]);
    }
}

// Função para saída de estoque
// Função para saída de estoque corrigida
function saidaEstoque($data) {
    global $pdo;

    if (!isset($data['id'], $data['quantidade'])) {
        logMessage("Erro: Dados ausentes na requisição. Recebido: " . json_encode($data));
        echo json_encode(["error" => "ID e quantidade são obrigatórios"]);
        return;
    }

    if (!is_numeric($data['id']) || !is_numeric($data['quantidade']) || $data['quantidade'] <= 0) {
        echo json_encode(["error" => "ID e quantidade devem ser válidos"]);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verifica se o produto existe e pega a quantidade atual
        $stmt = $pdo->prepare("SELECT quantidade FROM produtos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            echo json_encode(["error" => "Produto não encontrado"]);
            $pdo->rollBack();
            return;
        }

        if ($produto['quantidade'] < $data['quantidade']) {
            echo json_encode(["error" => "Estoque insuficiente"]);
            $pdo->rollBack();
            return;
        }

        // Atualiza a quantidade do produto
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = quantidade - ? WHERE id = ?");
        $stmt->execute([$data['quantidade'], $data['id']]);

        $pdo->commit();
        echo json_encode(["success" => "Saída de estoque realizada com sucesso!"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        logMessage("Erro ao processar saída de estoque: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao processar saída de estoque"]);
    }
}

// Função para atualizar produto
function updateProduct($id, $data) {
    global $pdo;
    if (!is_numeric($id) || empty($data)) {
        echo json_encode(["error" => "ID e dados são obrigatórios"]);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, quantidade = ? WHERE id = ?");
        $stmt->execute([$data['nome'], $data['descricao'], $data['preco'], $data['quantidade'], $id]);
        echo json_encode(["success" => "Produto atualizado com sucesso!"]);
    } catch (Exception $e) {
        logMessage("Erro ao atualizar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao atualizar produto"]);
    }
}

// Função para editar produto (PATCH)
function editProduct($id, $data) {
    global $pdo;
    if (!is_numeric($id)) {
        echo json_encode(["error" => "ID inválido"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(["error" => "Produto não encontrado"]);
            return;
        }

        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            if (!empty($value)) { // Evita atualização com valores vazios
                $fields[] = "$key = ?";
                $values[] = $value;
            }
        }

        if (empty($fields)) {
            echo json_encode(["error" => "Nenhum campo válido para atualizar"]);
            return;
        }

        $values[] = $id;
        $sql = "UPDATE produtos SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        echo json_encode(["success" => "Produto atualizado parcialmente!"]);
    } catch (Exception $e) {
        logMessage("Erro ao editar produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao editar produto"]);
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
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(["success" => "Produto excluído com sucesso"]);
        } else {
            echo json_encode(["error" => "Produto não encontrado"]);
        }
    } catch (Exception $e) {
        logMessage("Erro ao excluir produto: " . $e->getMessage());
        echo json_encode(["error" => "Erro ao excluir produto"]);
    }
}

// Tratamento das requisições
$method = $_SERVER['REQUEST_METHOD'];
$requestData = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["error" => "Erro no formato JSON recebido"]);
    exit;
}

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
