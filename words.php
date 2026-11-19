<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$uid = $_SESSION['user_id'];

/* ---------- POST әрекеттері ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $tab = in_array($_POST['tab'] ?? '', ['learning', 'learned'], true) ? $_POST['tab'] : 'learning';
    $ajax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

    if ($action === 'add' || $action === 'update') {
        $word  = trim($_POST['word'] ?? '');
        $trans = trim($_POST['translation'] ?? '');
        $ex    = trim($_POST['example'] ?? '');
        $id    = (int)($_POST['id'] ?? 0);
        if ($word === '' || mb_strlen($word) > 100) {
            flash('error', 'Сөзді енгізіңіз (100 символға дейін).');
        } elseif ($trans === '' || mb_strlen($trans) > 150) {
            flash('error', 'Аудармасын енгізіңіз (150 символға дейін).');
        } elseif (mb_strlen($ex) > 255) {
            flash('error', 'Мысал сөйлем 255 символдан аспауы тиіс.');
        } else {
            try {
                if ($action === 'add') {
                    $s = db()->prepare('INSERT INTO words (user_id, word, translation, example) VALUES (?, ?, ?, ?)');
                    $s->execute([$uid, $word, $trans, $ex === '' ? null : $ex]);
                    flash('success', 'Сөз қосылды: ' . $word);
                } else {
                    $s = db()->prepare('UPDATE words SET word = ?, translation = ?, example = ? WHERE id = ? AND user_id = ?');
                    $s->execute([$word, $trans, $ex === '' ? null : $ex, $id, $uid]);
                    flash('success', 'Сөз жаңартылды.');
                }
            } catch (PDOException $e) {
                flash('error', 'Бұл сөз сөздікте бар.');
            }
        }
    } elseif ($action === 'toggle') {
        $s = db()->prepare("UPDATE words SET status = IF(status = 'learned', 'learning', 'learned') WHERE id = ? AND user_id = ?");
        $s->execute([(int)($_POST['id'] ?? 0), $uid]);
        if ($ajax) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); exit; }
    } elseif ($action === 'delete') {
        $s = db()->prepare('DELETE FROM words WHERE id = ? AND user_id = ?');
        $s->execute([(int)($_POST['id'] ?? 0), $uid]);
        flash('success', 'Сөз жойылды.');
    } elseif ($action === 'template') {
        $list = [
            ['analyse','талдау'],['approach','тәсіл, көзқарас'],['assess','бағалау'],['benefit','пайда'],
            ['concept','ұғым'],['consequence','салдар'],['contribute','үлес қосу'],['criteria','өлшемдер'],
            ['data','деректер'],['define','анықтау'],['derive','шығару, алу'],['environment','қоршаған орта'],
            ['establish','құру, орнату'],['evidence','дәлел'],['factor','фактор'],['function','қызмет'],
            ['identify','айқындау'],['impact','әсер'],['indicate','көрсету'],['interpret','түсіндіру'],
            ['issue','мәселе'],['method','әдіс'],['policy','саясат'],['process','үдеріс'],
            ['require','талап ету'],['research','зерттеу'],['significant','маңызды'],['sufficient','жеткілікті'],
            ['sustain','қолдау, сақтау'],['trend','үрдіс'],
        ];
        $s = db()->prepare('INSERT IGNORE INTO words (user_id, word, translation) VALUES (?, ?, ?)');
        $n = 0;
        foreach ($list as [$w, $t]) { $s->execute([$uid, $w, $t]); $n += $s->rowCount(); }
        flash($n ? 'success' : 'error', $n ? "IELTS Academic сөздерінен $n сөз қосылды!" : 'Бұл сөздер бұрыннан қосылған.');
    }
    redirect('words.php?tab=' . $tab);
}

/* ---------- GET: оқу ---------- */
$tab  = in_array($_GET['tab'] ?? '', ['learning', 'learned'], true) ? $_GET['tab'] : 'learning';
$mode = $_GET['mode'] ?? '';
$q    = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 12;

$st = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='learned'),0) learned,
                     COALESCE(SUM(status='learning'),0) learning,
                     COALESCE(SUM(DATE(created_at) = CURDATE()),0) today
                     FROM words WHERE user_id = ?");
$st->execute([$uid]);
$stats = $st->fetch();

$where = 'user_id = ? AND status = ?';
$params = [$uid, $tab];
if ($q !== '') {
    $where .= ' AND (word LIKE ? OR translation LIKE ?)';
    array_push($params, "%$q%", "%$q%");
}
$c = db()->prepare("SELECT COUNT(*) FROM words WHERE $where");
$c->execute($params);
$total = (int)$c->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$off = ($page - 1) * $per;
$l = db()->prepare("SELECT * FROM words WHERE $where ORDER BY created_at DESC, id DESC LIMIT $per OFFSET $off");
$l->execute($params);
$words = $l->fetchAll();

$cards = [];
if ($mode === 'learn') {
    $f = db()->prepare("SELECT id, word, translation, example FROM words WHERE user_id = ? AND status = 'learning' ORDER BY RAND() LIMIT 30");
    $f->execute([$uid]);
    $cards = $f->fetchAll();
}

function wurl(array $over): string {
    return 'words.php?' . http_build_query(array_merge($_GET, $over));
}

$page_title = 'Сөздік';
$active = 'words';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <h2 class="panel-title">Менің сөздерім</h2>

    <div class="stats">
        <div class="stat green"><span><?= (int)$stats['learned'] ?></span>Үйренілді</div>
        <div class="stat orange"><span><?= (int)$stats['learning'] ?></span>Үйренуде</div>
        <div class="stat"><span><?= (int)$stats['total'] ?></span>Барлығы</div>
        <div class="stat red"><span><?= (int)$stats['today'] ?></span>Бүгін қосылған</div>
    </div>

<?php if ($mode === 'learn'): ?>
    <?php if (!$cards): ?>
        <div class="empty"><div class="empty-icon">🎉</div><h3>Үйренетін сөз қалмады!</h3>
            <p>Жаңа сөз қосыңыз немесе дайын IELTS сөздерін қосыңыз.</p>
            <a class="btn btn-primary" href="words.php">Сөздікке қайту</a></div>
    <?php else: ?>
        <div class="flash" id="flash" data-csrf="<?= e(csrf_token()) ?>">
            <div class="fc-top"><span id="fc-count"></span><a href="words.php">✕ Жабу</a></div>
            <div class="fc-card" id="fc-card">
                <button type="button" class="speak" id="fc-speak" title="Тыңдау">🔊</button>
                <div class="fc-word" id="fc-word"></div>
                <div class="fc-trans" id="fc-trans"></div>
                <div class="fc-ex" id="fc-ex"></div>
            </div>
            <div class="fc-actions">
                <button type="button" class="btn btn-outline" id="fc-flip">Аудармасын көрсету</button>
                <button type="button" class="btn btn-danger" id="fc-again">Әлі білмеймін</button>
                <button type="button" class="btn btn-primary" id="fc-know">Білемін ✓</button>
            </div>
            <div class="fc-done" id="fc-done" hidden>
                <div class="empty-icon">🎉</div><h3>Тамаша! Сессия аяқталды.</h3>
                <a class="btn btn-primary" href="words.php">Сөздікке қайту</a>
            </div>
        </div>
        <script type="application/json" id="flash-data"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <?php endif; ?>
<?php else: ?>

    <div class="tabs">
        <a class="<?= $tab === 'learning' ? 'active' : '' ?>" href="words.php?tab=learning">Үйренуде</a>
        <a class="<?= $tab === 'learned' ? 'active' : '' ?>" href="words.php?tab=learned">Үйренілді</a>
    </div>

    <div class="word-add">
        <a class="btn btn-primary" href="words.php?mode=learn">🧠 Сөздерді жаттау</a>
        <form method="post" class="add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="text" name="word" placeholder="English word" maxlength="100" required>
            <input type="text" name="translation" placeholder="Аудармасы" maxlength="150" required>
            <input type="text" name="example" placeholder="Мысал сөйлем (міндетті емес)" maxlength="255" class="wide">
            <button class="btn btn-primary" type="submit">➕ Қосу</button>
        </form>
    </div>

    <div class="word-tools">
        <form method="get" class="search-form">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <input type="text" name="q" placeholder="🔎 Сөзді іздеу..." value="<?= e($q) ?>">
            <button class="btn btn-outline btn-sm" type="submit">Іздеу</button>
        </form>
        <form method="post" data-confirm="30 IELTS Academic сөзін қосайын ба?">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="template">
            <input type="hidden" name="tab" value="<?= e($tab) ?>">
            <button class="btn btn-outline btn-sm" type="submit">📚 Дайын IELTS сөздері (30)</button>
        </form>
    </div>

    <p class="muted small">Барлығы: <?= $total ?> сөз</p>

    <?php if (!$words): ?>
        <div class="empty"><div class="empty-icon">📖</div><h3>Сөз табылмады</h3>
            <p>Жоғарыдан жаңа сөз қосыңыз немесе дайын IELTS сөздерін қосыңыз.</p></div>
    <?php else: ?>
        <div class="word-list">
        <?php foreach ($words as $w): ?>
            <div class="word">
                <button type="button" class="speak" data-word="<?= e($w['word']) ?>" title="Тыңдау">🔊</button>
                <div class="w-main">
                    <b><?= e($w['word']) ?></b>
                    <span><?= e($w['translation']) ?></span>
                    <?php if ($w['example']): ?><em><?= e($w['example']) ?></em><?php endif; ?>
                </div>
                <form method="post" class="w-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                    <button class="btn btn-outline btn-sm" type="submit" title="Мәртебесін өзгерту"><?= $tab === 'learning' ? '✓ Үйрендім' : '↩ Қайта' ?></button>
                </form>
                <details class="w-edit">
                    <summary title="Өңдеу">✏️</summary>
                    <form method="post" class="edit-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                        <input type="hidden" name="tab" value="<?= e($tab) ?>">
                        <input type="text" name="word" value="<?= e($w['word']) ?>" maxlength="100" required>
                        <input type="text" name="translation" value="<?= e($w['translation']) ?>" maxlength="150" required>
                        <input type="text" name="example" value="<?= e($w['example'] ?? '') ?>" maxlength="255" placeholder="Мысал">
                        <button class="btn btn-primary btn-sm" type="submit">Сақтау</button>
                    </form>
                </details>
                <form method="post" class="delete-form w-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                    <input type="hidden" name="tab" value="<?= e($tab) ?>">
                    <button class="btn btn-danger btn-sm" type="submit" title="Жою">🗑</button>
                </form>
            </div>
        <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?><a href="<?= e(wurl(['page' => $page - 1])) ?>">‹</a><?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(wurl(['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="<?= e(wurl(['page' => $page + 1])) ?>">›</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
