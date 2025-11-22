<?php
$titulo = "Meu Perfil";
include 'includes/header.php';

// Buscar dados do usuário logado
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Se for aluno, buscar dados completos
$aluno = null;
if (isAluno() && $usuario['vinculo_id']) {
    $sql = "SELECT a.*, e.endereco, e.bairro, e.cep, e.numero, e.id_endereco
            FROM alunos a
            LEFT JOIN enderecos e ON a.id_endereco = e.id_endereco
            WHERE a.id_aluno = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario['vinculo_id']);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Se for professor, buscar dados completos
$professor = null;
if (isProfessor() && $usuario['vinculo_id']) {
    $sql = "SELECT * FROM professores WHERE id_professor = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario['vinculo_id']);
    $stmt->execute();
    $professor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizarDados($conn, $_POST['nome']);
    $senha_nova = $_POST['senha_nova'];
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "O nome é obrigatório";
    }
    
    $conn->begin_transaction();
    
    try {
        // 1. Atualizar usuário
        if (!empty($senha_nova)) {
            if (strlen($senha_nova) < 6) {
                $erros[] = "A senha deve ter no mínimo 6 caracteres";
            } else {
                $senha_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET nome = ?, senha = ? WHERE id_usuario = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssi", $nome, $senha_hash, $usuario_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $sql = "UPDATE usuarios SET nome = ? WHERE id_usuario = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $nome, $usuario_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // 2. Atualizar dados específicos do ALUNO
        if ($aluno) {
            $telefone = sanitizarDados($conn, $_POST['telefone']);
            $endereco = sanitizarDados($conn, $_POST['endereco']);
            $bairro = sanitizarDados($conn, $_POST['bairro']);
            $cep = sanitizarDados($conn, $_POST['cep']);
            $numero = sanitizarDados($conn, $_POST['numero']);
            
            // Atualizar ou criar endereço
            $id_endereco = $aluno['id_endereco'];
            
            if (!empty($endereco) || !empty($bairro) || !empty($cep) || !empty($numero)) {
                if ($id_endereco) {
                    $sql = "UPDATE enderecos SET endereco = ?, bairro = ?, cep = ?, numero = ? WHERE id_endereco = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $endereco, $bairro, $cep, $numero, $id_endereco);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $sql = "INSERT INTO enderecos (endereco, bairro, cep, numero) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $endereco, $bairro, $cep, $numero);
                    $stmt->execute();
                    $id_endereco = $conn->insert_id;
                    $stmt->close();
                }
            }
            
            $sql = "UPDATE alunos SET nome = ?, telefone = ?, id_endereco = ? WHERE id_aluno = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssii", $nome, $telefone, $id_endereco, $aluno['id_aluno']);
            $stmt->execute();
            $stmt->close();
        }
        
        // 3. Atualizar dados específicos do PROFESSOR
        if ($professor) {
            $telefone = sanitizarDados($conn, $_POST['telefone']);
            $especialidade = sanitizarDados($conn, $_POST['especialidade']);
            
            $sql = "UPDATE professores SET nome_professor = ?, telefone = ?, especialidade = ? WHERE id_professor = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $telefone, $especialidade, $professor['id_professor']);
            $stmt->execute();
            $stmt->close();
        }
        
        if (empty($erros)) {
            $conn->commit();
            $_SESSION['usuario_nome'] = $nome;
            definirMensagem('success', 'Perfil atualizado com sucesso!');
            header('Location: perfil.php');
            exit;
        } else {
            $conn->rollback();
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $erros[] = "Erro ao atualizar perfil: " . $e->getMessage();
    }
    
    if (!empty($erros)) {
        foreach ($erros as $erro) {
            echo "<div class='mensagem mensagem-erro'>{$erro}</div>";
        }
    }
}
?>

<div class="content-header">
    <h2>🪪 Meu Perfil</h2>
</div>

<div class="section">
    <h3 class="section-title">Informações da Conta</h3>
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($usuario['nome']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" disabled style="background: var(--light-bg); cursor: not-allowed;">
                <small style="color: var(--gray-text);">O email não pode ser alterado</small>
            </div>
        </div>
        
        <?php if ($aluno): ?>
            <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--primary-color);">📋 Dados Pessoais</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($aluno['telefone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="data_nascimento">Data de Nascimento</label>
                    <input type="date" id="data_nascimento" value="<?php echo $aluno['data_nascimento']; ?>" disabled style="background: var(--light-bg); cursor: not-allowed;">
                    <small style="color: var(--gray-text);">A data de nascimento não pode ser alterada</small>
                </div>
            </div>
            
            <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--primary-color);">🏠 Endereço</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" placeholder="00000-000" value="<?php echo htmlspecialchars($aluno['cep']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($aluno['bairro']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group" style="flex: 3;">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" placeholder="Rua, Avenida, etc" value="<?php echo htmlspecialchars($aluno['endereco']); ?>">
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label for="numero">Número</label>
                    <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($aluno['numero']); ?>">
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($professor): ?>
            <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--primary-color);">📋 Dados Profissionais</h4>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo htmlspecialchars($professor['telefone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="especialidade">Especialidade</label>
                    <input type="text" id="especialidade" name="especialidade" placeholder="Ex: Yoga, Pilates" value="<?php echo htmlspecialchars($professor['especialidade']); ?>">
                </div>
            </div>
        <?php endif; ?>
        
        <h4 style="margin-top: 20px; margin-bottom: 15px; color: var(--primary-color);">🔒 Segurança</h4>
        
        <div class="form-group">
            <label for="senha_nova">Nova Senha (deixe em branco para manter a atual)</label>
            <input type="password" id="senha_nova" name="senha_nova" minlength="6" placeholder="Mínimo 6 caracteres">
            <small style="color: var(--gray-text); display: block; margin-top: 5px;">
                💡 Só preencha este campo se quiser alterar sua senha
            </small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">💾 Salvar Alterações</button>
            <a href="index.php" class="btn btn-secondary">↩️ Voltar</a>
        </div>
    </form>
</div>

<div class="section">
    <h3 class="section-title">📊 Informações do Sistema</h3>
    <div class="info-list">
        <div class="info-item">
            <span class="info-label">Tipo de Usuário:</span>
            <span class="info-value">
                <span class="badge badge-info">
                    <?php echo obterNomeTipoUsuario($usuario['tipo_usuario']); ?>
                </span>
            </span>
        </div>
        <div class="info-item">
            <span class="info-label">Membro desde:</span>
            <span class="info-value"><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></span>
        </div>
        <?php if ($usuario['ultimo_acesso']): ?>
            <div class="info-item">
                <span class="info-label">Último Acesso:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])); ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isAluno() && $aluno): ?>
    <?php
    // Buscar aulas matriculadas do aluno
    $sql = "SELECT m.*, 
            a.id_aula,
            mo.nome as modalidade_nome, mo.duracao_minutos,
            p.nome_professor as professor_nome,
            GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
            FROM matriculas m
            JOIN aulas a ON m.aula_id = a.id_aula
            JOIN modalidades mo ON a.modalidade_id = mo.id_modalidade
            JOIN professores p ON a.professor_id = p.id_professor
            LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
            WHERE m.aluno_id = ? AND m.ativo = 1
            GROUP BY m.id_matricula
            ORDER BY mo.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $aluno['id_aluno']);
    $stmt->execute();
    $matriculas = $stmt->get_result();
    $stmt->close();
    ?>
    
    <div class="section">
        <h3 class="section-title" id="minhas-aulas">📅 Minhas Aulas</h3>
        
        <?php if ($matriculas && $matriculas->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Modalidade</th>
                            <th>Professor</th>
                            <th>Horários</th>
                            <th>Duração</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($mat = $matriculas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($mat['modalidade_nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mat['professor_nome']); ?></td>
                                <td><?php echo $mat['horarios'] ?: 'Sem horários'; ?></td>
                                <td><?php echo $mat['duracao_minutos']; ?> min</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">
                <p style="color: #1e40af; margin-bottom: 15px;">
                    Você ainda não está matriculado em nenhuma aula. Entre em contato com o estúdio para realizar sua matrícula.
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isProfessor() && $professor): ?>
    <?php
    // Buscar aulas do professor
    $sql = "SELECT a.*, 
            m.nome as modalidade_nome, m.duracao_minutos,
            (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id_aula AND ativo = 1) as total_alunos,
            GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
            FROM aulas a
            JOIN modalidades m ON a.modalidade_id = m.id_modalidade
            LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
            WHERE a.professor_id = ? AND a.ativo = 1
            GROUP BY a.id_aula
            ORDER BY m.nome";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $professor['id_professor']);
    $stmt->execute();
    $aulas = $stmt->get_result();
    $stmt->close();
    ?>
    
    <div class="section">
        <h3 class="section-title">📅 Minhas Aulas</h3>
        
        <?php if ($aulas && $aulas->num_rows > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Modalidade</th>
                            <th>Horários</th>
                            <th>Duração</th>
                            <th>Vagas</th>
                            <th>Alunos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($aula = $aulas->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($aula['modalidade_nome']); ?></strong></td>
                                <td><?php echo $aula['horarios'] ?: 'Sem horários'; ?></td>
                                <td><?php echo $aula['duracao_minutos']; ?> min</td>
                                <td><?php echo $aula['vagas_disponiveis']; ?></td>
                                <td><span class="badge badge-info"><?php echo $aula['total_alunos']; ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="background: #dbeafe; padding: 20px; border-radius: 8px; text-align: center;">
                <p style="color: #1e40af;">
                    Você ainda não possui aulas cadastradas no sistema.
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>