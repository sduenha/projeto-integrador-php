<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../config/auth.php';
require_once '../../config/connect.php';

requireAuth();
requireAdmin();

$modalidade = null;
$message = '';
$message_type = '';
$id = 0;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_post = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $descricao = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

    if ($id_post <= 0) {
        $message = 'ID da modalidade é obrigatório.';
        $message_type = 'danger';
    } elseif (empty($nome)) {
        $message = 'Nome da modalidade é obrigatório.';
        $message_type = 'danger';
    } else {
        $sql = "UPDATE modalidades SET nome = ?, descricao = ? WHERE modalidade_id = ?";
        $stmt = $con->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param('ssi', $nome, $descricao, $id_post);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = 'Modalidade atualizada com sucesso!';
                    $message_type = 'success';
                    $id = $id_post;
                } else {
                    $message = 'Nenhuma alteração foi realizada. Verifique se os dados foram modificados.';
                    $message_type = 'info';
                    $id = $id_post;
                }
            } else {
                $message = 'Erro ao atualizar: ' . $stmt->error;
                $message_type = 'danger';
            }
            
            $stmt->close();
        } else {
            $message = 'Erro na preparação da query: ' . $con->error;
            $message_type = 'danger';
        }
    }
}

if ($id > 0) {
    $sql = "SELECT * FROM modalidades WHERE modalidade_id = ?";
    $stmt = $con->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $modalidade = $result->fetch_assoc();
            } else {
                if (empty($message)) {
                    $message = 'Modalidade com ID ' . $id . ' não foi encontrada no banco de dados.';
                    $message_type = 'danger';
                }
            }
        } else {
            if (empty($message)) {
                $message = 'Erro ao executar consulta: ' . $stmt->error;
                $message_type = 'danger';
            }
        }
        
        $stmt->close();
    } else {
        if (empty($message)) {
            $message = 'Erro ao preparar consulta: ' . $con->error;
            $message_type = 'danger';
        }
    }
} else {
    if (empty($message)) {
        $message = 'ID da modalidade não foi fornecido na URL. Acesse usando: update.php?id=1';
        $message_type = 'warning';
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Modalidade</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
        }
        h3 {
            color: #1e3a8a;
            margin-bottom: 25px;
            font-weight: 600;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .form-group label {
            font-weight: 500;
        }
        .breadcrumb {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <ol class="breadcrumb">
            <li><a href="../../dashboard.php">Home</a></li>
            <li><a href="get-with-pagination.php">Modalidades</a></li>
            <li class="active">Editar</li>
        </ol>

        <a href="get-with-pagination.php" class="btn btn-default" style="margin-bottom: 20px;">
            <span class="glyphicon glyphicon-arrow-left"></span> Voltar
        </a>

        <h3><i class="glyphicon glyphicon-edit"></i> Editar Modalidade</h3>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <strong>
                    <?php
                    echo match($message_type) {
                        'success' => 'Sucesso!',
                        'danger' => 'Erro!',
                        'warning' => 'Atenção!',
                        default => 'Info!'
                    };
                    ?>
                </strong>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($modalidade): ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">
                <strong>📝 Info:</strong> Editando modalidade ID: <strong><?php echo $modalidade['modalidade_id']; ?></strong>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo (int)$modalidade['modalidade_id']; ?>">
                
                <div class="form-group">
                    <label for="nome">Nome da Modalidade <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        id="nome" 
                        name="nome" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($modalidade['nome'] ?? '', ENT_QUOTES); ?>" 
                        required
                        maxlength="100"
                        placeholder="Ex: Futebol, Basquete, Voleibol"
                    >
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea 
                        id="descricao" 
                        name="descricao" 
                        class="form-control" 
                        rows="4"
                        maxlength="500"
                        placeholder="Descrição opcional da modalidade"
                    ><?php echo htmlspecialchars($modalidade['descricao'] ?? '', ENT_QUOTES); ?></textarea>
                    <small class="text-muted">Máximo 500 caracteres</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success">
                        <span class="glyphicon glyphicon-ok"></span> Atualizar
                    </button>
                    <a href="get-with-pagination.php" class="btn btn-default">
                        <span class="glyphicon glyphicon-remove"></span> Cancelar
                    </a>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">
                <h4><span class="glyphicon glyphicon-exclamation-sign"></span> Não foi possível carregar</h4>
                <p><strong>Possíveis causas:</strong></p>
                <ul>
                    <li>ID não fornecido na URL (use: <code>update.php?id=1</code>)</li>
                    <li>Modalidade não existe no banco</li>
                    <li>Erro de conexão</li>
                </ul>
                <a href="get-with-pagination.php" class="btn btn-default">
                    <span class="glyphicon glyphicon-arrow-left"></span> Voltar para Lista
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</body>
</html>