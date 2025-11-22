<?php
$titulo = "Editar Professor";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar professores.');
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    definirMensagem('error', 'ID do professor não informado');
    header('Location: index.php');
    exit;
}

$id = (int)$_GET['id'];

// Buscar dados do professor
$sql = "SELECT * FROM professores WHERE id_professor = ?";
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
$sql = "SELECT id_modalidade FROM professor_modalidade WHERE id_professor = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$modalidades_vinculadas = [];
while ($row = $result->fetch_assoc()) {
    $modalidades_vinculadas[] = $row['id_modalidade'];
}
$stmt->close();

// Buscar todas modalidades
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$modalidades_especialidade = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");

// Descobrir ID da especialidade atual
$especialidade_id = null;
if ($professor['especialidade']) {
    $sql = "SELECT id_modalidade FROM modalidades WHERE nome = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $professor['especialidade']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $especialidade_id = $result->fetch_assoc()['id_modalidade'];
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_professor = sanitizarDados($conn, $_POST['nome_professor']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $especialidade_id_post = !empty($_POST['especialidade']) ? (int)$_POST['especialidade'] : null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $modalidades_selecionadas = isset($_POST['modalidades']) ? $_POST['modalidades'] : [];
    
    $erros = [];
    
    if (empty($nome_professor)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe para outro professor
        $sql = "SELECT id_professor FROM professores WHERE email = ? AND id_professor != ?";
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
            // Buscar nome da especialidade (modalidade)
            $especialidade = null;
            if ($especialidade_id_post) {
                $sql = "SELECT nome FROM modalidades WHERE id_modalidade = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $especialidade_id_post);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $especialidade = $result->fetch_assoc()['nome'];
                }
                $stmt->close();
            }
            
            // Atualizar professor
            $sql = "UPDATE professores SET nome_professor = ?, email = ?, telefone = ?, especialidade = ?, ativo = ? WHERE id_professor = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $nome_professor, $email, $telefone, $especialidade, $ativo, $id);
            $stmt->execute();
            $stmt->close();
            
            // Atualizar vínculos com modalidades
            // Remover todos os vínculos antigos
            $sql = "DELETE FROM professor_modalidade WHERE id_professor = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            // Inserir novos vínculos
            if (!empty($modalidades_selecionadas)) {
                $sql = "INSERT INTO professor_modalidade (id_professor, id_modalidade) VALUES (?, ?)";
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
    <div class="section">
        <h3 class="section-title">Dados Pessoais</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nome_professor">Nome Completo *</label>
                <input type="text" id="nome_professor" name="nome_professor" required 
                       value="<?php echo htmlspecialchars($_POST['nome_professor']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email']); ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" 
                       value="<?php echo htmlspecialchars($_POST['telefone']); ?>">
            </div>
            
            <div class="form-group">
                <label for="especialidade">Modalidade Principal</label>
                <select id="especialidade" name="especialidade">
                    <option value="">Selecione uma especialidade...</option>
                    <?php while ($mod = $modalidades_especialidade->fetch_assoc()): ?>
                        <option value="<?php echo $mod['id_modalidade']; ?>"
                            <?php echo ($especialidade_id == $mod['id_modalidade']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mod['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
    </div>
    
    <div class="section">
        <h3 class="section-title">Modalidades que Leciona</h3>
        <p style="color: var(--gray-text); margin-bottom: 15px; font-size: 0.95rem;">
            Selecione todas as modalidades que este professor está apto a lecionar
        </p>
        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
            <?php if ($modalidades && $modalidades->num_rows > 0): ?>
                <?php while ($modalidade = $modalidades->fetch_assoc()): ?>
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--primary-color);">
                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                            <input type="checkbox" name="modalidades[]" value="<?php echo $modalidade['id_modalidade']; ?>"
                                style="margin-top: 4px;"
                                <?php echo in_array($modalidade['id_modalidade'], $modalidades_vinculadas) ? 'checked' : ''; ?>>
                            <div style="flex: 1;">
                                <strong style="color: var(--dark-text); font-size: 1rem;">
                                    <?php echo htmlspecialchars($modalidade['nome']); ?>
                                </strong>
                                <span style="color: var(--primary-color); font-weight: 600; margin-left: 10px;">
                                    (<?php echo $modalidade['duracao_minutos']; ?> min)
                                </span>
                                <?php if ($modalidade['descricao']): ?>
                                    <p style="color: var(--gray-text); font-size: 0.9rem; margin-top: 5px; margin-bottom: 0;">
                                        <?php echo htmlspecialchars($modalidade['descricao']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
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