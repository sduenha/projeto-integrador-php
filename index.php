<?php
$titulo = "Início";
include 'includes/header.php';
?>

<div class="content-header">
    <h2>Bem-vindo ao Sistema de Gestão de Aulas</h2>
</div>

<div class="section">
    <h3 class="section-title">📊 Estatísticas Rápidas</h3>
    
    <?php
    // Buscar estatísticas
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM alunos WHERE ativo = 1");
    $stats['alunos'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM professores WHERE ativo = 1");
    $stats['professores'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM modalidades WHERE ativo = 1");
    $stats['modalidades'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM aulas WHERE ativo = 1");
    $stats['aulas'] = $result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as total FROM matriculas WHERE ativo = 1");
    $stats['matriculas'] = $result->fetch_assoc()['total'];
    ?>
    
    <div class="info-list">
        <?php if (isProprietario()): ?>
            <div class="info-item">
                <span class="info-label">Total de Alunos:</span>
                <span class="info-value"><strong><?php echo $stats['alunos']; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Professores:</span>
                <span class="info-value"><strong><?php echo $stats['professores']; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Modalidades:</span>
                <span class="info-value"><strong><?php echo $stats['modalidades']; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Aulas:</span>
                <span class="info-value"><strong><?php echo $stats['aulas']; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Matrículas Ativas:</span>
                <span class="info-value"><strong><?php echo $stats['matriculas']; ?></strong></span>
            </div>
        <?php elseif (isAluno() && isset($_SESSION['vinculo_id'])): ?>
            <?php
            // Buscar estatísticas do aluno
            $aluno_id = $_SESSION['vinculo_id'];
            
            $sql = "SELECT COUNT(*) as total FROM matriculas WHERE aluno_id = ? AND ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $aluno_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $minhas_matriculas = $result->fetch_assoc()['total'];
            $stmt->close();
            ?>
            
            <div class="info-item">
                <span class="info-label">Minhas Aulas Matriculadas:</span>
                <span class="info-value"><strong><?php echo $minhas_matriculas; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Aulas Disponíveis:</span>
                <span class="info-value"><strong><?php echo $stats['aulas']; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Modalidades:</span>
                <span class="info-value"><strong><?php echo $stats['modalidades']; ?></strong></span>
            </div>
        <?php elseif (isProfessor() && isset($_SESSION['vinculo_id'])): ?>
            <?php
            // Buscar estatísticas do professor
            $professor_id = $_SESSION['vinculo_id'];
            
            $sql = "SELECT COUNT(*) as total FROM aulas WHERE professor_id = ? AND ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $professor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $minhas_aulas = $result->fetch_assoc()['total'];
            $stmt->close();
            
            $sql = "SELECT COUNT(DISTINCT m.aluno_id) as total 
                    FROM matriculas m 
                    JOIN aulas a ON m.aula_id = a.id_aula 
                    WHERE a.professor_id = ? AND m.ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $professor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $meus_alunos = $result->fetch_assoc()['total'];
            $stmt->close();
            
            // Total de vagas ocupadas
            $sql = "SELECT COUNT(*) as total 
                    FROM matriculas m 
                    JOIN aulas a ON m.aula_id = a.id_aula 
                    WHERE a.professor_id = ? AND m.ativo = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $professor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_matriculas = $result->fetch_assoc()['total'];
            $stmt->close();
            ?>
            
            <div class="info-item">
                <span class="info-label">Minhas Aulas:</span>
                <span class="info-value"><strong><?php echo $minhas_aulas; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Alunos Únicos:</span>
                <span class="info-value"><strong><?php echo $meus_alunos; ?></strong></span>
            </div>
            <div class="info-item">
                <span class="info-label">Total de Matrículas:</span>
                <span class="info-value"><strong><?php echo $total_matriculas; ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="section">
    <h3 class="section-title">🚀 Links Rápidos</h3>
    
    <?php if (isProprietario()): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <a href="alunos/cadastrar.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">👥</div>
                <strong>Cadastrar Aluno</strong>
            </a>
            <a href="professores/cadastrar.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">👨‍🏫</div>
                <strong>Cadastrar Professor</strong>
            </a>
            <a href="modalidades/cadastrar.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🎭</div>
                <strong>Cadastrar Modalidade</strong>
            </a>
            <a href="aulas/cadastrar.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📅</div>
                <strong>Cadastrar Aula</strong>
            </a>
            <a href="aulas/matricular.php" class="btn btn-success" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">✅</div>
                <strong>Matricular Aluno</strong>
            </a>
            <a href="alunos/index.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                <strong>Ver Todos os Alunos</strong>
            </a>
            <a href="professores/index.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                <strong>Ver Todos os Professores</strong>
            </a>
            <a href="aulas/index.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📋</div>
                <strong>Ver Todas as Aulas</strong>
            </a>
        </div>
        
    <?php elseif (isAluno() && isset($_SESSION['vinculo_id'])): ?>
        <?php
        $aluno_id = $_SESSION['vinculo_id'];
        
        // Buscar próximas aulas do aluno
        $sql = "SELECT m.*, 
                mo.nome as modalidade_nome,
                p.nome_professor as professor_nome,
                GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
                FROM matriculas m
                JOIN aulas a ON m.aula_id = a.id_aula
                JOIN modalidades mo ON a.modalidade_id = mo.id_modalidade
                JOIN professores p ON a.professor_id = p.id_professor
                LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
                WHERE m.aluno_id = ? AND m.ativo = 1
                GROUP BY m.id_matricula
                ORDER BY mo.nome
                LIMIT 3";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $aluno_id);
        $stmt->execute();
        $proximas_aulas = $stmt->get_result();
        $stmt->close();
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <a href="perfil.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🪪</div>
                <strong>Meu Perfil</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Ver e editar meus dados</small>
            </a>
            <a href="perfil.php#minhas-aulas" class="btn btn-success" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📅</div>
                <strong>Minhas Aulas</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Ver aulas matriculadas</small>
            </a>
            <a href="aulas/index.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🔍</div>
                <strong>Ver Todas as Aulas</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Explorar modalidades</small>
            </a>
        </div>
        
        <?php if ($proximas_aulas && $proximas_aulas->num_rows > 0): ?>
            <div style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <h4 style="color: #1e40af; margin-bottom: 15px;">📅 Suas Próximas Aulas</h4>
                <div style="display: grid; gap: 10px;">
                    <?php while ($aula = $proximas_aulas->fetch_assoc()): ?>
                        <div style="background: white; padding: 15px; border-radius: 8px;">
                            <strong style="color: var(--dark-text); font-size: 1.1rem;">
                                <?php echo htmlspecialchars($aula['modalidade_nome']); ?>
                            </strong>
                            <p style="color: var(--gray-text); margin: 5px 0 0 0; font-size: 0.9rem;">
                                👨‍🏫 <?php echo htmlspecialchars($aula['professor_nome']); ?><br>
                                🕐 <?php echo $aula['horarios']; ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; text-align: center;">
                <h4 style="color: #92400e; margin-bottom: 10px;">📋 Nenhuma Aula Matriculada</h4>
                <p style="color: #92400e; margin-bottom: 15px;">
                    Entre em contato com o estúdio para realizar sua matrícula nas aulas desejadas.
                </p>
                <a href="aulas/index.php" class="btn btn-primary">Ver Aulas Disponíveis</a>
            </div>
        <?php endif; ?>
        
    <?php elseif (isProfessor() && isset($_SESSION['vinculo_id'])): ?>
        <?php
        $professor_id = $_SESSION['vinculo_id'];
        
        // Buscar aulas do professor
        $sql = "SELECT a.*, 
                m.nome as modalidade_nome,
                (SELECT COUNT(*) FROM matriculas WHERE aula_id = a.id_aula AND ativo = 1) as total_alunos,
                GROUP_CONCAT(CONCAT(ah.dia_semana, ' ', TIME_FORMAT(ah.hora_inicio, '%H:%i'), '-', TIME_FORMAT(ah.hora_fim, '%H:%i')) SEPARATOR ', ') as horarios
                FROM aulas a
                JOIN modalidades m ON a.modalidade_id = m.id_modalidade
                LEFT JOIN aula_horario ah ON a.id_aula = ah.id_aula
                WHERE a.professor_id = ? AND a.ativo = 1
                GROUP BY a.id_aula
                ORDER BY m.nome";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $professor_id);
        $stmt->execute();
        $minhas_aulas_lista = $stmt->get_result();
        $stmt->close();
        ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <a href="perfil.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">🪪</div>
                <strong>Meu Perfil</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Ver e editar meus dados</small>
            </a>
            <a href="professores/visualizar.php?id=<?php echo $professor_id; ?>" class="btn btn-success" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">📅</div>
                <strong>Minhas Aulas</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Ver e gerenciar aulas</small>
            </a>
            <a href="aulas/matricular.php" class="btn btn-primary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">✅</div>
                <strong>Matricular Aluno</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Adicionar aluno às minhas aulas</small>
            </a>
            <a href="alunos/index.php" class="btn btn-secondary" style="text-align: center; padding: 20px;">
                <div style="font-size: 2rem; margin-bottom: 10px;">👥</div>
                <strong>Ver Alunos</strong>
                <small style="display: block; margin-top: 5px; opacity: 0.9;">Lista completa de alunos</small>
            </a>
        </div>
        
        <?php if ($minhas_aulas_lista && $minhas_aulas_lista->num_rows > 0): ?>
            <div style="background: #dbeafe; padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                <h4 style="color: #1e40af; margin-bottom: 15px;">📚 Suas Aulas</h4>
                <div style="display: grid; gap: 10px;">
                    <?php while ($aula = $minhas_aulas_lista->fetch_assoc()): ?>
                        <div style="background: white; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <strong style="color: var(--dark-text); font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($aula['modalidade_nome']); ?>
                                </strong>
                                <p style="color: var(--gray-text); margin: 5px 0 0 0; font-size: 0.9rem;">
                                    🕐 <?php echo $aula['horarios'] ?: 'Sem horários definidos'; ?><br>
                                    👥 <?php echo $aula['total_alunos']; ?> / <?php echo $aula['vagas_disponiveis']; ?> alunos
                                </p>
                            </div>
                            <div>
                                <a href="aulas/matricular.php?aula_id=<?php echo $aula['id_aula']; ?>" class="btn btn-success btn-small">
                                    ➕ Matricular
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; text-align: center;">
                <h4 style="color: #92400e; margin-bottom: 10px;">📋 Nenhuma Aula Cadastrada</h4>
                <p style="color: #92400e;">
                    Você ainda não possui aulas cadastradas no sistema. Entre em contato com o administrador.
                </p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>