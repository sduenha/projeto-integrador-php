<?php
$titulo = "Cadastrar Professor";
$nivel = 1;
include '../includes/header.php';

if (!isProprietario()) {
    definirMensagem('error', 'Acesso negado! Apenas proprietários podem gerenciar professores.');
    header('Location: index.php');
    exit;
}

// Buscar modalidades para vincular
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");
$modalidades_especialidade = $conn->query("SELECT * FROM modalidades WHERE ativo = 1 ORDER BY nome");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_professor = sanitizarDados($conn, $_POST['nome_professor']);
    $email = sanitizarDados($conn, $_POST['email']);
    $telefone = sanitizarDados($conn, $_POST['telefone']);
    $especialidade_id = !empty($_POST['especialidade']) ? (int)$_POST['especialidade'] : null;
    $modalidades_selecionadas = isset($_POST['modalidades']) ? $_POST['modalidades'] : [];
    
    // Criar usuário?
    $criar_usuario = isset($_POST['criar_usuario']) ? true : false;
    $senha_inicial = $_POST['senha_inicial'];
    
    $erros = [];
    
    if (empty($nome_professor)) {
        $erros[] = "O nome é obrigatório";
    }
    
    if (empty($email)) {
        $erros[] = "O email é obrigatório";
    } elseif (!validarEmail($email)) {
        $erros[] = "Email inválido";
    } else {
        // Verificar se email já existe
        $sql = "SELECT id_professor FROM professores WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $erros[] = "Este email já está cadastrado";
        }
        $stmt->close();
    }
    
    // Validar senha se for criar usuário
    if ($criar_usuario) {
        if (empty($senha_inicial)) {
            $erros[] = "A senha inicial é obrigatória para criar acesso ao sistema";
        } elseif (strlen($senha_inicial) < 6) {
            $erros[] = "A senha deve ter no mínimo 6 caracteres";
        }
    }
    
    if (empty($erros)) {
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Buscar nome da especialidade (modalidade)
            $especialidade = null;
            if ($especialidade_id) {
                $sql = "SELECT nome FROM modalidades WHERE id_modalidade = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $especialidade_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $especialidade = $result->fetch_assoc()['nome'];
                }
                $stmt->close();
            }
            
            // 1. Inserir professor
            $sql = "INSERT INTO professores (nome_professor, email, telefone, especialidade) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $nome_professor, $email, $telefone, $especialidade);
            $stmt->execute();
            
            $professor_id = $conn->insert_id;
            $stmt->close();
            
            // 2. Inserir vínculos com modalidades (RF5)
            if (!empty($modalidades_selecionadas)) {
                $sql = "INSERT INTO professor_modalidade (id_professor, id_modalidade) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                
                foreach ($modalidades_selecionadas as $modalidade_id) {
                    $stmt->bind_param("ii", $professor_id, $modalidade_id);
                    $stmt->execute();
                }
                $stmt->close();
            }
            
            // 3. Criar usuário se solicitado
            if ($criar_usuario) {
                $senha_hash = password_hash($senha_inicial, PASSWORD_DEFAULT);
                $tipo_usuario = 'professor';
                
                $sql = "INSERT INTO usuarios (nome, email, senha, tipo_usuario, vinculo_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $nome_professor, $email, $senha_hash, $tipo_usuario, $professor_id);
                $stmt->execute();
                $stmt->close();
                
                $mensagem_sucesso = "Professor cadastrado com sucesso! Usuário criado com email: {$email} e senha: {$senha_inicial}";
            } else {
                $mensagem_sucesso = "Professor cadastrado com sucesso!";
            }
            
            $conn->commit();
            definirMensagem('success', $mensagem_sucesso);
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
    <div class="section">
        <h3 class="section-title">Dados Pessoais</h3>
        
        <div class="form-row">
            <div class="form-group">
                <label for="nome_professor">Nome Completo *</label>
                <input type="text" id="nome_professor" name="nome_professor" required 
                       value="<?php echo isset($_POST['nome_professor']) ? htmlspecialchars($_POST['nome_professor']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000" 
                       value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="especialidade">Modalidade Principal</label>
                <select id="especialidade" name="especialidade">
                    <option value="">Selecione uma especialidade...</option>
                    <?php while ($mod = $modalidades_especialidade->fetch_assoc()): ?>
                        <option value="<?php echo $mod['id_modalidade']; ?>"
                            <?php echo (isset($_POST['especialidade']) && $_POST['especialidade'] == $mod['id_modalidade']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mod['nome']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small style="color: var(--gray-text); display: block; margin-top: 5px;">
                    💡 A especialidade principal do professor
                </small>
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
                                <?php echo (isset($_POST['modalidades']) && in_array($modalidade['id_modalidade'], $_POST['modalidades'])) ? 'checked' : ''; ?>>
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
                <p style="color: var(--gray-text);">Nenhuma modalidade cadastrada. <a href="../modalidades/cadastrar.php">Cadastrar modalidade</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="section" style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6;">
        <h3 style="color: #1e40af; margin-bottom: 15px;">🔐 Acesso ao Sistema</h3>
        <p style="color: #1e40af; margin-bottom: 15px;">
            Marque a opção abaixo para criar automaticamente um usuário para este professor acessar o sistema e visualizar suas aulas.
        </p>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="criar_usuario" id="criar_usuario" onchange="toggleSenha()" 
                    <?php echo (isset($_POST['criar_usuario'])) ? 'checked' : ''; ?>>
                <strong>Criar acesso ao sistema para este professor</strong>
            </label>
        </div>
        
        <div class="form-group" id="campo-senha" style="display: <?php echo (isset($_POST['criar_usuario'])) ? 'block' : 'none'; ?>;">
            <label for="senha_inicial">Senha Inicial * (mín. 6 caracteres)</label>
            <input type="text" id="senha_inicial" name="senha_inicial" minlength="6" 
                placeholder="Digite uma senha inicial para o professor"
                value="<?php echo isset($_POST['senha_inicial']) ? htmlspecialchars($_POST['senha_inicial']) : ''; ?>">
            <small style="color: #1e40af; display: block; margin-top: 5px;">
                💡 Dica: Use uma senha simples como "123456" ou "primeiroAcesso". O professor poderá alterá-la depois.
            </small>
        </div>
    </div>
    
    <script>
    function toggleSenha() {
        const checkbox = document.getElementById('criar_usuario');
        const campoSenha = document.getElementById('campo-senha');
        campoSenha.style.display = checkbox.checked ? 'block' : 'none';
    }
    </script>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-success">💾 Cadastrar</button>
        <a href="index.php" class="btn btn-secondary">↩️ Cancelar</a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>