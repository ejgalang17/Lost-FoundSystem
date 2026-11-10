-- Run this ONLY if you already imported schema.sql before notifications were added.
-- phpMyAdmin: select the barangay_lostfound database, open the SQL tab, paste, Go.
USE barangay_lostfound;
CREATE TABLE IF NOT EXISTS notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('report','claim') NOT NULL,
  item_id INT UNSIGNED NULL,
  message VARCHAR(200) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_read (is_read),
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
) ENGINE=InnoDB;
