-- Staff, settings, shop profile, website messages, activity and rate limits.
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  staff_code VARCHAR(16) NULL,
  email VARCHAR(191) NULL,
  name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(20) NOT NULL DEFAULT 'server',
  phone VARCHAR(30) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_staff_code (staff_code),
  KEY idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id INT PRIMARY KEY,
  ordering_on TINYINT(1) NOT NULL DEFAULT 1,
  order_lead_minutes INT NOT NULL DEFAULT 20,
  order_open TIME NOT NULL DEFAULT '11:00:00',
  order_close TIME NOT NULL DEFAULT '21:30:00',
  order_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7',
  tax_pct DECIMAL(5,3) NOT NULL DEFAULT 8.250,
  reservations_on TINYINT(1) NOT NULL DEFAULT 1,
  res_open TIME NOT NULL DEFAULT '11:00:00',
  res_close TIME NOT NULL DEFAULT '21:00:00',
  res_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7',
  res_slot_minutes INT NOT NULL DEFAULT 30,
  res_seats_per_slot INT NOT NULL DEFAULT 40,
  res_max_party INT NOT NULL DEFAULT 12,
  res_lead_hours INT NOT NULL DEFAULT 2,
  res_window_days INT NOT NULL DEFAULT 30,
  res_auto_confirm TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings (id) VALUES (1);

CREATE TABLE IF NOT EXISTS shop_profile (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(16) NOT NULL DEFAULT 'contact',
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(191) NULL,
  subject VARCHAR(160) NULL,
  event_date DATE NULL,
  guests INT NULL,
  body TEXT NOT NULL,
  handled TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_messages_open (handled, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  user_name VARCHAR(120) NULL,
  action VARCHAR(60) NOT NULL,
  detail VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_activity_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bucket VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bucket (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
