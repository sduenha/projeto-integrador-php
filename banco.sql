CREATE DATABASE IF NOT EXISTS `banco_aulas` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `banco_aulas`;

-- Tabela de usuários
CREATE TABLE `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dados de exemplo (senha: 123456)
INSERT INTO `users` (`email`, `password`, `ativo`, `role`) VALUES
('admin@example.com', '$2y$10$u4nqY6HY0hF8j5m7KpQqhe7x9Z8wR3sQ2bL6tM4nP1kO9jI8hG7fW', 1, 'adm'),
('user@example.com', '$2y$10$u4nqY6HY0hF8j5m7KpQqhe7x9Z8wR3sQ2bL6tM4nP1kO9jI8hG7fW', 1, 'user');

-- Tabela de modalidades
CREATE TABLE `modalidades` (
  `modalidade_id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`modalidade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `modalidades` (`nome`, `descricao`, `ativo`) VALUES
('Yoga', 'Aulas de Yoga para todos os níveis', 1),
('Pilates', 'Fortalecimento e flexibilidade através do Pilates', 1),
('Dança Contemporânea', 'Expressão corporal e técnica em dança', 1);

ALTER TABLE `modalidades` MODIFY `modalidade_id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
ALTER TABLE `users` MODIFY `user_id` INT(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;