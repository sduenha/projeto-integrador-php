<?php
$titulo = "Editar Professor";
$nivel = 1;
include '../includes/header.php';

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do professor não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do professor
$sql = "SELECT * FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    definirMensagem('error', 'Professor não encontrado');
    header('Location: index.php');
    exit;
}

$professor = $result->fetch_assoc();
$stmt->close();

// Buscar modalidades vinculadas
$sql = "SELECT modalidade_id FROM professor_modalidade WHERE professor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$modalidades_vinculadas = [];
while ($row = $result->fetch_assoc()) {
    $modalidades_vinculadas[] = $row['modalidade_id'];
}
$stmt->close();

// Buscar todas modalidades
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $especialidade = sanitizarDados($conn, $_POST['especialidade']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $modalidades_selecionadas = isset($_POST['modalidades']) ? $_POST['modalidades'] : [];
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe para outro professor
        $sql = "SELECT id FROM professores WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está cadastrado para outro professor";
        }
        $stmt->close();
    }
    
    if (empty($erros)) {
        $conn->begin_transaction();
        
        try {
            // Atualizar professor
            $sql = "UPDATE professores SET nome = ?, email = ?, telefone = ?, especialidade = ?, ativo = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $nome, $email, $telefone, $especialidade, $ativo, $id);
            $stmt->execute();
            $stmt->close();
            
            // Atualizar vínculos com modalidades
            // Remover todos os vínculos antigos
            $sql = "DELETE FROM professor_modalidade WHERE professor_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Inserir novos vínculos
            if (!empty($modalidades_selecionadas)) {
                $sql = "INSERT INTO professor_modalidade (professor_id, modalidade_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                
                foreach ($modalidades_selecionadas as $modalidade_id) {
                    $stmt->bind_param("ii", $id, $modalidade_id);
                    $stmt->execute();
                }
                $stmt->close();
            }
            
            $conn->commit();
            definirMensagem('success', 'Professor atualizado com sucesso!');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao atualizar professor: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
} else {
    $_POST = $professor;
}
?>

<div class="content-header">
    <h2>Editar Professor</h2>
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="nome">Nome Completo *</label>
            <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($_POST['nome']); ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email']); ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($_POST['telefone']); ?>">
        </div>
        
        <div class="form-group">
            <label for="especialidade">Especialidade</label>
            <input type="text" id="especialidade" name="especialidade" value="<?php echo htmlspecialchars($_POST['especialidade']); ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label>Modalidades que Leciona (RF5)</label>
        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
            <?php if ($modalidades && $modalidades->num_rows > 0): ?>
                <?php while ($modalidade = $modalidades->fetch_assoc()): ?>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="checkbox" name="modalidades[]" value="<?php echo $modalidade['id']; ?>"
                            <?php echo in_array($modalidade['id'], $modalidades_vinculadas) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($modalidade['nome']); ?>
                    </label>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--gray-text);">Nenhuma modalidade cadastrada.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="ativo" <?php echo $professor['ativo'] ? 'checked' : ''; ?>>
            Professor Ativo
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>