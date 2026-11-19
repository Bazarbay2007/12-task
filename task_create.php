<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$errors = [];
$form = ['title' => '', 'description' => '', 'priority' => 'medium', 'category_id' => '', 'due_date' => '', 'duration_min' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $clean = [];
    $errors = validate_task($_POST, $clean);
    $form = array_merge($form, array_map(fn($v) => $v ?? '', $clean));
    if (!$errors) {
        // CREATE — prepared statement
        $s = db()->prepare('INSERT INTO tasks (user_id, category_id, title, description, priority, due_date, duration_min)
                            VALUES (?, ?, ?, ?, ?, ?, ?)');
        $s->execute([$_SESSION['user_id'], $clean['category_id'], $clean['title'],
                     $clean['description'], $clean['priority'], $clean['due_date'], $clean['duration_min']]);
        flash('success', 'Жаттығу жоспарға қосылды!');
        redirect('dashboard.php');
    }
}

$categories = get_categories();
$page_title = 'Жаңа жаттығу';
$submit_label = 'Сақтау';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><h2>Жаңа жаттығу</h2></div>
<?php require __DIR__ . '/includes/task_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
