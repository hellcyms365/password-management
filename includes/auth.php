<?php
/**
 * 会话与登录校验
 */
require_once __DIR__ . '/db.php';

/** 启动会话（带 cookie 加固） */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** 当前登录的管理器（数组）或 null */
function current_admin(): ?array
{
    start_session();
    return $_SESSION['admin'] ?? null;
}

/** 当前登录的管理器 id，未登录返回 null */
function current_admin_id(): ?int
{
    $a = current_admin();
    return $a ? (int)$a['id'] : null;
}

/** 当前是否超级管理员 */
function is_super(): bool
{
    $a = current_admin();
    return $a && ($a['role'] === 'super');
}

/** 未登录则跳转登录页 */
function require_login(): void
{
    start_session();
    if (empty($_SESSION['admin'])) {
        redirect('login.php');
    }
}

/** 仅超级管理员可访问，否则 403 */
function require_super(): void
{
    require_login();
    if (!is_super()) {
        http_response_code(403);
        exit('没有权限访问该页面。');
    }
}

/** 登录后写入会话 */
function set_session(array $admin): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['admin'] = [
        'id'       => (int)$admin['id'],
        'username' => $admin['username'],
        'role'     => $admin['role'],
    ];
}

/** 清除会话 */
function destroy_session(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
