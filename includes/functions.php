<?php
/**
 * 通用辅助函数
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/** 校验当前管理员是否拥有指定密码库，返回密码库或 null */
function find_owned_project(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM projects WHERE id = ? AND admin_id = ? LIMIT 1');
    $stmt->execute([$id, current_admin_id()]);
    return $stmt->fetch() ?: null;
}

/** 校验当前管理员是否拥有指定账号（经密码库归属判断），返回账号或 null */
function find_owned_credential(int $id): ?array
{
    $stmt = db()->prepare('SELECT c.* FROM credentials c
                           JOIN projects p ON p.id = c.project_id
                           WHERE c.id = ? AND p.admin_id = ? LIMIT 1');
    $stmt->execute([$id, current_admin_id()]);
    return $stmt->fetch() ?: null;
}


/** HTML 转义 */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** 生成站内 URL（兼容子目录部署） */
function site_url(string $path): string
{
    $prefix = BASE_URL ? rtrim(BASE_URL, '/') : '';
    return $prefix . '/' . ltrim($path, '/');
}

/** 页面跳转 */
function redirect(string $url): void
{
    $prefix = BASE_URL ? rtrim(BASE_URL, '/') : '';
    header('Location: ' . $prefix . '/' . $url);
    exit;
}

/** 生成 CSRF Token 并保存到会话 */
function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/** 校验 CSRF Token */
function csrf_check(?string $token): bool
{
    start_session();
    return isset($_SESSION['csrf']) && is_string($token)
        && hash_equals($_SESSION['csrf'], $token);
}

/** 设置一次性提示消息 */
function flash(string $type, string $msg): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/** 读取并清除提示消息 */
function flash_get(): ?array
{
    start_session();
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

// ================= 密码加密 =================
// 使用 AES-256-CBC，密钥由 ENCRYPTION_KEY 经 SHA-256 派生，长度固定为 32 字节。
// 存储格式：base64( 随机IV(16字节) . 密文 )

function encryption_key(): string
{
    return hash('sha256', ENCRYPTION_KEY, true);
}

function encrypt_password(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'aes-256-cbc', encryption_key(), 0, $iv);
    if ($cipher === false) {
        return null;
    }
    return base64_encode($iv . $cipher);
}

function decrypt_password(?string $stored): ?string
{
    if ($stored === null || $stored === '') {
        return null;
    }
    $raw = base64_decode($stored, true);
    if ($raw === false || strlen($raw) < 17) {
        return null;
    }
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'aes-256-cbc', encryption_key(), 0, $iv);
    return $plain === false ? null : $plain;
}

// ================= 图片上传 / 删除 =================

/**
 * 保存上传的图片（通用）。返回入库用的相对访问路径；失败返回 null。
 * @param string $prefix 文件名前缀，如 proj / bg / loginbg / logo
 */
function save_uploaded_image(array $file, string $prefix): ?string
{
    // 仅在允许的图片类型中接受
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if (!in_array($file['type'], $allowed, true)) {
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }
    // 用内容检测真实类型
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return null;
    }
    $extMap = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_GIF  => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];
    if (!isset($extMap[$info[2]])) {
        return null;
    }
    $ext = $extMap[$info[2]];

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0777, true)) {
        return null;
    }

    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = UPLOAD_DIR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    // 返回给前端使用的相对 URL
    return 'assets/uploads/' . $name;
}

/** 保存密码库图片 */
function save_project_image(array $file): ?string
{
    return save_uploaded_image($file, 'proj');
}

/** 保存账号图片（前缀 cred_） */
function save_credential_image(array $file): ?string
{
    return save_uploaded_image($file, 'cred');
}

/** 删除账号图片（兼容） */
function delete_credential_image(?string $relPath): void
{
    delete_stored_file($relPath);
}

/** 账号图片自动迁移：credentials 表缺 image 列时自动补充（旧库无需手动改表） */
function ensure_credential_image_column(): void
{
    try {
        $cols = db()->query("SHOW COLUMNS FROM credentials LIKE 'image'")->fetchAll();
        if (!$cols) {
            db()->exec('ALTER TABLE `credentials` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL');
        }
    } catch (Throwable $e) {
    }
}

/** 删除受控上传目录内的文件（防路径穿越） */
function delete_stored_file(?string $relPath): void
{
    if (!$relPath) {
        return;
    }
    $base = realpath(dirname(UPLOAD_DIR));
    $full = realpath(__DIR__ . '/../' . $relPath);
    if ($full !== false && strpos($full, $base . DIRECTORY_SEPARATOR) === 0) {
        @unlink($full);
    }
}

/** 删除密码库图片（兼容旧调用） */
function delete_project_image(?string $relPath): void
{
    delete_stored_file($relPath);
}

// ================= 字段处理 =================
/** 收集并清洗 POST 提交的可修改字段（field_name 非空才保留） */
function collect_custom_fields(array $names, array $values): array
{
    $fields = [];
    $count = min(count($names), count($values));
    for ($i = 0; $i < $count; $i++) {
        $n = trim((string)$names[$i]);
        $v = trim((string)$values[$i]);
        if ($n === '') {
            continue;
        }
        $fields[] = ['name' => mb_substr($n, 0, 100), 'value' => mb_substr($v, 0, 5000)];
    }
    return $fields;
}
