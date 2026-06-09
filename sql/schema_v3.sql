-- ============================================================
-- schema_v3.sql - Applying structural changes from user request
-- ============================================================

USE deutschlernen;

-- 1. SCENARIO USER PROGRESS
-- Drop old table if exists
DROP TABLE IF EXISTS `scenario_user_progress`;

CREATE TABLE `scenario_user_progress` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL,
  `scenario_id`  INT UNSIGNED NOT NULL,
  `completed`    TINYINT(1) DEFAULT 0,
  `score`        TINYINT UNSIGNED DEFAULT 0 COMMENT '0-100',
  `last_step`    TINYINT UNSIGNED DEFAULT 0,
  `completed_at` TIMESTAMP NULL,
  UNIQUE KEY `user_scenario` (`user_id`,`scenario_id`),
  INDEX `idx_user` (`user_id`),
  FOREIGN KEY (`scenario_id`) REFERENCES `scenarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate data from old user_progress if needed, or just let it start fresh
INSERT IGNORE INTO `scenario_user_progress` (`user_id`, `scenario_id`, `completed`, `score`, `last_step`, `completed_at`)
SELECT `user_id`, `scenario_id`, `completed`, `score`, 0, `completed_at` FROM `user_progress`;

-- 2. SCENARIO CHAT MESSAGES
DROP TABLE IF EXISTS `scenario_chat_messages`;

CREATE TABLE `scenario_chat_messages` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`      INT UNSIGNED NOT NULL COMMENT 'Added to scope chat to specific user',
  `scenario_id`  INT UNSIGNED NOT NULL,
  `sender`       ENUM('user','system','npc') NOT NULL DEFAULT 'user',
  `message_text` TEXT NOT NULL,
  `translation`  TEXT,
  `message_type` ENUM('text','voice') DEFAULT 'text',
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_chat_session` (`user_id`, `scenario_id`),
  FOREIGN KEY (`scenario_id`) REFERENCES `scenarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
