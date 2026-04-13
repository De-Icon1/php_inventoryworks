-- Stock Costs Table
-- Tracks cost information for each stock receipt

CREATE TABLE IF NOT EXISTS `stock_costs` (
  `cost_id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `qty` DECIMAL(10, 2) NOT NULL,
  `cost_per_unit` DECIMAL(10, 2) NOT NULL DEFAULT 0,
  `total_cost` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `supplier` VARCHAR(255) NOT NULL,
  `created_by` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`item_id`) REFERENCES `items`(`item_id`) ON DELETE CASCADE,
  INDEX `idx_item_id` (`item_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
