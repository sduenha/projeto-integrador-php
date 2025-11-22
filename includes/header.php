<?php
require_once __DIR__ . '/../config/database.php';

// Verificar login em todas as páginas
verificarLogin();

$conn = conectarBanco();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titulo) ? $titulo . ' - ' : ''; ?>Gestão de Aulas</title>
    <link rel="stylesheet" href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Sistema de Gestão de Aulas</h1>
            <p>Organize suas aulas, professores e alunos em um só lugar</p>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="color: rgba(255,255,255,0.95);">
                        👤 <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>
                        <span style="opacity: 0.8; margin-left: 10px;">
                            (<?php echo obterNomeTipoUsuario($_SESSION['usuario_tipo']); ?>)
                        </span>
                    </div>
                    <a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>logout.php" 
                       style="background: rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        🚪 Sair
                    </a>
                </div>
            </div>
        </header>

        <!-- <nav>
            <ul>
                <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>index.php">🏠 Início</a></li>
                
                <?php if (isProprietario()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>alunos/index.php">👥 Alunos</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>professores/index.php">👨‍🏫 Professores</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>modalidades/index.php">🎭 Modalidades</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>aulas/index.php">📅 Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>usuarios/index.php">🔐 Usuários</a></li>
                <?php elseif (isProfessor()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>professores/visualizar.php?id=<?php echo $_SESSION['vinculo_id']; ?>">📋 Minhas Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>alunos/index.php">👥 Alunos</a></li>
                <?php elseif (isAluno()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>alunos/visualizar.php?id=<?php echo $_SESSION['vinculo_id']; ?>">📋 Minhas Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>aulas/index.php">📅 Ver Aulas</a></li>
                <?php endif; ?>
            </ul>
        </nav> -->

        <nav class="nav-menu">
            <ul>
                <?php if (isProprietario()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>index.php">🏠 Início</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>alunos/index.php">👥 Alunos</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>professores/index.php">👨‍🏫 Professores</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>modalidades/index.php">🎭 Modalidades</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>aulas/index.php">📅 Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>usuarios/index.php">🔐 Usuários</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>perfil.php">🪪 Meu Perfil</a></li>
                <?php elseif (isProfessor()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>index.php">🏠 Início</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>aulas/index.php">📅 Ver Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>perfil.php">🪪 Meu Perfil</a></li>
                <?php elseif (isAluno()): ?>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>index.php">🏠 Início</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>aulas/index.php">📅 Ver Aulas</a></li>
                    <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>perfil.php">🪪 Meu Perfil</a></li>
                <?php endif; ?>
                <li><a href="<?php echo isset($nivel) ? str_repeat('../', $nivel) : ''; ?>logout.php">🚪 Sair</a></li>
            </ul>
        </nav>

        <main class="fade-in">
            <?php exibirMensagem(); ?>