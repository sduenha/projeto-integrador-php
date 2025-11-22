-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS gestao_aulas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestao_aulas;


-- Tabela de Endereços  

CREATE TABLE IF NOT EXISTS enderecos (
    id_endereco INT AUTO_INCREMENT PRIMARY KEY,
    bairro VARCHAR(100),
    cep VARCHAR(20),
    numero VARCHAR(20),
    endereco VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Alunos

CREATE TABLE IF NOT EXISTS alunos (
    id_aluno INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    ativo BOOLEAN DEFAULT TRUE,

    id_endereco INT,
    FOREIGN KEY (id_endereco) REFERENCES enderecos(id_endereco)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Professores

CREATE TABLE IF NOT EXISTS professores (
    id_professor INT AUTO_INCREMENT PRIMARY KEY,
    nome_professor VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    especialidade VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Modalidades 

CREATE TABLE IF NOT EXISTS modalidades (
    id_modalidade INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    duracao_minutos INT NOT NULL,
    vagas_maximas INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Aulas

CREATE TABLE IF NOT EXISTS aulas (
    id_aula INT AUTO_INCREMENT PRIMARY KEY,
    modalidade_id INT NOT NULL,
    professor_id INT NOT NULL,
    vagas_disponiveis INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id_modalidade) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professores(id_professor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de AulaHorario

CREATE TABLE IF NOT EXISTS aula_horario (
    id_aulahorario INT AUTO_INCREMENT PRIMARY KEY,
    dia_semana ENUM('Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    id_aula INT NOT NULL,

    FOREIGN KEY (id_aula) REFERENCES aulas(id_aula) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Matrículas

CREATE TABLE IF NOT EXISTS matriculas (
    id_matricula INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    aula_id INT NOT NULL,
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,

    FOREIGN KEY (aluno_id) REFERENCES alunos(id_aluno) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id_aula) ON DELETE CASCADE,

    UNIQUE KEY unique_matricula (aluno_id, aula_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela Professor-Modalidade

CREATE TABLE IF NOT EXISTS professor_modalidade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_professor INT NOT NULL,
    id_modalidade INT NOT NULL,

    FOREIGN KEY (id_professor) REFERENCES professores(id_professor) ON DELETE CASCADE,
    FOREIGN KEY (id_modalidade) REFERENCES modalidades(id_modalidade) ON DELETE CASCADE,

    UNIQUE KEY unique_vinculo (id_professor, id_modalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Tabela de Usuários

CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('proprietario', 'professor', 'aluno') DEFAULT 'aluno',
    vinculo_id INT DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_tipo (tipo_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Índices

CREATE INDEX idx_aulas_professor ON aulas(professor_id);
CREATE INDEX idx_aulas_modalidade ON aulas(modalidade_id);
CREATE INDEX idx_aula_horario ON aula_horario(dia_semana, hora_inicio);
CREATE INDEX idx_matriculas_aluno ON matriculas(aluno_id);
CREATE INDEX idx_matriculas_aula ON matriculas(aula_id);


-- Dados de exemplo

INSERT INTO modalidades (nome, descricao, vagas_maximas, duracao_minutos) VALUES
('Yoga', 'Aulas de yoga para todos os níveis', 15, 60),
('Pilates', 'Fortalecimento e flexibilidade', 12, 50),
('Dança Contemporânea', 'Expressão corporal e técnica', 20, 90),
('Ballet Clássico', 'Técnica clássica de ballet', 15, 60),
('Jazz Dance', 'Dança moderna e energética', 18, 60);


-- Inserir usuário administrador padrão
-- Senha: admin123

INSERT INTO usuarios (nome, email, senha, tipo_usuario) 
VALUES ('Administrador', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'proprietario');