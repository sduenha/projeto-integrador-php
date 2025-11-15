<?php
$titulo = "Cadastrar Professor";
$nivel = 1;
include '../includes/header.php';

// Buscar modalidades para vincular
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $especialidade = sanitizarDados($conn, $_POST['especialidade']);
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
        // Verificar se email já existe
        $sql = "SELECT id FROM professores WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está cadastrado";
        }
        $stmt->close();
    }
    
    if (empty($erros)) {
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Inserir professor
            $sql = "INSERT INTO professores (nome, email, telefone, especialidade) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nome, $email, $telefone, $especialidade);
            $stmt->execute();
            
            $professor_id = $conn->insert_id;
            $stmt->close();
            
            // Inserir vínculos com modalidades (RF5)
            if (!empty($modalidades_selecionadas)) {
                $sql = "INSERT INTO professor_modalidade (professor_id, modalidade_id) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                
                foreach ($modalidades_selecionadas as $modalidade_id) {
                    $stmt->bind_param("ii", $professor_id, $modalidade_id);
                    $stmt->execute();
                }
                $stmt->close();
            }
            
            $conn->commit();
            definirMensagem('success', 'Professor cadastrado com sucesso!');
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $erros[] = "Erro ao cadastrar professor: " . $e->getMessage();
        }
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
}
?>

<div class="content-header">
    <h2>Cadastrar Novo Professor</h2>
</div>

<form method="POST" action="">
    <div class="form-row">
        <div class="form-group">
            <label for="nome">Nome Completo *</label>
            <input type="text" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="especialidade">Especialidade</label>
            <input type="text" id="especialidade" name="especialidade" placeholder="Ex: Yoga, Pilates" value="<?php echo isset($_POST['especialidade']) ? htmlspecialchars($_POST['especialidade']) : ''; ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label>Modalidades que Leciona (RF5)</label>
        <div style="background: var(--light-bg); padding: 15px; border-radius: 8px;">
            <?php if ($modalidades && $modalidades->num_rows > 0): ?>
                <?php while ($modalidade = $modalidades->fetch_assoc()): ?>
                    <label style="display: block; margin-bottom: 10px;">
                        <input type="checkbox" name="modalidades[]" value="<?php echo $modalidade['id']; ?>"
                            <?php echo (isset($_POST['modalidades']) && in_array($modalidade['id'], $_POST['modalidades'])) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($modalidade['nome']); ?>
                    </label>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--gray-text);">Nenhuma modalidade cadastrada. <a href="../modalidades/cadastrar.php">Cadastrar modalidade</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>