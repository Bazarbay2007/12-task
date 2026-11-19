<?php
require_once __DIR__ . '/includes/functions.php';
guest_only();

$errors = [];
$old = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $email = mb_strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    $old = ['name' => $name, 'email' => $email];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 50) {
        $errors['name'] = 'Аты-жөні 2-50 символ болуы тиіс.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors['email'] = 'Дұрыс email енгізіңіз.';
    }
    if (strlen($pass) < 6) {
        $errors['password'] = 'Құпиясөз кемінде 6 символ болуы тиіс.';
    }
    if ($pass !== $pass2) {
        $errors['password2'] = 'Құпиясөздер сәйкес келмейді.';
    }
    if (!$errors) {
        $s = db()->prepare('SELECT id FROM users WHERE email = ?');
        $s->execute([$email]);
        if ($s->fetch()) {
            $errors['email'] = 'Бұл email тіркелген.';
        }
    }
    if (!$errors) {
        $ins = db()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        flash('success', 'Тіркелу сәтті! Енді жүйеге кіріңіз.');
        redirect('login.php');
    }
}

$page_title = 'Тіркелу';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-card">
    <h2>Тіркелу</h2>
    <p class="muted">Жаңа аккаунт құрыңыз</p>
    <form method="post" novalidate>
        <?= csrf_field() ?>
        <div class="field <?= isset($errors['name']) ? 'has-error' : '' ?>">
            <label for="name">Аты-жөні</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" maxlength="50" required>
            <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['email']) ? 'has-error' : '' ?>">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" required>
            <?php if (isset($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['password']) ? 'has-error' : '' ?>">
            <label for="password">Құпиясөз</label>
            <div class="pw-wrap">
                <input type="password" id="password" name="password" required>
                <button type="button" class="pw-toggle" data-target="password">👁</button>
            </div>
            <?php if (isset($errors['password'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($errors['password2']) ? 'has-error' : '' ?>">
            <label for="password2">Құпиясөзді қайталаңыз</label>
            <input type="password" id="password2" name="password2" required>
            <?php if (isset($errors['password2'])): ?><small class="error"><?= e($errors['password2']) ?></small><?php endif; ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Тіркелу</button>
    </form>
    <p class="switch">Аккаунт бар ма? <a href="login.php">Кіру</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
