-- =======================================================
-- ONLINE WATCHES WEBSITE - DATABASE SCHEMA
-- Project: BCA Semester 5 - 504 Web Framework & Services (WFS)
-- Database Name: watches_db
-- =======================================================

CREATE DATABASE IF NOT EXISTS `watches_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `watches_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- 1. Table: admins
-- Purpose: Store administrator login credentials
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`) VALUES
(1, 'admin', 'admin@watches.com', '$2y$10$wY9e4b7eZfN6Tq5U1A3jQ.0n3d7cW6s6Y0B1m2L8x9K4r5O6P7Q8u')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- -------------------------------------------------------
-- 2. Table: users
-- Purpose: Store registered customer details
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `mobile` VARCHAR(15) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`user_id`, `full_name`, `email`, `mobile`, `password`) VALUES
(1, 'Rahul Sharma', 'user@example.com', '9876543210', '$2y$10$HfInkr5Mt0gW2NVkB/IdFOsBkJQfckU9qMXjB3dr9enP/AdMiebqC')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);

-- -------------------------------------------------------
-- 3. Table: products
-- Purpose: Store watch product catalogue
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `brand` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Watch Products
INSERT INTO `products` (`id`, `name`, `brand`, `price`, `image`, `category`, `description`, `quantity`) VALUES
(1, 'Titan Contemporary Quartz Watch', 'Titan', 12499.00, 'images/watch1.jpg', 'Luxury Watches', 'Authentic Titan contemporary rectangular quartz watch with sleek black dial, silver indices, and stainless steel link bracelet. Water resistant up to 50m.', 15),
(2, 'Fossil Two-Tone Chronograph Watch', 'Fossil', 18995.00, 'images/watch2.jpg', 'Luxury Watches', 'Authentic Fossil two-tone gold and stainless steel chronograph watch featuring a multi-layered textured dial, date display, sub-dials, and scratch-resistant mineral glass.', 8),
(3, 'Citizen Eco-Drive Tachymeter Chrono', 'Citizen', 9495.00, 'images/watch3.jpg', 'Chronograph Watches', 'Citizen Eco-Drive sports chronograph powered by any light source, featuring stopwatch functionality, tachymeter scale bezel, and rugged stainless steel casing.', 20),
(4, 'Seiko 5 Automatic GMT Edition Watch', 'Seiko', 15750.00, 'images/watch4.jpg', 'Chronograph Watches', 'Authentic Seiko 5 Sports Automatic GMT watch with iconic blue/black 24-hour rotating bezel, red GMT dual-time hand, date magnifier, and solid jubilee bracelet.', 12),
(5, 'Samsung Galaxy Touch Smartwatch', 'Samsung', 14999.00, 'images/watch5.jpg', 'Smart Watches', 'Authentic Samsung Galaxy smartwatch with circular AMOLED display, rotating navigation bezel, heart rate monitoring, SpO2 sensor, sleep tracker, and 50m swimproof design.', 18),
(6, 'ColorFit Digital OLED Smartwatch', 'ColorFit', 3499.00, 'images/watch6.jpg', 'Smart Watches', 'Modern digital touchscreen smartwatch featuring crisp active UI display showing real-time digital clock, steps counter, weather widget, 60+ sports modes, and sleep tracking.', 30),
(7, 'Daniel Wellington Classic NATO Watch', 'Daniel Wellington', 11999.00, 'images/watch7.jpg', 'Casual & Minimalist', 'Authentic Daniel Wellington Classic ultra-thin dress watch in polished gold casing with signature tri-color striped nylon NATO strap and eggshell white minimalist dial.', 14),
(8, 'Timex Outdoor Casual Leather Watch', 'Timex', 2195.00, 'images/watch8.jpg', 'Casual & Minimalist', 'Authentic Timex outdoor field watch with easy-to-read Arabic numerals, matte black casing, indiglo night light, and durable genuine tan leather strap.', 25),
(9, 'Citizen Eco-Drive Diver 200M Watch', 'Citizen', 13500.00, 'images/watch9.jpg', 'Formal & Classic', 'Authentic Citizen Eco-Drive Promaster 200m diver watch powered by light with unidirectional rotating bezel, date window, and high-visibility luminescent markers.', 10),
(10, 'Casio LTP Classic Roman Tank Watch', 'Casio', 7895.00, 'images/watch10.jpg', 'Formal & Classic', 'Authentic Casio LTP Classic rectangular tank watch featuring elegant Roman numeral indices on a subtle mint dial and genuine black leather band.', 16),
(11, 'Battuta Minimalist Rose Gold Watch', 'Battuta', 8495.00, 'images/watch11.jpg', 'Casual & Minimalist', 'Minimalist dress watch featuring a slender rose gold tone bezel, clean white dial with slim markers, Japanese quartz movement, and premium black leather strap.', 12),
(12, 'Casio G-Shock Shock Resistant Watch', 'Casio', 11495.00, 'images/watch12.jpg', 'Chronograph Watches', 'Authentic Casio G-Shock rugged sports watch engineered with legendary shock resistance, 200m water resistance, world time, stopwatch, countdown timer, and auto LED backlight.', 15)
ON DUPLICATE KEY UPDATE 
  `name`=VALUES(`name`), 
  `brand`=VALUES(`brand`), 
  `price`=VALUES(`price`), 
  `image`=VALUES(`image`), 
  `category`=VALUES(`category`), 
  `description`=VALUES(`description`), 
  `quantity`=VALUES(`quantity`);

-- -------------------------------------------------------
-- 3b. Table: brands
-- Purpose: Store watch brand names and their logo paths
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `logo` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Initial Brand Logos
INSERT INTO `brands` (`id`, `name`, `logo`) VALUES
(1, 'Titan', 'images/brands/titan.png'),
(2, 'Fossil', 'images/brands/fossil.png'),
(3, 'Citizen', 'images/brands/citizen.png'),
(4, 'Seiko', 'images/brands/seiko.png'),
(5, 'Samsung', 'images/brands/samsung.png'),
(6, 'ColorFit', 'images/brands/colorfit.png'),
(7, 'Daniel Wellington', 'images/brands/daniel-wellington.png'),
(8, 'Timex', 'images/brands/timex.png'),
(9, 'Casio', 'images/brands/casio.png'),
(10, 'Battuta', 'images/brands/battuta.png')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `logo`=VALUES(`logo`);

-- -------------------------------------------------------
-- 4. Table: wishlist
-- Purpose: Store user saved/wishlisted watches
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NULL,
  `product_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_wishlist_product` (`product_id`),
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 5. Table: feedback
-- Purpose: Store user reviews and feedback messages
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `rating` INT(1) NOT NULL DEFAULT 5,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Demo Feedback
INSERT INTO `feedback` (`id`, `name`, `email`, `rating`, `message`) VALUES
(1, 'Aakash Patel', 'aakash@example.com', 5, 'Great collection of authentic watches! Delivery was on time and the Titan watch packaging was very secure.')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- -------------------------------------------------------
-- 5b. Table: reviews
-- Purpose: Store product-specific customer reviews and star ratings
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `rating` INT(1) NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_product_review` (`user_id`, `product_id`),
  KEY `fk_reviews_product` (`product_id`),
  KEY `fk_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 6. Table: orders
-- Purpose: Store customer order records
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT(11) NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(15) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `city` VARCHAR(50) NOT NULL,
  `pincode` VARCHAR(10) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'Paid (Demo)',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `order_status` VARCHAR(20) NOT NULL DEFAULT 'Placed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 7. Table: order_items
-- Purpose: Line items for customer orders
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_order_item_order` (`order_id`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
