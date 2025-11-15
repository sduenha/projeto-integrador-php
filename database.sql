-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS gestao_aulas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestao_aulas;

-- Tabela de Alunos
CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    data_nascimento DATE,
    endereco TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Professores
CREATE TABLE professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telefone VARCHAR(20),
    especialidade VARCHAR(100),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Modalidades
CREATE TABLE modalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    duracao_minutos INT NOT NULL,
    vagas_maximas INT DEFAULT 20,
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Aulas
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    modalidade_id INT NOT NULL,
    professor_id INT NOT NULL,
    dia_semana ENUM('Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado', 'Domingo') NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    vagas_disponiveis INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Matrículas (vínculo aluno-aula)
CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    aula_id INT NOT NULL,
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_matricula (aluno_id, aula_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Professor-Modalidade (vínculo)
CREATE TABLE professor_modalidade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    modalidade_id INT NOT NULL,
    FOREIGN KEY (professor_id) REFERENCES professores(id) ON DELETE CASCADE,
    FOREIGN KEY (modalidade_id) REFERENCES modalidades(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vinculo (professor_id, modalidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para melhor performance
CREATE INDEX idx_aulas_professor ON aulas(professor_id);
CREATE INDEX idx_aulas_modalidade ON aulas(modalidade_id);
CREATE INDEX idx_aulas_horario ON aulas(dia_semana, hora_inicio);
CREATE INDEX idx_matriculas_aluno ON matriculas(aluno_id);
CREATE INDEX idx_matriculas_aula ON matriculas(aula_id);

-- Dados de exemplo
INSERT INTO modalidades (nome, descricao, duracao_minutos, vagas_maximas) VALUES
('Yoga', 'Aulas de yoga para todos os níveis', 60, 15),
('Pilates', 'Fortalecimento e flexibilidade', 50, 12),
('Dança Contemporânea', 'Expressão corporal e técnica', 90, 20),
('Ballet Clássico', 'Técnica clássica de ballet', 60, 15),
('Jazz Dance', 'Dança moderna e energética', 60, 18);