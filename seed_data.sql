-- ============================================================
-- 密码管理器 · 数据导入脚本（纯数据，不含建表语句）
-- ============================================================
-- 适用场景：数据库表结构已经创建好，只需补数据时使用
-- （例如：已执行过 database.sql / database_no_create.sql，或已在 phpMyAdmin 建好表）
--
-- 用法：
--   1) phpMyAdmin：左侧选中「mima」数据库 → 顶部「导入」→ 选择本文件 → 执行
--   2) 或命令行：  mysql -u mima -p mima < seed_data.sql
--
-- 安全说明：全部使用 INSERT IGNORE，可重复执行；
--   已存在的记录会自动跳过，不会覆盖已有数据、不会报错。
--   若 admins 表已存在 admin 账号，将保持不动（不重置密码）。
-- ============================================================

-- 1) 默认超级管理员（用户名：admin / 密码：admin123）
--    bcrypt 哈希与 PHP password_verify 兼容；首次登录后请尽快修改密码。
INSERT IGNORE INTO `admins` (`id`, `username`, `password_hash`, `role`, `created_at`)
VALUES (1, 'admin', '$2y$10$W2Kmpih055pBshN5a.c63uxjjw4Vkcfda4MRGTE7NPOJbwm9zcz5G', 'super', NOW());

-- 2) 系统设置默认项（缺失的键会补上，已有键保持原值）
INSERT IGNORE INTO `settings` (`skey`, `svalue`) VALUES
  ('theme_mode',      'light'),
  ('theme_color',     '#2563eb'),
  ('page_bg_color',   '#f4f6f9'),
  ('page_bg_image',   ''),
  ('login_bg_color',  '#f4f6f9'),
  ('login_bg_image',  ''),
  ('login_title',     '密码管理器'),
  ('login_subtitle',  '登录后管理你的密码库'),
  ('login_logo',      ''),
  ('nav_opacity',     '90'),
  ('card_opacity',    '90');
