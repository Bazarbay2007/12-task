<?php
$page_title = 'Басты бет';
$active = 'home';
require __DIR__ . '/includes/header.php';

$stats = null;
if (is_logged_in()) {
    $s = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='done'),0) done FROM tasks WHERE user_id = ?");
    $s->execute([$_SESSION['user_id']]);
    $stats = $s->fetch();
}
?>
<section class="hero">
    <div class="hero-text">
        <span class="badge">Қазақ ұлттық қыздар педагогикалық университетінің студенттеріне</span>
        <h1>Оқуыңды да, IELTS-ті де <span class="grad">жоспармен</span> жеңіңіз</h1>
        <p>Жаттығуларды жоспарлаңыз, жаңа ағылшын сөздерін сөздікке жинап жаттаңыз, прогресіңізді бақылаңыз.</p>
        <div class="hero-actions">
            <?php if (is_logged_in()): ?>
                <a class="btn btn-primary" href="dashboard.php">Жоспарыма өту</a>
                <a class="btn btn-outline" href="task_create.php">+ Жаңа жаттығу</a>
            <?php else: ?>
                <a class="btn btn-primary" href="register.php">Жоспар құруды бастау</a>
                <a class="btn btn-outline" href="login.php">Кіру</a>
            <?php endif; ?>
        </div>
        <?php if ($stats): ?>
            <p class="hint">Жоспарыңызда <b><?= (int)$stats['total'] ?></b> жаттығу, оның <b><?= (int)$stats['done'] ?></b> орындалды.</p>
        <?php endif; ?>
    </div>
    <div class="hero-card">
        <div class="mock-task done"><span class="check">✓</span> Listening Section 1–2</div>
        <div class="mock-task done"><span class="check">✓</span> 30 Academic words</div>
        <div class="mock-task"><span class="check"></span> Writing Task 2 essay</div>
        <div class="mock-task"><span class="check"></span> Full Mock Test</div>
    </div>
</section>

<section class="features">
    <div class="feature"><div class="f-icon">🎧</div><h3>4 бөлім + Mock Test</h3><p>Listening, Reading, Writing, Speaking, Grammar, Vocabulary санаттары.</p></div>
    <div class="feature"><div class="f-icon">📖</div><h3>Жеке сөздік</h3><p>Сөз қосу, жаттау карточкалары және 🔊 айтылымы.</p></div>
    <div class="feature"><div class="f-icon">📋</div><h3>Дайын 4 апталық жоспар</h3><p>Бір басумен 12 жаттығу қосылады.</p></div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
