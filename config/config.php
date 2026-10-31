<?php
/**
 * 全局配置
 * 部署时请按实际环境修改数据库连接与加密密钥。
 */

// ---------- 数据库配置 ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'mima');
define('DB_USER', 'mima');
// 请填入 mima 账户在 phpMyAdmin 登录时用的实际密码
define('DB_PASS', '');

// ---------- 应用配置 ----------
define('APP_NAME', '密码管理器');
// 若部署在网站子目录下（例如 http://localhost/password-manager），
// 请将 BASE_URL 改为 '/password-manager'；根目录部署则留空 ''。
define('BASE_URL', '');

// 密码库图片存放的物理目录与访问前缀
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', (BASE_URL ?: '') . '/assets/uploads/');

// ---------- 密码加密密钥 ----------
// 用于加密密码库内账号密码（AES-256-CBC）。请务必改成自己的随机字符串，
// 部署后不要泄露。修改此值会导致已存密码无法解密。
define('ENCRYPTION_KEY', '0kYZ2Vb/PBFbqymq8UfifKoiPfNCkS5+');

// ---------- 会话 ----------
define('SESSION_NAME', 'pm_session');
