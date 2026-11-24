-- Events (weekly or one-off), specials such as happy hour, and the games schedule shown on the screens.
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(10) NOT NULL DEFAULT 'event',
  title VARCHAR(100) NOT NULL,
  description VARCHAR(400) NULL,
  weekday TINYINT NULL,
  event_date DATE NULL,
  time_text VARCHAR(60) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  position INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_events_kind (kind, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  league VARCHAR(12) NOT NULL,
  away VARCHAR(60) NOT NULL,
  home VARCHAR(60) NOT NULL,
  starts_at DATETIME NOT NULL,
  screens VARCHAR(60) NULL,
  sound TINYINT(1) NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  note VARCHAR(160) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_games_starts (starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_photos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  file VARCHAR(80) NOT NULL,
  caption VARCHAR(160) NULL,
  position INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
