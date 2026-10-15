-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema marketplace_fanmerch
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema marketplace_fanmerch
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `marketplace_fanmerch` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
USE `marketplace_fanmerch` ;

-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`fandom`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`fandom` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fandom_name` VARCHAR(120) NOT NULL,
  `status` ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active',
  `proposed_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_fandom_name` (`fandom_name` ASC),
  INDEX `idx_fandom_status` (`status` ASC),
  INDEX `idx_fandom_proposer` (`proposed_by` ASC),
  CONSTRAINT `fk_fandom_proposer`
    FOREIGN KEY (`proposed_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`fandom_theming`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`fandom_theming` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fandom_id` BIGINT(20) UNSIGNED NOT NULL,
  `theming_name` VARCHAR(80) NOT NULL,
  `primary_color` CHAR(7) NOT NULL COMMENT 'hex warna utama',
  `secondary_color` CHAR(7) NOT NULL COMMENT 'hex ujung gradasi',
  `accent_color` CHAR(7) NULL DEFAULT NULL COMMENT 'hex aksen (opsional)',
  `banner_image` VARCHAR(255) NULL DEFAULT NULL COMMENT 'gambar banner tema (opsional)',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_theming_fandom_name` (`fandom_id` ASC, `theming_name` ASC),
  INDEX `idx_theming_active` (`is_active` ASC),
  INDEX `idx_theming_created_by` (`created_by` ASC),
  CONSTRAINT `fk_theming_created_by`
    FOREIGN KEY (`created_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_theming_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`user` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NULL DEFAULT NULL,
  `role` ENUM('admin', 'artist', 'buyer') NOT NULL DEFAULT 'buyer',
  `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `rating_as_buyer` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count_as_buyer` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `theming_mode` ENUM('auto', 'manual', 'off') NOT NULL DEFAULT 'auto',
  `active_theming_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_user_email` (`email` ASC),
  INDEX `idx_user_role` (`role` ASC),
  INDEX `idx_user_active_theming` (`active_theming_id` ASC),
  CONSTRAINT `fk_user_active_theming`
    FOREIGN KEY (`active_theming_id`)
    REFERENCES `marketplace_fanmerch`.`fandom_theming` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`address`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`address` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `address_label` VARCHAR(60) NOT NULL,
  `recipient_name` VARCHAR(120) NOT NULL,
  `full_address` TEXT NOT NULL,
  `rajaongkir_city_id` VARCHAR(20) NOT NULL,
  `province` VARCHAR(80) NOT NULL,
  `postal_code` VARCHAR(10) NULL DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_address_user` (`user_id` ASC),
  CONSTRAINT `fk_address_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`artist_profile`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`artist_profile` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_name` VARCHAR(120) NOT NULL,
  `shop_description` TEXT NULL DEFAULT NULL,
  `bank_name` VARCHAR(60) NULL DEFAULT NULL,
  `bank_account_number` VARCHAR(40) NULL DEFAULT NULL,
  `available_balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `held_balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_artist_profile_user` (`user_id` ASC),
  CONSTRAINT `fk_artist_profile_user1`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`merchandise_type`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`merchandise_type` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(80) NOT NULL,
  `status` ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active',
  `proposed_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_type_name` (`type_name` ASC),
  INDEX `idx_type_status` (`status` ASC),
  INDEX `idx_type_proposer` (`proposed_by` ASC),
  CONSTRAINT `fk_type_proposer`
    FOREIGN KEY (`proposed_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`custom_request`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`custom_request` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `buyer_id` BIGINT(20) UNSIGNED NOT NULL,
  `merchandise_type_id` BIGINT(20) UNSIGNED NOT NULL,
  `design_character` VARCHAR(150) NOT NULL,
  `quantity` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `specification` TEXT NULL DEFAULT NULL,
  `budget_price` DECIMAL(12,2) NULL DEFAULT NULL,
  `request_status` ENUM('open', 'offered', 'selected', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_request_buyer` (`buyer_id` ASC),
  INDEX `idx_request_type` (`merchandise_type_id` ASC),
  CONSTRAINT `fk_request_buyer`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_request_type`
    FOREIGN KEY (`merchandise_type_id`)
    REFERENCES `marketplace_fanmerch`.`merchandise_type` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`event`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`event` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_event_period` (`start_date` ASC, `end_date` ASC),
  INDEX `fk_event_creator` (`created_by` ASC),
  CONSTRAINT `fk_event_creator`
    FOREIGN KEY (`created_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`event_booth`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`event_booth` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` BIGINT(20) UNSIGNED NOT NULL,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `booth_number` VARCHAR(40) NULL DEFAULT NULL,
  `operating_hours` VARCHAR(60) NULL DEFAULT NULL,
  `attend_days` SET('1', '2', '3', '4', '5', '6', '7') NOT NULL DEFAULT '1',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_booth_event_artist` (`event_id` ASC, `artist_profile_id` ASC, `booth_number` ASC),
  INDEX `idx_booth_artist` (`artist_profile_id` ASC),
  CONSTRAINT `fk_booth_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_booth_event`
    FOREIGN KEY (`event_id`)
    REFERENCES `marketplace_fanmerch`.`event` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`customer_order`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`customer_order` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(40) NOT NULL,
  `buyer_id` BIGINT(20) UNSIGNED NOT NULL,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `address_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `event_booth_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `order_type` ENUM('ready_stock', 'pre_order', 'custom', 'ots_event') NOT NULL,
  `shipping_method` ENUM('courier', 'event_pickup') NULL DEFAULT NULL,
  `payment_scheme` ENUM('full', 'dp') NOT NULL DEFAULT 'full',
  `product_subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `admin_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `order_status` ENUM('awaiting_payment', 'paid', 'awaiting_production', 'in_production', 'production_completed', 'awaiting_settlement', 'shipped', 'ready_for_pickup', 'completed', 'cancelled', 'failed') NOT NULL DEFAULT 'awaiting_payment',
  `courier` VARCHAR(40) NULL DEFAULT NULL,
  `tracking_number` VARCHAR(60) NULL DEFAULT NULL,
  `settlement_deadline` DATETIME NULL DEFAULT NULL,
  `settlement_reminder_stage` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `shipping_deadline` DATETIME NULL DEFAULT NULL,
  `shipping_reminder_stage` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `completed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  `custom_request_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_order_code` (`order_code` ASC),
  UNIQUE INDEX `uq_order_request` (`custom_request_id` ASC),
  INDEX `idx_order_buyer` (`buyer_id` ASC),
  INDEX `idx_order_artist` (`artist_profile_id` ASC),
  INDEX `idx_order_address` (`address_id` ASC),
  INDEX `idx_order_booth` (`event_booth_id` ASC),
  INDEX `idx_order_status` (`order_status` ASC, `created_at` ASC),
  INDEX `idx_order_settlement_due` (`order_status` ASC, `settlement_deadline` ASC),
  INDEX `idx_order_shipping_due` (`order_status` ASC, `shipping_deadline` ASC),
  CONSTRAINT `fk_order_request_custom`
    FOREIGN KEY (`custom_request_id`)
    REFERENCES `marketplace_fanmerch`.`custom_request` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_order_address`
    FOREIGN KEY (`address_id`)
    REFERENCES `marketplace_fanmerch`.`address` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_order_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_order_booth`
    FOREIGN KEY (`event_booth_id`)
    REFERENCES `marketplace_fanmerch`.`event_booth` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_order_buyer`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`payment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`payment` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `payment_type` ENUM('dp', 'settlement', 'full', 'refund') NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `payment_method` VARCHAR(60) NULL DEFAULT NULL,
  `midtrans_order_id` VARCHAR(60) NULL DEFAULT NULL,
  `midtrans_transaction_id` VARCHAR(100) NULL DEFAULT NULL,
  `refund_method` ENUM('midtrans_api', 'manual_transfer') NULL DEFAULT NULL,
  `refund_proof` VARCHAR(255) NULL DEFAULT NULL,
  `payment_status` ENUM('pending', 'success', 'failed', 'expired', 'refunded') NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_payment_midtrans_order` (`midtrans_order_id` ASC),
  UNIQUE INDEX `uq_payment_midtrans_trx` (`midtrans_transaction_id` ASC),
  INDEX `idx_payment_order` (`customer_order_id` ASC, `payment_type` ASC),
  CONSTRAINT `fk_payment_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`withdrawal`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`withdrawal` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `bank_name` VARCHAR(60) NOT NULL,
  `bank_account_number` VARCHAR(40) NOT NULL,
  `withdrawal_status` ENUM('submitted', 'processing', 'completed', 'rejected') NOT NULL DEFAULT 'submitted',
  `processed_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `transfer_proof` VARCHAR(255) NULL DEFAULT NULL,
  `rejection_reason` VARCHAR(255) NULL DEFAULT NULL COMMENT 'alasan admin menolak penarikan (dilihat artis)',
  `requested_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `processed_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_withdrawal_artist` (`artist_profile_id` ASC, `withdrawal_status` ASC),
  INDEX `idx_withdrawal_admin` (`processed_by` ASC),
  CONSTRAINT `fk_withdrawal_admin`
    FOREIGN KEY (`processed_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_withdrawal_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`balance_transaction`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`balance_transaction` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `customer_order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `payment_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `withdrawal_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `mutation_type` ENUM('credit', 'debit') NOT NULL,
  `category` ENUM('hold', 'release', 'forfeiture', 'refund', 'withdrawal') NOT NULL DEFAULT 'release',
  `gross_amount` DECIMAL(14,2) NULL DEFAULT NULL,
  `fee_amount` DECIMAL(14,2) NULL DEFAULT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_balance_artist` (`artist_profile_id` ASC, `created_at` ASC),
  INDEX `idx_balance_order` (`customer_order_id` ASC),
  INDEX `idx_balance_withdrawal` (`withdrawal_id` ASC),
  INDEX `idx_balance_payment` (`payment_id` ASC),
  CONSTRAINT `fk_balance_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_payment`
    FOREIGN KEY (`payment_id`)
    REFERENCES `marketplace_fanmerch`.`payment` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_balance_withdrawal`
    FOREIGN KEY (`withdrawal_id`)
    REFERENCES `marketplace_fanmerch`.`withdrawal` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`cancellation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`cancellation` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `requester_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `cancellation_type` ENUM('voluntary', 'settlement_timeout', 'shipping_timeout') NOT NULL DEFAULT 'voluntary',
  `reason` TEXT NOT NULL,
  `cancellation_status` ENUM('submitted', 'in_discussion', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
  `artist_response` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `resolved_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_cancellation_order` (`customer_order_id` ASC),
  INDEX `idx_cancellation_requester` (`requester_id` ASC),
  CONSTRAINT `fk_cancellation_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_cancellation_requester`
    FOREIGN KEY (`requester_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`fandom_character`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`fandom_character` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fandom_id` BIGINT(20) UNSIGNED NOT NULL,
  `character_name` VARCHAR(120) NOT NULL,
  `status` ENUM('active', 'pending', 'rejected') NOT NULL DEFAULT 'active',
  `proposed_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_character_fandom_name` (`fandom_id` ASC, `character_name` ASC),
  INDEX `idx_character_status` (`status` ASC),
  INDEX `idx_character_proposer` (`proposed_by` ASC),
  CONSTRAINT `fk_character_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_character_proposer`
    FOREIGN KEY (`proposed_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`design_poll`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`design_poll` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `poll_title` VARCHAR(150) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME NOT NULL,
  `poll_status` ENUM('running', 'completed', 'cancelled') NOT NULL DEFAULT 'running',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_poll_artist` (`artist_profile_id` ASC),
  INDEX `idx_poll_period` (`end_date` ASC, `poll_status` ASC),
  CONSTRAINT `fk_poll_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`design_option`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`design_option` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `design_poll_id` BIGINT(20) UNSIGNED NOT NULL,
  `design_image` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_option_poll` (`design_poll_id` ASC),
  CONSTRAINT `fk_option_poll`
    FOREIGN KEY (`design_poll_id`)
    REFERENCES `marketplace_fanmerch`.`design_poll` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`product` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `fandom_character_id` BIGINT(20) UNSIGNED NOT NULL,
  `merchandise_type_id` BIGINT(20) UNSIGNED NOT NULL,
  `design_option_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `size` VARCHAR(60) NULL DEFAULT NULL,
  `stock` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `availability_type` ENUM('ready_stock', 'pre_order') NOT NULL DEFAULT 'ready_stock',
  `allow_event_pickup` TINYINT(1) NOT NULL DEFAULT 0,
  `product_status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
  `rating` DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `rating_count` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_product_artist` (`artist_profile_id` ASC),
  INDEX `idx_product_character` (`fandom_character_id` ASC),
  INDEX `idx_product_type` (`merchandise_type_id` ASC),
  INDEX `idx_product_option_design` (`design_option_id` ASC),
  INDEX `idx_product_filter` (`availability_type` ASC, `product_status` ASC, `price` ASC),
  CONSTRAINT `fk_product_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_product_character`
    FOREIGN KEY (`fandom_character_id`)
    REFERENCES `marketplace_fanmerch`.`fandom_character` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_product_option_design`
    FOREIGN KEY (`design_option_id`)
    REFERENCES `marketplace_fanmerch`.`design_option` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_product_type`
    FOREIGN KEY (`merchandise_type_id`)
    REFERENCES `marketplace_fanmerch`.`merchandise_type` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`cart_item`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`cart_item` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `product_id` BIGINT(20) UNSIGNED NOT NULL,
  `quantity` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_cart_user_product` (`user_id` ASC, `product_id` ASC),
  INDEX `idx_cart_product` (`product_id` ASC),
  CONSTRAINT `fk_cart_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_cart_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`chat_message`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`chat_message` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` BIGINT(20) UNSIGNED NOT NULL,
  `receiver_id` BIGINT(20) UNSIGNED NOT NULL,
  `customer_order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `custom_request_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `product_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `message_body` TEXT NOT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_chat_sender` (`sender_id` ASC),
  INDEX `idx_chat_receiver` (`receiver_id` ASC),
  INDEX `idx_chat_order` (`customer_order_id` ASC),
  INDEX `idx_chat_request` (`custom_request_id` ASC),
  INDEX `idx_chat_product` (`product_id` ASC),
  CONSTRAINT `fk_chat_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_receiver`
    FOREIGN KEY (`receiver_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_request`
    FOREIGN KEY (`custom_request_id`)
    REFERENCES `marketplace_fanmerch`.`custom_request` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_chat_sender`
    FOREIGN KEY (`sender_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`custom_offer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`custom_offer` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `custom_request_id` BIGINT(20) UNSIGNED NOT NULL,
  `artist_profile_id` BIGINT(20) UNSIGNED NOT NULL,
  `offer_price` DECIMAL(12,2) NOT NULL,
  `estimated_days` INT(10) UNSIGNED NOT NULL,
  `note` TEXT NULL DEFAULT NULL,
  `offer_status` ENUM('pending', 'selected', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_offer_request_artist` (`custom_request_id` ASC, `artist_profile_id` ASC),
  INDEX `idx_offer_artist` (`artist_profile_id` ASC),
  CONSTRAINT `fk_offer_artist`
    FOREIGN KEY (`artist_profile_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_offer_request`
    FOREIGN KEY (`custom_request_id`)
    REFERENCES `marketplace_fanmerch`.`custom_request` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`design_vote`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`design_vote` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `design_poll_id` BIGINT(20) UNSIGNED NOT NULL,
  `design_option_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `voted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_vote_poll_user` (`design_poll_id` ASC, `user_id` ASC),
  UNIQUE INDEX `uq_vote_option_user` (`design_option_id` ASC, `user_id` ASC),
  INDEX `idx_vote_user` (`user_id` ASC),
  CONSTRAINT `fk_vote_option`
    FOREIGN KEY (`design_option_id`)
    REFERENCES `marketplace_fanmerch`.`design_option` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_poll`
    FOREIGN KEY (`design_poll_id`)
    REFERENCES `marketplace_fanmerch`.`design_poll` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_vote_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`discussion_topic`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`discussion_topic` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fandom_id` BIGINT(20) UNSIGNED NOT NULL,
  `creator_id` BIGINT(20) UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_topic_fandom` (`fandom_id` ASC, `created_at` ASC),
  INDEX `idx_topic_creator` (`creator_id` ASC),
  CONSTRAINT `fk_topic_creator`
    FOREIGN KEY (`creator_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_topic_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`event_pickup`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`event_pickup` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_booth_id` BIGINT(20) UNSIGNED NOT NULL,
  `qr_code` VARCHAR(100) NOT NULL,
  `pickup_status` ENUM('not_picked_up', 'picked_up', 'forfeited') NOT NULL DEFAULT 'not_picked_up',
  `picked_up_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_pickup_code_qr` (`qr_code` ASC),
  UNIQUE INDEX `uq_pickup_order` (`customer_order_id` ASC),
  INDEX `idx_pickup_booth` (`event_booth_id` ASC),
  CONSTRAINT `fk_event_pickup_customer_order1`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_pickup_booth`
    FOREIGN KEY (`event_booth_id`)
    REFERENCES `marketplace_fanmerch`.`event_booth` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`fandom_member`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`fandom_member` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fandom_id` BIGINT(20) UNSIGNED NOT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `role` ENUM('member', 'moderator') NOT NULL DEFAULT 'member',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_member_fandom_user` (`fandom_id` ASC, `user_id` ASC),
  INDEX `idx_member_user` (`user_id` ASC),
  CONSTRAINT `fk_member_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_member_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`favorite_fandom`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`favorite_fandom` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `fandom_id` BIGINT(20) UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_favorite_user_fandom` (`user_id` ASC, `fandom_id` ASC),
  INDEX `idx_favorite_fandom` (`fandom_id` ASC),
  CONSTRAINT `fk_favorite_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_favorite_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`file_upload`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`file_upload` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `url` VARCHAR(255) NOT NULL,
  `source_table` VARCHAR(50) NOT NULL,
  `source_id` BIGINT(20) UNSIGNED NOT NULL,
  `uploaded_by` BIGINT(20) UNSIGNED NOT NULL,
  `sort_order` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_file_source` (`source_table` ASC, `source_id` ASC),
  INDEX `idx_file_uploader` (`uploaded_by` ASC),
  CONSTRAINT `fk_file_uploader`
    FOREIGN KEY (`uploaded_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`po_batch`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`po_batch` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` BIGINT(20) UNSIGNED NOT NULL,
  `price_po` DECIMAL(12,2) NOT NULL,
  `payment_scheme` ENUM('dp', 'full') NOT NULL DEFAULT 'full',
  `dp_amount` DECIMAL(12,2) NULL DEFAULT NULL,
  `min_order` INT(10) UNSIGNED NULL DEFAULT NULL,
  `max_slot` INT(10) UNSIGNED NULL DEFAULT NULL,
  `opens_at` DATETIME NOT NULL,
  `closes_at` DATETIME NOT NULL,
  `estimated_month` DATE NULL DEFAULT NULL,
  `batch_status` ENUM('open', 'closed', 'in_production', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
  `completed_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_batch_product` (`product_id` ASC, `batch_status` ASC),
  CONSTRAINT `fk_batch_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`order_item`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`order_item` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `product_id` BIGINT(20) UNSIGNED NOT NULL,
  `po_batch_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `quantity` INT(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_item_order` (`customer_order_id` ASC),
  INDEX `idx_item_product` (`product_id` ASC),
  INDEX `idx_item_batch` (`po_batch_id` ASC),
  CONSTRAINT `fk_item_batch`
    FOREIGN KEY (`po_batch_id`)
    REFERENCES `marketplace_fanmerch`.`po_batch` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_item_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_item_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`order_return`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`order_return` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `buyer_id` BIGINT(20) UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `photo_evidence` VARCHAR(255) NULL DEFAULT NULL,
  `return_status` ENUM('submitted', 'processing', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'submitted',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_return_order` (`customer_order_id` ASC),
  INDEX `idx_return_buyer` (`buyer_id` ASC),
  CONSTRAINT `fk_return_buyer`
    FOREIGN KEY (`buyer_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_return_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`platform_setting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`platform_setting` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_percentage` DECIMAL(5,2) NOT NULL,
  `fee_minimum` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `penalty_voluntary` DECIMAL(3,2) NOT NULL DEFAULT 0.20,
  `penalty_settlement_timeout` DECIMAL(3,2) NOT NULL DEFAULT 0.20,
  `penalty_shipping_timeout` DECIMAL(3,2) NOT NULL DEFAULT 0.30,
  `effective_from` DATETIME NOT NULL,
  `changed_by_id` BIGINT(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_effective` (`effective_from` ASC),
  INDEX `idx_setting_changed_by` (`changed_by_id` ASC),
  CONSTRAINT `fk_setting_changed_by`
    FOREIGN KEY (`changed_by_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`product_review`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`product_review` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `product_id` BIGINT(20) UNSIGNED NOT NULL,
  `reviewer_id` BIGINT(20) UNSIGNED NOT NULL,
  `rating` TINYINT(3) UNSIGNED NOT NULL,
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_preview_order_product` (`customer_order_id` ASC, `product_id` ASC),
  INDEX `idx_preview_product` (`product_id` ASC),
  INDEX `idx_preview_reviewer` (`reviewer_id` ASC),
  CONSTRAINT `fk_preview_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_preview_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_preview_reviewer`
    FOREIGN KEY (`reviewer_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`product_view`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`product_view` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `product_id` BIGINT(20) UNSIGNED NOT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_view_user` (`user_id` ASC, `viewed_at` ASC),
  INDEX `idx_view_product` (`product_id` ASC),
  CONSTRAINT `fk_view_product`
    FOREIGN KEY (`product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_view_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`review`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`review` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_order_id` BIGINT(20) UNSIGNED NOT NULL,
  `reviewer_id` BIGINT(20) UNSIGNED NOT NULL,
  `reviewee_id` BIGINT(20) UNSIGNED NOT NULL,
  `direction` ENUM('buyer_to_artist', 'artist_to_buyer') NOT NULL,
  `rating` TINYINT(3) UNSIGNED NOT NULL,
  `comment` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uq_review_order_reviewer` (`customer_order_id` ASC, `reviewer_id` ASC),
  INDEX `idx_review_reviewee` (`reviewee_id` ASC),
  INDEX `idx_review_reviewer` (`reviewer_id` ASC),
  CONSTRAINT `fk_review_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reviewee`
    FOREIGN KEY (`reviewee_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_review_reviewer`
    FOREIGN KEY (`reviewer_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`search_history`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`search_history` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `keyword` VARCHAR(200) NOT NULL,
  `fandom_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `fandom_character_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `merchandise_type_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `searched_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_history_user` (`user_id` ASC, `searched_at` ASC),
  INDEX `idx_history_fandom` (`fandom_id` ASC),
  INDEX `idx_history_character` (`fandom_character_id` ASC),
  INDEX `idx_history_type` (`merchandise_type_id` ASC),
  CONSTRAINT `fk_history_character`
    FOREIGN KEY (`fandom_character_id`)
    REFERENCES `marketplace_fanmerch`.`fandom_character` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_history_fandom`
    FOREIGN KEY (`fandom_id`)
    REFERENCES `marketplace_fanmerch`.`fandom` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_history_type`
    FOREIGN KEY (`merchandise_type_id`)
    REFERENCES `marketplace_fanmerch`.`merchandise_type` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_history_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`topic_message`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`topic_message` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `discussion_topic_id` BIGINT(20) UNSIGNED NOT NULL,
  `parent_message_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `message_body` TEXT NOT NULL,
  `attached_product_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `attached_artist_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_message_topic` (`discussion_topic_id` ASC, `sent_at` ASC),
  INDEX `idx_message_parent` (`parent_message_id` ASC),
  INDEX `idx_message_group_user` (`user_id` ASC),
  INDEX `idx_message_attachment_product` (`attached_product_id` ASC),
  INDEX `idx_message_attachment_artist` (`attached_artist_id` ASC),
  CONSTRAINT `fk_message_attachment_artist`
    FOREIGN KEY (`attached_artist_id`)
    REFERENCES `marketplace_fanmerch`.`artist_profile` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_message_attachment_product`
    FOREIGN KEY (`attached_product_id`)
    REFERENCES `marketplace_fanmerch`.`product` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_message_group_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_message_parent`
    FOREIGN KEY (`parent_message_id`)
    REFERENCES `marketplace_fanmerch`.`topic_message` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_message_topic`
    FOREIGN KEY (`discussion_topic_id`)
    REFERENCES `marketplace_fanmerch`.`discussion_topic` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`track_transaksi`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`track_transaksi` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT(20) UNSIGNED NOT NULL,
  `customer_order_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL,
  `title` VARCHAR(150) NOT NULL,
  `body` TEXT NOT NULL,
  `notification_type` ENUM('order', 'payment', 'po_settlement', 'event_pickup', 'cancellation', 'custom', 'design_poll', 'withdrawal', 'attribute', 'community') NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `email_status` ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_track_user` (`user_id` ASC, `is_read` ASC),
  INDEX `idx_track_email` (`email_status` ASC),
  INDEX `idx_track_order` (`customer_order_id` ASC),
  CONSTRAINT `fk_track_order`
    FOREIGN KEY (`customer_order_id`)
    REFERENCES `marketplace_fanmerch`.`customer_order` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_track_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------
-- Table `marketplace_fanmerch`.`community_report`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `marketplace_fanmerch`.`community_report` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporter_id` BIGINT(20) UNSIGNED NOT NULL COMMENT 'pengguna yang melapor',
  `reported_user_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'penulis konten yang dilaporkan',
  `discussion_topic_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'topik yang dilaporkan / tempat pesan berada',
  `topic_message_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = yang dilaporkan topiknya',
  `reason_category` ENUM('spam', 'sara', 'pornografi', 'penipuan', 'pelecehan', 'lainnya') NOT NULL,
  `reason_note` VARCHAR(255) NULL DEFAULT NULL COMMENT 'keterangan tambahan dari pelapor',
  `reported_content` TEXT NOT NULL COMMENT 'salinan isi konten saat dilaporkan (tetap ada walau konten dihapus)',
  `report_status` ENUM('pending', 'removed', 'dismissed') NOT NULL DEFAULT 'pending' COMMENT 'removed = konten dihapus admin, dismissed = laporan ditolak',
  `handled_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL COMMENT 'admin yang menindaklanjuti',
  `admin_note` VARCHAR(255) NULL DEFAULT NULL COMMENT 'alasan penghapusan / alasan laporan ditolak',
  `handled_at` DATETIME NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  INDEX `idx_report_status` (`report_status` ASC, `created_at` ASC),
  INDEX `idx_report_reporter` (`reporter_id` ASC),
  INDEX `idx_report_reported_user` (`reported_user_id` ASC),
  INDEX `idx_report_topic` (`discussion_topic_id` ASC),
  INDEX `idx_report_message` (`topic_message_id` ASC),
  INDEX `idx_report_handler` (`handled_by` ASC),
  CONSTRAINT `fk_report_reporter`
    FOREIGN KEY (`reporter_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_report_reported_user`
    FOREIGN KEY (`reported_user_id`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_report_topic`
    FOREIGN KEY (`discussion_topic_id`)
    REFERENCES `marketplace_fanmerch`.`discussion_topic` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_report_message`
    FOREIGN KEY (`topic_message_id`)
    REFERENCES `marketplace_fanmerch`.`topic_message` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `fk_report_handler`
    FOREIGN KEY (`handled_by`)
    REFERENCES `marketplace_fanmerch`.`user` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_unicode_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
