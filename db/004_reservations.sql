-- Table reservations. Capacity is counted in seats (party sizes) per time slot.
CREATE TABLE IF NOT EXISTS reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(10) NOT NULL,
  token CHAR(48) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(191) NULL,
  party INT NOT NULL,
  starts_at DATETIME NOT NULL,
  occasion VARCHAR(40) NULL,
  seating VARCHAR(20) NULL,
  notes VARCHAR(500) NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'requested',
  decline_reason VARCHAR(255) NULL,
  source VARCHAR(10) NOT NULL DEFAULT 'web',
  decided_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_res_ref (ref),
  UNIQUE KEY uq_res_token (token),
  KEY idx_res_starts (starts_at, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
