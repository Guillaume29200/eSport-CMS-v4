-- ============================================
-- PremiumManager - Schema SQL
-- ============================================

-- Table des plans premium
CREATE TABLE IF NOT EXISTS `premium_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `billing_period` ENUM('monthly', 'yearly', 'lifetime') NOT NULL DEFAULT 'monthly',
  `trial_days` INT UNSIGNED DEFAULT 0,
  `features` JSON,
  `max_articles` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
  `max_pages` INT UNSIGNED DEFAULT NULL,
  `max_modules` INT UNSIGNED DEFAULT NULL,
  `stripe_price_id` VARCHAR(255) DEFAULT NULL,
  `paypal_plan_id` VARCHAR(255) DEFAULT NULL,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des abonnements utilisateurs
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `status` ENUM('active', 'trialing', 'cancelled', 'expired', 'past_due') NOT NULL DEFAULT 'active',
  `stripe_subscription_id` VARCHAR(255) DEFAULT NULL,
  `paypal_subscription_id` VARCHAR(255) DEFAULT NULL,
  `current_period_start` TIMESTAMP NOT NULL,
  `current_period_end` TIMESTAMP NOT NULL,
  `cancel_at_period_end` BOOLEAN NOT NULL DEFAULT FALSE,
  `cancelled_at` TIMESTAMP NULL,
  `trial_ends_at` TIMESTAMP NULL,
  `auto_renew` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `premium_plans`(`id`) ON DELETE RESTRICT,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_period_end` (`current_period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des contenus premium
CREATE TABLE IF NOT EXISTS `premium_content` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `content_type` ENUM('article', 'page', 'module', 'forum_section', 'download') NOT NULL,
  `content_id` INT UNSIGNED NOT NULL,
  `access_type` ENUM('one_time', 'subscription', 'plan_required') NOT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL COMMENT 'Pour one_time',
  `currency` VARCHAR(3) DEFAULT 'EUR',
  `required_plan_ids` JSON DEFAULT NULL COMMENT 'Plans autorisés',
  `preview_enabled` BOOLEAN NOT NULL DEFAULT TRUE,
  `preview_length` INT DEFAULT 300 COMMENT 'Caractères visibles gratuitement',
  `custom_paywall_message` TEXT,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_content` (`content_type`, `content_id`),
  INDEX `idx_content_type` (`content_type`),
  INDEX `idx_access_type` (`access_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des transactions
CREATE TABLE IF NOT EXISTS `premium_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_type` ENUM('subscription', 'one_time', 'refund', 'upgrade', 'downgrade') NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `status` ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_provider` ENUM('stripe', 'paypal', 'manual', 'bank_transfer') NOT NULL,
  `provider_transaction_id` VARCHAR(255) DEFAULT NULL,
  `provider_customer_id` VARCHAR(255) DEFAULT NULL,
  `content_type` VARCHAR(50) DEFAULT NULL,
  `content_id` INT UNSIGNED DEFAULT NULL,
  `plan_id` INT UNSIGNED DEFAULT NULL,
  `subscription_id` INT UNSIGNED DEFAULT NULL,
  `coupon_code` VARCHAR(50) DEFAULT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0.00,
  `invoice_id` INT UNSIGNED DEFAULT NULL,
  `metadata` JSON,
  `failure_reason` TEXT,
  `refunded_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_provider_transaction` (`provider_transaction_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des accès débloqués
CREATE TABLE IF NOT EXISTS `user_premium_access` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `content_type` VARCHAR(50) NOT NULL,
  `content_id` INT UNSIGNED NOT NULL,
  `access_method` ENUM('subscription', 'one_time', 'gift', 'admin_grant') NOT NULL,
  `transaction_id` INT UNSIGNED DEFAULT NULL,
  `subscription_id` INT UNSIGNED DEFAULT NULL,
  `unlocked_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL COMMENT 'NULL = permanent',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_access` (`user_id`, `content_type`, `content_id`),
  INDEX `idx_user_content` (`user_id`, `content_type`),
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des coupons
CREATE TABLE IF NOT EXISTS `premium_coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('percentage', 'fixed_amount') NOT NULL,
  `value` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'EUR',
  `max_uses` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = illimité',
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_from` TIMESTAMP NULL,
  `valid_until` TIMESTAMP NULL,
  `applicable_plans` JSON DEFAULT NULL COMMENT 'NULL = tous les plans',
  `first_payment_only` BOOLEAN NOT NULL DEFAULT FALSE,
  `active` BOOLEAN NOT NULL DEFAULT TRUE,
  `created_by` INT UNSIGNED,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des utilisations de coupons
CREATE TABLE IF NOT EXISTS `premium_coupon_usage` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `coupon_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` INT UNSIGNED NOT NULL,
  `discount_amount` DECIMAL(10,2) NOT NULL,
  `used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`coupon_id`) REFERENCES `premium_coupons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`transaction_id`) REFERENCES `premium_transactions`(`id`) ON DELETE CASCADE,
  INDEX `idx_coupon_user` (`coupon_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des factures
CREATE TABLE IF NOT EXISTS `premium_invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NOT NULL,
  `transaction_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `status` ENUM('draft', 'sent', 'paid', 'cancelled') NOT NULL DEFAULT 'draft',
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `due_at` TIMESTAMP NULL,
  `paid_at` TIMESTAMP NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`transaction_id`) REFERENCES `premium_transactions`(`id`) ON DELETE CASCADE,
  INDEX `idx_invoice_number` (`invoice_number`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de statistiques (cache)
CREATE TABLE IF NOT EXISTS `premium_statistics` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  `metric` VARCHAR(50) NOT NULL,
  `value` DECIMAL(15,2) NOT NULL,
  `metadata` JSON,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_date_metric` (`date`, `metric`),
  INDEX `idx_date` (`date`),
  INDEX `idx_metric` (`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des webhooks logs
CREATE TABLE IF NOT EXISTS `premium_webhook_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `provider` VARCHAR(50) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` JSON NOT NULL,
  `status` ENUM('received', 'processed', 'failed') NOT NULL DEFAULT 'received',
  `error_message` TEXT,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_provider` (`provider`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
