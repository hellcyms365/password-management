<?php
/**
 * 系统设置：读取 / 保存 / 主题渲染
 */
require_once __DIR__ . '/functions.php';

/** 确保设置表存在并写入默认项（自动建表，旧库无需手动导入脚本） */
function ensure_settings_table(): void
{
    db()->exec("CREATE TABLE IF NOT EXISTS `settings` (
        `skey`   VARCHAR(64) NOT NULL,
        `svalue` TEXT,
        PRIMARY KEY (`skey`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    db()->exec("INSERT IGNORE INTO `settings` (`skey`, `svalue`) VALUES
        ('theme_mode','light'),('theme_color','#2563eb'),('page_bg_color','#f4f6f9'),
        ('page_bg_image',''),('login_bg_color','#f4f6f9'),('login_bg_image',''),
        ('login_title','密码管理器'),('login_subtitle','登录后管理你的密码库'),('login_logo',''),
        ('nav_opacity','90'),('card_opacity','90')");
}

/** 读取全部设置（key => value）。设置表缺失时自动建表并降级为默认值。 */
function settings(): array
{
    static $s = null;
    if ($s !== null) {
        return $s;
    }
    $s = [];
    try {
        foreach (db()->query('SELECT skey, svalue FROM settings')->fetchAll() as $row) {
            $s[$row['skey']] = (string)$row['svalue'];
        }
    } catch (Throwable $e) {
        // 设置表尚未创建：先自动建表，再重读
        try {
            ensure_settings_table();
            foreach (db()->query('SELECT skey, svalue FROM settings')->fetchAll() as $row) {
                $s[$row['skey']] = (string)$row['svalue'];
            }
        } catch (Throwable $e2) {
            $s = [];
        }
    }
    return $s;
}

/** 读取单个设置，带默认值 */
function setting(string $key, $default = null)
{
    $s = settings();
    return (isset($s[$key]) && $s[$key] !== '') ? $s[$key] : $default;
}

/** 当前主题模式：light / dark */
function theme_mode(): string
{
    return setting('theme_mode', 'light') === 'dark' ? 'dark' : 'light';
}

/** 写入单个设置（设置表缺失时自动建表后重试） */
function save_setting(string $key, ?string $value): void
{
    $value = $value ?? '';
    $sql = 'INSERT INTO settings (skey, svalue) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE svalue = ?';
    try {
        db()->prepare($sql)->execute([$key, $value, $value]);
    } catch (Throwable $e) {
        ensure_settings_table();
        db()->prepare($sql)->execute([$key, $value, $value]);
    }
}

// ================= 主题渲染 =================

/** 校验是否为合法 3 位或 6 位十六进制颜色 */
function is_hex_color(string $c): bool
{
    return (bool)preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $c);
}

/** 加深/减淡颜色：$amt 1=原色，<1 变深，>1 变浅 */
function shade_hex(string $hex, float $amt): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, (int)round($r * $amt)));
    $g = max(0, min(255, (int)round($g * $amt)));
    $b = max(0, min(255, (int)round($b * $amt)));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

/** 将 hex 颜色转换为 [r, g, b] */
function hex_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

/**
 * 生成应用到整站的 <style> 内容（主题变量 + 页面背景）。
 * 深色模式下覆盖浅色默认值。
 */
function theme_css(): string
{
    $s     = settings();
    $mode  = ($s['theme_mode'] ?? 'light') === 'dark' ? 'dark' : 'light';
    $color = is_hex_color($s['theme_color'] ?? '') ? $s['theme_color'] : '#2563eb';
    $colorDark = shade_hex($color, 0.85);

    if ($mode === 'dark') {
        $bg     = '#111827';
        $card   = '#1f2937';
        $text   = '#f9fafb';
        $muted  = '#9ca3af';
        $border = '#374151';
    } else {
        $bg     = is_hex_color($s['page_bg_color'] ?? '') ? $s['page_bg_color'] : '#f4f6f9';
        $card   = '#ffffff';
        $text   = '#1f2937';
        $muted  = '#6b7280';
        $border = '#e5e7eb';
    }

    $bgImage = $s['page_bg_image'] ?? '';
    $bodyBg  = 'background-color:' . $bg . ';';
    if ($bgImage !== '') {
        $bodyBg .= "background-image:url('" . e(site_url($bgImage)) . "');"
                 . 'background-size:cover;background-position:center;background-attachment:fixed;';
    }

    list($pr, $pg, $pb) = hex_rgb($color);
    $css = ":root{"
         . "--primary:{$color};"
         . "--primary-dark:{$colorDark};"
         . "--primary-soft:rgba({$pr},{$pg},{$pb},.14);"
         . "--bg:{$bg};--card:{$card};--text:{$text};--muted:{$muted};--border:{$border};"
         . "--theme-mode:{$mode};"
         . "}";
    $css .= 'body{' . $bodyBg . '}';

    // 顶部导航：毛玻璃效果 + 可调透明度（nav_opacity，0-100）
    $navAlpha = (isset($s['nav_opacity']) && $s['nav_opacity'] !== '')
        ? max(0, min(100, (int)$s['nav_opacity'])) / 100
        : 0.90;
    list($nr, $ng, $nb) = hex_rgb($card);
    $css .= ".topbar{background:rgba({$nr},{$ng},{$nb},{$navAlpha});"
          . "-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);}";

    // 账号卡片：毛玻璃效果 + 可调透明度（card_opacity，0-100）
    $cardAlpha = (isset($s['card_opacity']) && $s['card_opacity'] !== '')
        ? max(0, min(100, (int)$s['card_opacity'])) / 100
        : 0.90;
    $css .= ".cred-card{background:rgba({$nr},{$ng},{$nb},{$cardAlpha});"
          . "-webkit-backdrop-filter:blur(8px);backdrop-filter:blur(8px);}";

    if ($mode === 'dark') {
        // 深色模式下微调几处固定色
        $css .= '.cover-placeholder{background:#111827}.project-cover{background:#111827}'
              . '.empty,.card{background:var(--card)}'
              . '.topbar{background:var(--card);border-color:var(--border)}'
              . '.brand,.nav-links a{color:var(--text)}';
    }
    return $css;
}

/** 生成登录页自定义的 <style> 内容（背景图自动拉伸铺满） */
function login_css(): string
{
    $s    = settings();
    $bg   = is_hex_color($s['login_bg_color'] ?? '') ? $s['login_bg_color'] : '#f4f6f9';
    // 背景图自动拉伸铺满：cover 填满 + 居中 + 不重复，登录区占满视口
    $css  = '.auth-wrap{min-height:100vh;background-color:' . $bg
          . ';background-size:cover;background-position:center;background-repeat:no-repeat;}';
    $img  = $s['login_bg_image'] ?? '';
    if ($img !== '') {
        $css .= ".auth-wrap{background-image:url('" . e(site_url($img)) . "');}"
              . '.auth-card{box-shadow:0 8px 30px rgba(0,0,0,.28);}';
    }
    return $css;
}
