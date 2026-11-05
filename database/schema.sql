-- YouTube Keşif - MySQL 8+ başlangıç şeması
-- Üretim öncesinde migration, yedekleme ve güvenlik incelemesi yapılmalıdır.

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  google_sub VARCHAR(191) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  status ENUM('active','suspended','deleted') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE youtube_channels (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  youtube_channel_id VARCHAR(64) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  thumbnail_url TEXT NULL,
  custom_url VARCHAR(255) NULL,
  last_synced_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_channel_links (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  youtube_channel_id BIGINT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ended_at DATETIME NULL,
  active_user_id BIGINT UNSIGNED GENERATED ALWAYS AS
    (CASE WHEN ended_at IS NULL THEN user_id ELSE NULL END) STORED,
  active_channel_id BIGINT UNSIGNED GENERATED ALWAYS AS
    (CASE WHEN ended_at IS NULL THEN youtube_channel_id ELSE NULL END) STORED,
  UNIQUE KEY uq_active_user (active_user_id),
  UNIQUE KEY uq_active_channel (active_channel_id),
  KEY idx_link_user_history (user_id, started_at),
  CONSTRAINT fk_link_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_link_channel FOREIGN KEY (youtube_channel_id) REFERENCES youtube_channels(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platform_follows (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  follower_user_id BIGINT UNSIGNED NOT NULL,
  followed_user_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_platform_follow (follower_user_id, followed_user_id),
  KEY idx_followed (followed_user_id, created_at),
  CONSTRAINT fk_follow_from FOREIGN KEY (follower_user_id) REFERENCES users(id),
  CONSTRAINT fk_follow_to FOREIGN KEY (followed_user_id) REFERENCES users(id),
  CONSTRAINT chk_no_self_follow CHECK (follower_user_id <> followed_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_blocks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  blocker_user_id BIGINT UNSIGNED NOT NULL,
  blocked_user_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_block (blocker_user_id, blocked_user_id),
  CONSTRAINT fk_blocker FOREIGN KEY (blocker_user_id) REFERENCES users(id),
  CONSTRAINT fk_blocked FOREIGN KEY (blocked_user_id) REFERENCES users(id),
  CONSTRAINT chk_no_self_block CHECK (blocker_user_id <> blocked_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supporter_status (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  provider_reference VARCHAR(191) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_supporter_active (status, ends_at),
  KEY idx_supporter_user (user_id, starts_at),
  CONSTRAINT fk_supporter_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contributions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(50) NOT NULL,
  provider_reference VARCHAR(191) NULL,
  amount DECIMAL(12,2) NULL,
  currency CHAR(3) NULL,
  status ENUM('pending','succeeded','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_contribution_user (user_id, created_at),
  CONSTRAINT fk_contribution_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE videos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  youtube_video_id VARCHAR(32) NOT NULL UNIQUE,
  youtube_channel_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(500) NOT NULL,
  thumbnail_url TEXT NULL,
  published_at DATETIME NULL,
  availability ENUM('available','private','deleted','unavailable') NOT NULL DEFAULT 'available',
  last_checked_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_video_channel_published (youtube_channel_id, published_at),
  KEY idx_video_availability (availability, published_at),
  CONSTRAINT fk_video_channel FOREIGN KEY (youtube_channel_id) REFERENCES youtube_channels(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE video_impressions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  video_id BIGINT UNSIGNED NOT NULL,
  shown_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  KEY idx_video_impression_user (user_id, shown_at),
  KEY idx_video_impression_pair (user_id, video_id),
  CONSTRAINT fk_video_impression_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_video_impression_video FOREIGN KEY (video_id) REFERENCES videos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_impressions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  viewer_user_id BIGINT UNSIGNED NOT NULL,
  shown_user_id BIGINT UNSIGNED NOT NULL,
  pool_type ENUM('normal','supporter') NOT NULL,
  shown_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_impression_viewer (viewer_user_id, shown_at),
  KEY idx_user_impression_pair (viewer_user_id, shown_user_id),
  CONSTRAINT fk_user_impression_viewer FOREIGN KEY (viewer_user_id) REFERENCES users(id),
  CONSTRAINT fk_user_impression_shown FOREIGN KEY (shown_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE youtube_oauth_tokens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  access_token_enc MEDIUMTEXT NOT NULL,
  refresh_token_enc MEDIUMTEXT NULL,
  expires_at DATETIME NULL,
  scopes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_oauth_user (user_id),
  CONSTRAINT fk_oauth_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE youtube_subscription_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  target_youtube_channel_id VARCHAR(64) NOT NULL,
  api_subscription_id VARCHAR(128) NULL,
  status ENUM('requested','succeeded','already_subscribed','failed','revoked') NOT NULL,
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME NULL,
  error_code VARCHAR(100) NULL,
  KEY idx_subscription_user (user_id, requested_at),
  KEY idx_subscription_target (target_youtube_channel_id),
  CONSTRAINT fk_subscription_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE youtube_metrics_monthly (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  youtube_channel_id BIGINT UNSIGNED NOT NULL,
  metric_month DATE NOT NULL,
  subscriber_count BIGINT UNSIGNED NULL,
  view_count BIGINT UNSIGNED NULL,
  collected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_channel_month (youtube_channel_id, metric_month),
  CONSTRAINT fk_metric_channel FOREIGN KEY (youtube_channel_id) REFERENCES youtube_channels(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  actor_user_id BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(100) NULL,
  entity_id VARCHAR(100) NULL,
  metadata JSON NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created (created_at),
  KEY idx_audit_actor (actor_user_id, created_at),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
