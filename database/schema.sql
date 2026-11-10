-- Barangay Lost and Found Information Management System
-- Import in phpMyAdmin (Import tab) or: mysql -u root < schema.sql
CREATE DATABASE IF NOT EXISTS barangay_lostfound CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barangay_lostfound;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(80) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  address VARCHAR(160) NOT NULL DEFAULT '',
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('resident','admin') NOT NULL DEFAULT 'resident',
  consent_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('lost','found') NOT NULL,
  title VARCHAR(80) NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  description TEXT NOT NULL,
  location VARCHAR(120) NOT NULL,
  date_reported DATE NOT NULL,
  photo VARCHAR(60) NULL,
  reporter_id INT UNSIGNED NOT NULL,
  status ENUM('pending','listed','claim_pending','claimed','closed','rejected') NOT NULL DEFAULT 'pending',
  reject_reason VARCHAR(200) NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_status (status), KEY idx_type (type), KEY idx_reporter (reporter_id),
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (reporter_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE claims (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  claimant_id INT UNSIGNED NOT NULL,
  proof TEXT NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_claim (item_id, claimant_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (claimant_id) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  sender_id INT UNSIGNED NOT NULL,
  body VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item (item_id),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE activity_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  item_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item (item_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('report','claim') NOT NULL,
  item_id INT UNSIGNED NULL,
  message VARCHAR(200) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_read (is_read),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
('Wallets & bags'),('Phones & electronics'),('IDs & documents'),('Keys'),
('Clothing'),('Jewelry & accessories'),('Pets'),('Other');
