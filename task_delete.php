<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}
csrf_check();

// DELETE — prepared statement, тек өз тапсырмасы
$s = db()->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$s->execute([(int)($_POST['id'] ?? 0), $_SESSION['user_id']]);

flash($s->rowCount() ? 'success' : 'error', $s->rowCount() ? 'Тапсырма жойылды.' : 'Тапсырма табылмады.');
redirect('dashboard.php');
