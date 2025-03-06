<?php
$host = 'localhost';
$port = '3307'; // Troque para 3307 se necessário
$db = 'estoque'; // Nome real do banco
$user = 'root'; // Usuário padrão do MySQL no XAMPP
$pass = ''; // Senha padrão no XAMPP é vazia

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erro na conexão: " . $e->getMessage()]);
    exit();
}

$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'GET':
        // Verifique se um ID ou nome foi fornecido na URL
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

    if (!isset($data['nome']) || !isset($data['quantidade']) || !isset($data['preco'])) {
        echo json_encode(["error" => "Todos os campos são obrigatórios"]);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, quantidade, preco) VALUES (?, ?, ?)");
        $stmt->execute([$data['nome'], $data['quantidade'], $data['preco']]);

        echo json_encode(["message" => "Produto adicionado com sucesso!", "id" => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erro ao adicionar produto: " . $e->getMessage()]);
    }
}



function updateProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, quantidade = ?, preco = ? WHERE id = ?");
    $stmt->execute([$data['nome'], $data['quantidade'], $data['preco'], $data['id']]);
    error_log("Recebendo requisição para adicionar produto");
    error_log(json_encode($data));

    echo json_encode(["message" => "Produto atualizado com sucesso!"]);
}

function deleteProduto($pdo) {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$data['id']]);
    echo json_encode(["message" => "Produto removido com sucesso!"]);
}
function getProdutoById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($produto);
}

function getProdutosByNome($pdo, $nome) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ?");
    $stmt->execute(['%' . $nome . '%']);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($produtos);
}
function updateQuantidade($pdo, $data) {
    // Verifique se o ID e a quantidade estão presentes no JSON
    if (isset($data['id']) && isset($data['quantidade'])) {
        // Prepare a instrução SQL
        $stmt = $pdo->prepare("UPDATE produtos SET quantidade = ? WHERE id = ?");
        
        // Execute a instrução e verifique se a atualização foi bem-sucedida
        if ($stmt->execute([$data['quantidade'], $data['id']])) {
            echo json_encode(["message" => "Quantidade atualizada com sucesso!"]);
        } else {
            echo json_encode(["error" => "Erro ao atualizar a quantidade."]);
        }
    } else {
        echo json_encode(["error" => "ID e quantidade são necessários."]);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    // Obtenha os dados JSON da requisição
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Chame a função para atualizar a quantidade
    updateQuantidade($pdo, $data);
}


?>
