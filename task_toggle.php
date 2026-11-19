<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_check();

$s = db()->prepare("UPDATE tasks SET status = IF(status = 'done', 'todo', 'done') WHERE id = ? AND user_id = ?");
$s->execute([(int)($_POST['id'] ?? 0), $_SESSION['user_id']]);
redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
