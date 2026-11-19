-- IELTS Planner: дайындық жоспары (To-Do). phpMyAdmin-де Import жасауға болады
-- (сайт өзі де бірінші ашылғанда осыны автоматты түрде жасайды)
CREATE DATABASE IF NOT EXISTS ielts_planner CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ielts_planner;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    faculty VARCHAR(100) NULL,
    course TINYINT UNSIGNED NULL,
    avatar VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL DEFAULT '#6366f1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NULL,
    title VARCHAR(120) NOT NULL,
    description TEXT NULL,
    priority ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status ENUM('todo','done') NOT NULL DEFAULT 'todo',
    due_date DATE NULL,
    duration_min SMALLINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categories (name, color) VALUES
('Listening','#6366f1'),('Reading','#0ea5e9'),('Writing','#ec4899'),('Speaking','#10b981'),
('Grammar','#f59e0b'),('Vocabulary','#8b5cf6'),('Mock Test','#ef4444');

CREATE TABLE IF NOT EXISTS words (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    word VARCHAR(100) NOT NULL,
    translation VARCHAR(150) NOT NULL,
    example VARCHAR(255) NULL,
    status ENUM('learning','learned') NOT NULL DEFAULT 'learning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_word (user_id, word),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
