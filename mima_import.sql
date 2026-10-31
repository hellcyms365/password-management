-- ============================================================
-- 密码管理器 · 一键导入脚本（建表 + 默认数据）
-- ============================================================
-- 适用：数据库（mima）已存在但尚未建表时，直接导入本文件即可完成
--      全部建表与默认数据，无需分步。
-- 本文件【不包含】CREATE DATABASE，直接在当前选中的数据库中执行，
-- 因此不会触发 #1044 无权建库的报错。
--
-- 用法（phpMyAdmin）：
--   1) 左侧面板选中「mima」数据库（务必先选中库！）
--   2) 顶部「导入」→ 选择本文件 mima_import.sql → 执行
--
-- 安全说明：
--   · 建表使用 CREATE TABLE IF NOT EXISTS，已存在的表不会重建、不会丢数据
--   · 默认数据使用 INSERT IGNORE，已存在的记录自动跳过，可重复导入
--   · 若 admins 表已存在 admin 账号，将保持不动（不重置密码）
-- ============================================================

-- ---------- 1) 管理员表 ----------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('super','manager') NOT NULL DEFAULT 'manager',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 2) 密码库表 ----------
CREATE TABLE IF NOT EXISTS `projects` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id`   INT UNSIGNED NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `image`      VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                           ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  CONSTRAINT `fk_project_admin` FOREIGN KEY (`admin_id`)
    REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 3) 账号表（含账号图片 image 列） ----------
CREATE TABLE IF NOT EXISTS `credentials` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`   INT UNSIGNED NOT NULL,
  `name`         VARCHAR(100) NOT NULL,
  `username`     VARCHAR(255) DEFAULT NULL,
  `password_enc` TEXT,
  `image`        VARCHAR(255) DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  CONSTRAINT `fk_cred_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 4) 可修改字段表 ----------
CREATE TABLE IF NOT EXISTS `credential_fields` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `credential_id` INT UNSIGNED NOT NULL,
  `field_name`    VARCHAR(100) NOT NULL,
  `field_value`   TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_credential` (`credential_id`),
  CONSTRAINT `fk_field_credential` FOREIGN KEY (`credential_id`)
    REFERENCES `credentials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 5) 默认超级管理员（admin / admin123，首次登录后请尽快修改密码） ----------
INSERT IGNORE INTO `admins` (`id`, `username`, `password_hash`, `role`, `created_at`)
VALUES (1, 'admin', '$2y$10$W2Kmpih055pBshN5a.c63uxjjw4Vkcfda4MRGTE7NPOJbwm9zcz5G', 'super', NOW());

-- ---------- 6) 系统设置表 ----------
CREATE TABLE IF NOT EXISTS `settings` (
  `skey`   VARCHAR(64) NOT NULL,
  `svalue` TEXT,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- 7) 系统设置默认项 ----------
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
