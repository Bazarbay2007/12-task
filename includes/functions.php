<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/** XSS-тен қорғау */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Жабық беттерді қорғау */
function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Алдымен жүйеге кіріңіз.');
        redirect('login.php');
    }
}

function guest_only(): void
{
    if (is_logged_in()) {
        redirect('dashboard.php');
    }
}

/* ---------- Flash хабарламалар ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ---------- CSRF қорғанысы ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF қатесі: сұраныс жарамсыз.');
    }
}

/* ---------- Аватар ---------- */
function avatar_html(?string $avatar, string $name, string $cls = 'avatar'): string
{
    if ($avatar) {
        return '<img class="' . $cls . '" src="uploads/avatars/' . e($avatar) . '" alt="">';
    }
    return '<span class="' . $cls . ' avatar-ph">' . e(mb_strtoupper(mb_substr($name, 0, 1))) . '</span>';
}

/* ---------- Көмекші функциялар ---------- */
function get_categories(): array
{
    return db()->query('SELECT id, name, color FROM categories ORDER BY id')->fetchAll();
}

function priority_label(string $p): string
{
    return ['low' => 'Төмен', 'medium' => 'Орташа', 'high' => 'Жоғары'][$p] ?? $p;
}

/** Тапсырма деректерін тексеру (валидация). Қателер массивін қайтарады */
function validate_task(array $in, array &$clean): array
{
    $errors = [];
    $title = trim($in['title'] ?? '');
    $desc  = trim($in['description'] ?? '');
    $prio  = $in['priority'] ?? 'medium';
    $cat   = $in['category_id'] ?? '';
    $due   = trim($in['due_date'] ?? '');
    $dur   = trim($in['duration_min'] ?? '');

    if ($title === '') {
        $errors['title'] = 'Тақырыпты енгізіңіз.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 120) {
        $errors['title'] = 'Тақырып 3-тен 120 символға дейін болуы тиіс.';
    }
    if (mb_strlen($desc) > 1000) {
        $errors['description'] = 'Сипаттама 1000 символдан аспауы тиіс.';
    }
    if (!in_array($prio, ['low', 'medium', 'high'], true)) {
        $errors['priority'] = 'Басымдық дұрыс емес.';
    }
    $catId = null;
    if ($cat !== '') {
        $s = db()->prepare('SELECT id FROM categories WHERE id = ?');
        $s->execute([(int)$cat]);
        if ($s->fetch()) {
            $catId = (int)$cat;
        } else {
            $errors['category_id'] = 'Санат табылмады.';
        }
    }
    if ($due !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $due);
        if (!$d || $d->format('Y-m-d') !== $due) {
            $errors['due_date'] = 'Күн пішімі дұрыс емес.';
        }
    }
    $durVal = null;
    if ($dur !== '') {
        if (!ctype_digit($dur) || (int)$dur < 1 || (int)$dur > 600) {
            $errors['duration_min'] = 'Уақыт 1-ден 600 минутқа дейін болуы тиіс.';
        } else {
            $durVal = (int)$dur;
        }
    }
    $clean = [
        'title' => $title, 'description' => $desc, 'priority' => $prio,
        'category_id' => $catId, 'due_date' => $due === '' ? null : $due, 'duration_min' => $durVal,
    ];
    return $errors;
}
