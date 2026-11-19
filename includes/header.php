<?php
require_once __DIR__ . '/functions.php';
$flash = get_flash();
$page_title = $page_title ?? APP_NAME;
$active = $active ?? '';
if (is_logged_in() && !array_key_exists('user_avatar', $_SESSION)) {
    $a = db()->prepare('SELECT avatar FROM users WHERE id = ?');
    $a->execute([$_SESSION['user_id']]);
    $_SESSION['user_avatar'] = $a->fetchColumn() ?: null;
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav">
        <a class="logo" href="index.php"><span class="logo-icon">Q</span> <?= e(APP_NAME) ?></a>
        <button class="burger" id="burger" aria-label="Мәзір">☰</button>
        <nav id="menu" class="menu">
            <a href="index.php" class="<?= $active === 'home' ? 'on' : '' ?>">Басты бет</a>
            <?php if (is_logged_in()): ?>
                <a href="dashboard.php" class="<?= $active === 'plan' ? 'on' : '' ?>">📋 Жоспар</a>
                <a href="words.php" class="<?= $active === 'words' ? 'on' : '' ?>">📖 Сөздік</a>
                <a href="profile.php" class="user-chip <?= $active === 'profile' ? 'on' : '' ?>">
                    <?= avatar_html($_SESSION['user_avatar'] ?? null, $_SESSION['user_name'], 'avatar avatar-sm') ?>
                    <?= e($_SESSION['user_name']) ?>
                </a>
                <a class="btn btn-outline btn-sm" href="logout.php">Шығу</a>
            <?php else: ?>
                <a href="login.php">Кіру</a>
                <a class="btn btn-primary btn-sm" href="register.php">Тіркелу</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container main">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" data-autohide><?= e($flash['message']) ?></div>
<?php endif; ?>
