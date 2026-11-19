<?php
require_once __DIR__ . '/includes/functions.php';
guest_only();

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Email мен құпиясөзді енгізіңіз.';
    } else {
        $s = db()->prepare('SELECT id, name, password, avatar FROM users WHERE email = ?');
        $s->execute([$email]);
        $user = $s->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);   // session fixation-нан қорғау
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_avatar'] = $user['avatar'];
            flash('success', 'Қош келдіңіз, ' . $user['name'] . '!');
            redirect('dashboard.php');
        }
        $error = 'Email немесе құпиясөз қате.';
    }
}

$page_title = 'Кіру';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h2>Жүйеге кіру</h2>
    <p class="muted">Аккаунтыңызға кіріңіз</p>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="field">
            <label for="password">Құпиясөз</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" required>
                <button type="button" class="pw-toggle" data-target="password">👁</button>
            </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Кіру</button>
    </form>
    <p class="switch">Аккаунт жоқ па? <a href="register.php">Тіркелу</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
