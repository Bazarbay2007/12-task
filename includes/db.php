<?php
require_once __DIR__ . '/config.php';

/**
 * PDO арқылы MySQL-ге қосылу.
 * Дерекқор мен кестелер жоқ болса, автоматты түрде құрылады.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // нағыз prepared statements
        ]);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . DB_NAME . '`');
        install_schema($pdo);
        migrate_schema($pdo);
    } catch (PDOException $e) {
        http_response_code(500);
        die('<h2>Дерекқорға қосылу қатесі</h2><p>XAMPP-та MySQL қосулы екенін тексеріңіз.</p>');
    }
    return $pdo;
}

/** Бар дерекқорды жаңарту: профиль бағандары және сөздік кестесі (қайталанса зиян жоқ) */
function migrate_schema(PDO $pdo): void
{
    if (isset($_SESSION) && ($_SESSION['schema_v'] ?? 0) >= 2) {
        return;
    }
    $cols = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $add = ['phone' => 'VARCHAR(20) NULL', 'faculty' => 'VARCHAR(100) NULL',
            'course' => 'TINYINT UNSIGNED NULL', 'avatar' => 'VARCHAR(255) NULL'];
    foreach ($add as $c => $def) {
        if (!in_array($c, $cols, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $c $def");
        }
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS words (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        word VARCHAR(100) NOT NULL,
        translation VARCHAR(150) NOT NULL,
        example VARCHAR(255) NULL,
        status ENUM('learning','learned') NOT NULL DEFAULT 'learning',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_word (user_id, word),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    if (isset($_SESSION)) {
        $_SESSION['schema_v'] = 2;
    }
}

function install_schema(PDO $pdo): void
{
    $exists = $pdo->query("SHOW TABLES LIKE 'tasks'")->fetch();
    if ($exists) {
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        color VARCHAR(7) NOT NULL DEFAULT '#6366f1'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $pdo->prepare('INSERT IGNORE INTO categories (name, color) VALUES (?, ?)');
    foreach ([['Listening', '#6366f1'], ['Reading', '#0ea5e9'], ['Writing', '#ec4899'],
              ['Speaking', '#10b981'], ['Grammar', '#f59e0b'], ['Vocabulary', '#8b5cf6'],
              ['Mock Test', '#ef4444']] as $c) {
        $stmt->execute($c);
    }
}
