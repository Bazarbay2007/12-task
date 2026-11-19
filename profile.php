<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$uid = $_SESSION['user_id'];

$s = db()->prepare('SELECT * FROM users WHERE id = ?');
$s->execute([$uid]);
$user = $s->fetch();
if (!$user) { redirect('logout.php'); }

$errors = [];
$pwErrors = [];
$form = [
    'name' => $user['name'], 'phone' => $user['phone'] ?? '',
    'faculty' => $user['faculty'] ?? '', 'course' => $user['course'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['form'] ?? '') === 'profile') {
        $form['name']    = trim($_POST['name'] ?? '');
        $form['phone']   = trim($_POST['phone'] ?? '');
        $form['faculty'] = trim($_POST['faculty'] ?? '');
        $form['course']  = $_POST['course'] ?? '';

        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 50) {
            $errors['name'] = 'Аты-жөні 2-50 символ болуы тиіс.';
        }
        if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{5,20}$/', $form['phone'])) {
            $errors['phone'] = 'Телефон нөмірі дұрыс емес.';
        }
        if (mb_strlen($form['faculty']) > 100) {
            $errors['faculty'] = 'Ең көбі 100 символ.';
        }
        $course = null;
        if ($form['course'] !== '') {
            if (ctype_digit((string)$form['course']) && (int)$form['course'] >= 1 && (int)$form['course'] <= 6) {
                $course = (int)$form['course'];
            } else {
                $errors['course'] = 'Курс 1-6 аралығында болуы тиіс.';
            }
        }

        /* ---- Фото жүктеу (валидация) ---- */
        $newAvatar = null;
        $f = $_FILES['avatar'] ?? null;
        if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors['avatar'] = 'Файлды жүктеу кезінде қате шықты.';
            } elseif ($f['size'] > 2 * 1024 * 1024) {
                $errors['avatar'] = 'Фото 2 МБ-тан аспауы тиіс.';
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
                $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
                if (!$ext || @getimagesize($f['tmp_name']) === false) {
                    $errors['avatar'] = 'Тек JPG, PNG немесе WEBP фото жүктеңіз.';
                } else {
                    $dir = __DIR__ . '/uploads/avatars';
                    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
                    // Қауіпсіздік: аватар қалтасында PHP орындалмайды
                    if (!file_exists($dir . '/.htaccess')) {
                        file_put_contents($dir . '/.htaccess', "php_flag engine off\nRemoveHandler .php .phtml\nOptions -ExecCGI\n");
                    }
                    $newAvatar = 'u' . $uid . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $newAvatar)) {
                        $errors['avatar'] = 'Фотоны сақтау мүмкін болмады.';
                        $newAvatar = null;
                    }
                }
            }
        }
        $removeAvatar = isset($_POST['remove_avatar']);

        if (!$errors) {
            $avatar = $user['avatar'];
            if ($newAvatar || $removeAvatar) {
                if ($avatar && is_file(__DIR__ . '/uploads/avatars/' . $avatar)) {
                    @unlink(__DIR__ . '/uploads/avatars/' . $avatar);
                }
                $avatar = $newAvatar;   // жаңа фото немесе null (өшіру)
            }
            $u = db()->prepare('UPDATE users SET name = ?, phone = ?, faculty = ?, course = ?, avatar = ? WHERE id = ?');
            $u->execute([$form['name'], $form['phone'] ?: null, $form['faculty'] ?: null, $course, $avatar, $uid]);
            $_SESSION['user_name'] = $form['name'];
            $_SESSION['user_avatar'] = $avatar;
            flash('success', 'Профиль сәтті жаңартылды!');
            redirect('profile.php');
        }
    }

    if (($_POST['form'] ?? '') === 'password') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $new2 = $_POST['new2'] ?? '';
        if (!password_verify($cur, $user['password'])) {
            $pwErrors['current'] = 'Қазіргі құпиясөз қате.';
        }
        if (strlen($new) < 6) {
            $pwErrors['new'] = 'Жаңа құпиясөз кемінде 6 символ болуы тиіс.';
        }
        if ($new !== $new2) {
            $pwErrors['new2'] = 'Құпиясөздер сәйкес келмейді.';
        }
        if (!$pwErrors) {
            $u = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
            $u->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Құпиясөз өзгертілді!');
            redirect('profile.php');
        }
    }
}

// Қысқаша статистика
$t = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='done'),0) done FROM tasks WHERE user_id = ?");
$t->execute([$uid]); $ts = $t->fetch();
$w = db()->prepare("SELECT COUNT(*) total, COALESCE(SUM(status='learned'),0) learned FROM words WHERE user_id = ?");
$w->execute([$uid]); $ws = $w->fetch();

$page_title = 'Профиль';
$active = 'profile';
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <h2 class="panel-title">Профиль баптаулары</h2>

    <div class="stats">
        <div class="stat"><span><?= (int)$ts['total'] ?></span>Жаттығу</div>
        <div class="stat green"><span><?= (int)$ts['done'] ?></span>Орындалды</div>
        <div class="stat orange"><span><?= (int)$ws['total'] ?></span>Сөз</div>
        <div class="stat red"><span><?= (int)$ws['learned'] ?></span>Үйренілді</div>
    </div>

    <form method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="profile">
        <div class="profile-grid">
            <div class="avatar-box">
                <div id="avatar-preview"><?= avatar_html($user['avatar'], $user['name'], 'avatar avatar-xl') ?></div>
                <label class="btn btn-outline btn-block upload-btn">Фото таңдау
                    <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp" hidden>
                </label>
                <small class="muted">JPG, PNG, WEBP · 2 МБ дейін</small>
                <?php if (isset($errors['avatar'])): ?><small class="error"><?= e($errors['avatar']) ?></small><?php endif; ?>
                <?php if ($user['avatar']): ?>
                    <label class="check-line"><input type="checkbox" name="remove_avatar"> Фотоны өшіру</label>
                <?php endif; ?>
            </div>

            <div class="profile-fields">
                <h4 class="sect">Жеке мәліметтер</h4>
                <div class="row <?= isset($errors['name']) ? 'has-error' : '' ?>">
                    <label for="name">Аты-жөні</label>
                    <div><input type="text" id="name" name="name" maxlength="50" value="<?= e($form['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?></div>
                </div>
                <div class="row">
                    <label>Email</label>
                    <div><input type="email" value="<?= e($user['email']) ?>" disabled></div>
                </div>
                <div class="row <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                    <label for="phone">Телефон</label>
                    <div><input type="text" id="phone" name="phone" placeholder="+7 7XX XXX XX XX" value="<?= e($form['phone']) ?>">
                    <?php if (isset($errors['phone'])): ?><small class="error"><?= e($errors['phone']) ?></small><?php endif; ?></div>
                </div>
                <div class="row <?= isset($errors['faculty']) ? 'has-error' : '' ?>">
                    <label for="faculty">Мамандық / факультет</label>
                    <div><input type="text" id="faculty" name="faculty" maxlength="100" placeholder="мыс: Шетел тілі: екі шетел тілі" value="<?= e($form['faculty']) ?>">
                    <?php if (isset($errors['faculty'])): ?><small class="error"><?= e($errors['faculty']) ?></small><?php endif; ?></div>
                </div>
                <div class="row <?= isset($errors['course']) ? 'has-error' : '' ?>">
                    <label for="course">Курс</label>
                    <div><select id="course" name="course">
                        <option value="">— Таңдалмаған —</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= (string)$form['course'] === (string)$i ? 'selected' : '' ?>><?= $i ?> курс</option>
                        <?php endfor; ?>
                    </select></div>
                </div>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Сақтау</button></div>
            </div>
        </div>
    </form>

    <h4 class="sect" style="margin-top:34px">Құпиясөзді өзгерту</h4>
    <form method="post" class="pw-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">
        <div class="field <?= isset($pwErrors['current']) ? 'has-error' : '' ?>">
            <label for="current">Қазіргі құпиясөз</label>
            <input type="password" id="current" name="current" required>
            <?php if (isset($pwErrors['current'])): ?><small class="error"><?= e($pwErrors['current']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($pwErrors['new']) ? 'has-error' : '' ?>">
            <label for="new">Жаңа құпиясөз</label>
            <input type="password" id="new" name="new" required>
            <?php if (isset($pwErrors['new'])): ?><small class="error"><?= e($pwErrors['new']) ?></small><?php endif; ?>
        </div>
        <div class="field <?= isset($pwErrors['new2']) ? 'has-error' : '' ?>">
            <label for="new2">Жаңа құпиясөзді қайталаңыз</label>
            <input type="password" id="new2" name="new2" required>
            <?php if (isset($pwErrors['new2'])): ?><small class="error"><?= e($pwErrors['new2']) ?></small><?php endif; ?>
        </div>
        <button class="btn btn-outline" type="submit">Өзгерту</button>
    </form>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
