-- =========================================================
-- SISGED - Sistema de Gestão Educacional Dinâmico
-- Script de criação do banco de dados + dados de exemplo
-- Compatível com MySQL 5.7+ / MariaDB (WAMP)
-- =========================================================

CREATE DATABASE IF NOT EXISTS sisged CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sisged;

-- ---------------------------------------------------------
-- Tabela: instrutores
-- ---------------------------------------------------------
CREATE TABLE instrutores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    especialidade VARCHAR(120),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: alunos
-- ---------------------------------------------------------
CREATE TABLE alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    matricula VARCHAR(30) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: salas
-- ---------------------------------------------------------
CREATE TABLE salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL,
    capacidade INT NOT NULL DEFAULT 0,
    localizacao VARCHAR(120),
    recursos VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: turmas
-- ---------------------------------------------------------
CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    disciplina VARCHAR(120) NOT NULL,
    instrutor_id INT,
    data_inicio DATE,
    data_fim DATE,
    turno ENUM('Manhã','Tarde','Noite') NOT NULL DEFAULT 'Manhã',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_turma_instrutor FOREIGN KEY (instrutor_id) REFERENCES instrutores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: matriculas (N:N aluno <-> turma)
-- ---------------------------------------------------------
CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    data_matricula DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_matricula_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
    CONSTRAINT fk_matricula_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    UNIQUE KEY uk_aluno_turma (aluno_id, turma_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabela: aulas (agenda: liga turma + sala + instrutor + data/hora)
-- ---------------------------------------------------------
CREATE TABLE aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    sala_id INT NOT NULL,
    instrutor_id INT NOT NULL,
    data_aula DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    status ENUM('Agendada','Realizada','Cancelada') NOT NULL DEFAULT 'Agendada',
    observacoes VARCHAR(255),
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aula_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    CONSTRAINT fk_aula_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_aula_instrutor FOREIGN KEY (instrutor_id) REFERENCES instrutores(id) ON DELETE RESTRICT,
    CHECK (hora_fim > hora_inicio)
) ENGINE=InnoDB;

CREATE INDEX idx_aulas_sala_data ON aulas (sala_id, data_aula);
CREATE INDEX idx_aulas_instrutor_data ON aulas (instrutor_id, data_aula);

-- ---------------------------------------------------------
-- Tabela: usuarios (login do sistema)
-- perfil define o que o usuário enxerga; referencia_id aponta
-- para instrutores.id ou alunos.id conforme o perfil
-- (NULL quando perfil = coordenacao, que é administrativo)
-- ---------------------------------------------------------
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('aluno','instrutor','coordenacao') NOT NULL,
    referencia_id INT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- DADOS DE EXEMPLO (seed) - facilita teste e apresentação
-- Senha de todos os usuários de exemplo: "123456"
-- =========================================================

INSERT INTO instrutores (nome, email, telefone, especialidade) VALUES
('Carlos Andrade', 'carlos.andrade@sisged.com.br', '(31) 98888-1111', 'Desenvolvimento Web'),
('Fernanda Lima', 'fernanda.lima@sisged.com.br', '(31) 98888-2222', 'Redes de Computadores'),
('Marcos Souza', 'marcos.souza@sisged.com.br', '(31) 98888-3333', 'Banco de Dados');

INSERT INTO alunos (nome, email, matricula, telefone) VALUES
('Ana Beatriz Santos', 'ana.santos@aluno.sisged.com.br', '2026001', '(31) 97777-1111'),
('Bruno Costa', 'bruno.costa@aluno.sisged.com.br', '2026002', '(31) 97777-2222'),
('Camila Ferreira', 'camila.ferreira@aluno.sisged.com.br', '2026003', '(31) 97777-3333'),
('Diego Martins', 'diego.martins@aluno.sisged.com.br', '2026004', '(31) 97777-4444');

INSERT INTO salas (nome, capacidade, localizacao, recursos) VALUES
('Sala 101', 30, 'Bloco A - 1º andar', 'Projetor, Quadro branco'),
('Lab. Informática 1', 25, 'Bloco B - Térreo', '25 computadores, Projetor, Ar-condicionado'),
('Sala 205', 20, 'Bloco A - 2º andar', 'TV, Quadro branco');

INSERT INTO turmas (nome, disciplina, instrutor_id, data_inicio, data_fim, turno) VALUES
('Turma A - Dev Web 2026/2', 'Desenvolvimento Web', 1, '2026-08-03', '2026-12-18', 'Manhã'),
('Turma B - Redes 2026/2', 'Redes de Computadores', 2, '2026-08-03', '2026-12-18', 'Tarde'),
('Turma C - Banco de Dados 2026/2', 'Banco de Dados', 3, '2026-08-03', '2026-12-18', 'Noite');

INSERT INTO matriculas (aluno_id, turma_id) VALUES
(1, 1), (2, 1), (3, 2), (4, 2), (1, 3), (3, 3);

INSERT INTO aulas (turma_id, sala_id, instrutor_id, data_aula, hora_inicio, hora_fim, status, observacoes) VALUES
(1, 2, 1, '2026-09-02', '08:00:00', '10:00:00', 'Agendada', 'Introdução a HTML/CSS'),
(2, 1, 2, '2026-09-02', '13:30:00', '15:30:00', 'Agendada', 'Camadas do modelo OSI'),
(3, 3, 3, '2026-09-02', '19:00:00', '21:00:00', 'Agendada', 'Modelagem ER'),
(1, 2, 1, '2026-09-04', '08:00:00', '10:00:00', 'Agendada', 'Introdução a JavaScript');

-- Os usuários de login (tabela usuarios) NÃO são inseridos aqui porque a
-- senha precisa passar pela função password_hash() do PHP para ficar
-- correta. Depois de importar este arquivo, rode uma vez no navegador:
--   http://localhost/sisged/sql/seed_usuarios.php
-- Esse script cria os 3 usuários de exemplo abaixo, todos com senha "123456":
--   coordenacao@sisged.com.br      (perfil: coordenação)
--   carlos.andrade@sisged.com.br   (perfil: instrutor)
--   ana.santos@aluno.sisged.com.br (perfil: aluno)
