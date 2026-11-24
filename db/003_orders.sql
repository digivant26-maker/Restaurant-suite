-- Online pickup orders. Item names and prices are copied onto each line, so later menu edits never change an order.
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref VARCHAR(10) NOT NULL,
  token CHAR(48) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(191) NULL,
  pickup_at DATETIME NOT NULL,
  notes VARCHAR(500) NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'new',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  source VARCHAR(10) NOT NULL DEFAULT 'web',
  cancel_reason VARCHAR(255) NULL,
  accepted_at DATETIME NULL,
  ready_at DATETIME NULL,
  done_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_ref (ref),
  UNIQUE KEY uq_orders_token (token),
  KEY idx_orders_status (status, pickup_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  item_id INT NULL,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  note VARCHAR(200) NULL,
  line_total DECIMAL(10,2) NOT NULL,
  KEY idx_oi_order (order_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
