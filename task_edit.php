<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// READ — тек өз тапсырмасын көру (user_id тексеріледі)
$s = db()->prepare('SELECT * FROM tasks WHERE id = ? AND user_id = ?');
$s->execute([$id, $_SESSION['user_id']]);
$task = $s->fetch();
if (!$task) {
    flash('error', 'Тапсырма табылмады.');
    redirect('dashboard.php');
}

$errors = [];
$form = [
    'title' => $task['title'], 'description' => $task['description'] ?? '',
    'priority' => $task['priority'], 'category_id' => $task['category_id'] ?? '',
    'due_date' => $task['due_date'] ?? '', 'duration_min' => $task['duration_min'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $clean = [];
    $errors = validate_task($_POST, $clean);
    $form = array_merge($form, array_map(fn($v) => $v ?? '', $clean));
    if (!$errors) {
        // UPDATE — prepared statement
        $u = db()->prepare('UPDATE tasks SET category_id = ?, title = ?, description = ?, priority = ?, due_date = ?, duration_min = ?
                            WHERE id = ? AND user_id = ?');
        $u->execute([$clean['category_id'], $clean['title'], $clean['description'],
                     $clean['priority'], $clean['due_date'], $clean['duration_min'], $id, $_SESSION['user_id']]);
        flash('success', 'Тапсырма жаңартылды!');
        redirect('dashboard.php');
    }
}

$categories = get_categories();
$page_title = 'Тапсырманы өңдеу';
$submit_label = 'Жаңарту';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h2>Тапсырманы өңдеу</h2></div>
<?php require __DIR__ . '/includes/task_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
