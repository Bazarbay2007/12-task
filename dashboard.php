<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$uid = $_SESSION['user_id'];

/* ---------- Іздеу, сүзу, сұрыптау ---------- */
$q      = trim($_GET['q'] ?? '');
$cat    = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$prio   = $_GET['priority'] ?? '';
$sort   = $_GET['sort'] ?? 'new';
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['t.user_id = ?'];
$params = [$uid];
if ($q !== '') {
    $where[] = '(t.title LIKE ? OR t.description LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
if ($cat !== '' && ctype_digit($cat)) {
    $where[] = 't.category_id = ?';
    $params[] = (int)$cat;
}
if (in_array($status, ['todo', 'done'], true)) {
    $where[] = 't.status = ?';
    $params[] = $status;
}
if (in_array($prio, ['low', 'medium', 'high'], true)) {
    $where[] = 't.priority = ?';
    $params[] = $prio;
}
$orders = [
    'new'  => 't.created_at DESC',
    'old'  => 't.created_at ASC',
    'due'  => 't.due_date IS NULL, t.due_date ASC',
    'prio' => "FIELD(t.priority,'high','medium','low')",
    'name' => 't.title ASC',
];
$orderBy = $orders[$sort] ?? $orders['new'];
$whereSql = implode(' AND ', $where);

/* ---------- Пагинация ---------- */
$c = db()->prepare("SELECT COUNT(*) FROM tasks t WHERE $whereSql");
$c->execute($params);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / PER_PAGE));
$page  = min($page, $pages);
$offset = ($page - 1) * PER_PAGE;

$sql = "SELECT t.*, c.name AS cat_name, c.color AS cat_color
        FROM tasks t LEFT JOIN categories c ON c.id = t.category_id
        WHERE $whereSql ORDER BY $orderBy LIMIT " . PER_PAGE . " OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

/* ---------- Статистика ---------- */
$st = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='done'),0) done,
                     COALESCE(SUM(status='todo' AND due_date < CURDATE()),0) overdue,
                     COALESCE(SUM(CASE WHEN status='done' THEN duration_min END),0) mins_done,
                     COALESCE(SUM(CASE WHEN status='todo' THEN duration_min END),0) mins_left
                     FROM tasks WHERE user_id = ?");
$st->execute([$uid]);
$stats = $st->fetch();
$percent = $stats['total'] > 0 ? round($stats['done'] / $stats['total'] * 100) : 0;

$categories = get_categories();
function page_url(int $p): string {
    $qs = $_GET; $qs['page'] = $p;
    return 'dashboard.php?' . http_build_query($qs);
}

$page_title = 'Жоспарым';
$active = 'plan';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h2>IELTS дайындық жоспарым</h2>
    <div class="hero-actions" style="margin:0">
        <form method="post" action="task_template.php" data-confirm="24 жаттығудан тұратын 4 апталық IELTS жоспарын қосайын ба?" style="margin:0">
            <?= csrf_field() ?>
            <button class="btn btn-outline" type="submit">📋 Дайын 4 апталық жоспар</button>
        </form>
        <a class="btn btn-primary" href="task_create.php">+ Жаңа жаттығу</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><span><?= (int)$stats['total'] ?></span>Барлығы</div>
    <div class="stat green"><span><?= (int)$stats['done'] ?></span>Орындалды</div>
    <div class="stat orange"><span><?= (int)($stats['total'] - $stats['done']) ?></span>Күтуде</div>
    <div class="stat red"><span><?= (int)$stats['overdue'] ?></span>Мерзімі өткен</div>
</div>
<div class="progress" title="<?= $percent ?>%"><div class="bar" style="width: <?= $percent ?>%"></div></div>
<p class="muted small">Жалпы прогресс: <?= $percent ?>% · ⏱ Оқылған: <b><?= intdiv((int)$stats['mins_done'], 60) ?> сағ <?= (int)$stats['mins_done'] % 60 ?> мин</b> · Қалған жоспар: <b><?= intdiv((int)$stats['mins_left'], 60) ?> сағ <?= (int)$stats['mins_left'] % 60 ?> мин</b></p>

<form class="filters" method="get">
    <input type="text" name="q" placeholder="🔎 Жаттығуды іздеу..." value="<?= e($q) ?>">
    <select name="category">
        <option value="">Барлық санат</option>
        <?php foreach ($categories as $ct): ?>
            <option value="<?= (int)$ct['id'] ?>" <?= (string)$ct['id'] === $cat ? 'selected' : '' ?>><?= e($ct['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">Барлық мәртебе</option>
        <option value="todo" <?= $status === 'todo' ? 'selected' : '' ?>>Күтуде</option>
        <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Орындалды</option>
    </select>
    <select name="priority">
        <option value="">Барлық басымдық</option>
        <?php foreach (['high', 'medium', 'low'] as $p): ?>
            <option value="<?= $p ?>" <?= $prio === $p ? 'selected' : '' ?>><?= e(priority_label($p)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort">
        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Жаңалары алдымен</option>
        <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>Ескілері алдымен</option>
        <option value="due" <?= $sort === 'due' ? 'selected' : '' ?>>Мерзімі бойынша</option>
        <option value="prio" <?= $sort === 'prio' ? 'selected' : '' ?>>Басымдық бойынша</option>
        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Аты бойынша (А-Я)</option>
    </select>
    <button class="btn btn-primary" type="submit">Қолдану</button>
    <a class="btn btn-outline" href="dashboard.php">Тазалау</a>
</form>

<?php if (!$tasks): ?>
    <div class="empty">
        <div class="empty-icon">📭</div>
        <h3>Жаттығу табылмады</h3>
        <p>Жаңа жаттығу қосыңыз, дайын жоспарды қолданыңыз немесе сүзгіні өзгертіңіз.</p>
    </div>
<?php else: ?>
    <div class="task-list">
    <?php foreach ($tasks as $t):
        $overdue = $t['status'] === 'todo' && $t['due_date'] && $t['due_date'] < date('Y-m-d'); ?>
        <div class="task <?= $t['status'] === 'done' ? 'is-done' : '' ?>">
            <form method="post" action="task_toggle.php" class="toggle-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button class="check-btn" type="submit" title="Мәртебені өзгерту"><?= $t['status'] === 'done' ? '✓' : '' ?></button>
            </form>
            <div class="task-body">
                <h3><?= e($t['title']) ?></h3>
                <?php if ($t['description'] !== null && $t['description'] !== ''): ?>
                    <p><?= nl2br(e($t['description'])) ?></p>
                <?php endif; ?>
                <div class="meta">
                    <?php if ($t['cat_name']): ?>
                        <span class="tag" style="background: <?= e($t['cat_color']) ?>"><?= e($t['cat_name']) ?></span>
                    <?php endif; ?>
                    <span class="prio prio-<?= e($t['priority']) ?>"><?= e(priority_label($t['priority'])) ?></span>
                    <?php if ($t['duration_min']): ?><span class="dur">⏱ <?= (int)$t['duration_min'] ?> мин</span><?php endif; ?>
                    <?php if ($t['due_date']): ?>
                        <span class="due <?= $overdue ? 'late' : '' ?>">📅 <?= e(date('d.m.Y', strtotime($t['due_date']))) ?><?= $overdue ? ' (өтіп кетті)' : '' ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="task-actions">
                <a class="btn btn-outline btn-sm" href="task_edit.php?id=<?= (int)$t['id'] ?>">✏️ Өңдеу</a>
                <form method="post" action="task_delete.php" class="delete-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button class="btn btn-danger btn-sm" type="submit">🗑 Жою</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1)) ?>">‹</a><?php endif; ?>
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(page_url($i)) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1)) ?>">›</a><?php endif; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
