-- ============================================================
-- Food-Mania Database Schema
-- Single-restaurant food ordering system
-- Run this SQL on MySQL via phpMyAdmin or CLI
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `food_mania` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `food_mania`;

-- ============================================================
-- 1. USERS TABLE
-- ============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `address` TEXT DEFAULT NULL,
    `status` ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. ADMINS TABLE
-- ============================================================
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. CATEGORIES TABLE
-- ============================================================
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. FOOD ITEMS TABLE
-- ============================================================
DROP TABLE IF EXISTS `food_items`;
CREATE TABLE `food_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_food_category` (`category_id`),
    CONSTRAINT `fk_food_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. CART TABLE
-- ============================================================
DROP TABLE IF EXISTS `cart`;
CREATE TABLE `cart` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `food_item_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_cart_user` (`user_id`),
    KEY `fk_cart_food` (`food_item_id`),
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_food` FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. ORDERS TABLE
-- ============================================================
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('cod','online') NOT NULL DEFAULT 'cod',
    `delivery_address` TEXT NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_order_user` (`user_id`),
    CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. ORDER ITEMS TABLE
-- ============================================================
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `food_item_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_oi_order` (`order_id`),
    KEY `fk_oi_food` (`food_item_id`),
    CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_oi_food` FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. CONTACT MESSAGES TABLE
-- ============================================================
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. REVIEWS TABLE
-- ============================================================
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `food_item_id` INT(11) NOT NULL,
    `rating` INT(1) NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `comment` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_review_user` (`user_id`),
    KEY `fk_review_food` (`food_item_id`),
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_review_food` FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin account (password: admin123)
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Admin', 'admin@foodmania.com', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQbF7s1OoQe1sKnLQq5lC1k1YFKzG.');

-- Test users (password: user123)
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `address`, `status`) VALUES
('Rahul Sharma', 'rahul@example.com', '9876543210', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQbF7s1OoQe1sKnLQq5lC1k1YFKzG.', '123 MG Road, Mumbai, Maharashtra 400001', 'active'),
('Priya Patel', 'priya@example.com', '9876543211', '$2y$10$8K1p/a0dL1LXMIgoEDFrwOfMQbF7s1OoQe1sKnLQq5lC1k1YFKzG.', '456 Brigade Road, Bangalore, Karnataka 560001', 'active');

-- Categories
INSERT INTO `categories` (`name`, `image`, `status`) VALUES
('Pizza', 'pizza.jpg', 'active'),
('Burgers', 'burgers.jpg', 'active'),
('Chinese', 'chinese.jpg', 'active'),
('Desserts', 'desserts.jpg', 'active'),
('Beverages', 'beverages.jpg', 'active'),
('Indian', 'indian.jpg', 'active');

-- Food Items
-- Pizza (category_id = 1)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(1, 'Margherita Pizza', 'Classic pizza topped with fresh mozzarella cheese, vine-ripened tomato sauce, and fragrant basil leaves on a perfectly crispy thin crust.', 299.00, 'margherita_pizza.jpg', 1),
(1, 'Pepperoni Pizza', 'Loaded with generous layers of spicy pepperoni slices, melted mozzarella cheese, and our signature marinara sauce on a hand-tossed crust.', 399.00, 'pepperoni_pizza.jpg', 1),
(1, 'BBQ Chicken Pizza', 'Tender grilled chicken pieces with smoky BBQ sauce, red onions, fresh cilantro, and a blend of mozzarella and cheddar cheese.', 449.00, 'bbq_chicken_pizza.jpg', 1);

-- Burgers (category_id = 2)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(2, 'Classic Beef Burger', 'Juicy hand-pressed beef patty with crisp lettuce, ripe tomato, pickles, onions, and our secret sauce in a toasted brioche bun.', 249.00, 'classic_burger.jpg', 1),
(2, 'Chicken Zinger Burger', 'Crispy fried chicken fillet with spicy mayo, fresh lettuce, and cheese in a soft sesame seed bun. A true crowd pleaser!', 229.00, 'chicken_zinger.jpg', 1),
(2, 'Veggie Supreme Burger', 'Crispy vegetable patty loaded with fresh lettuce, tomato, onion rings, jalapeños, and creamy cheese sauce.', 199.00, 'veggie_burger.jpg', 1);

-- Chinese (category_id = 3)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(3, 'Hakka Noodles', 'Stir-fried noodles tossed with colorful vegetables, soy sauce, and aromatic spices. A beloved Indo-Chinese classic!', 179.00, 'hakka_noodles.jpg', 1),
(3, 'Chicken Manchurian', 'Crispy chicken balls coated in a tangy, spicy Manchurian sauce with spring onions and bell peppers. Served with steamed rice.', 259.00, 'chicken_manchurian.jpg', 1),
(3, 'Veg Fried Rice', 'Fluffy basmati rice wok-tossed with fresh vegetables, eggs, soy sauce, and a hint of sesame oil.', 169.00, 'veg_fried_rice.jpg', 1);

-- Desserts (category_id = 4)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(4, 'Chocolate Lava Cake', 'Warm, rich chocolate cake with a molten chocolate center, served with a scoop of vanilla ice cream and fresh berries.', 199.00, 'chocolate_lava_cake.jpg', 1),
(4, 'Gulab Jamun', 'Soft, golden-brown milk dumplings soaked in aromatic cardamom-infused sugar syrup. Served warm (4 pieces).', 129.00, 'gulab_jamun.jpg', 1),
(4, 'Tiramisu', 'Classic Italian dessert with layers of espresso-soaked ladyfingers, mascarpone cream, and cocoa powder.', 249.00, 'tiramisu.jpg', 1);

-- Beverages (category_id = 5)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(5, 'Mango Lassi', 'Creamy, refreshing yogurt-based drink blended with ripe Alphonso mango pulp and a touch of cardamom.', 99.00, 'mango_lassi.jpg', 1),
(5, 'Cold Coffee', 'Rich, creamy cold coffee blended with ice cream, topped with whipped cream and chocolate shavings.', 149.00, 'cold_coffee.jpg', 1),
(5, 'Fresh Lime Soda', 'Refreshing lime juice with soda water, a pinch of salt, and mint leaves. Available sweet or salted.', 79.00, 'fresh_lime_soda.jpg', 1);

-- Indian (category_id = 6)
INSERT INTO `food_items` (`category_id`, `name`, `description`, `price`, `image`, `is_available`) VALUES
(6, 'Butter Chicken', 'Tender chicken pieces in a rich, creamy tomato-based gravy with butter, cream, and aromatic spices. Served with naan.', 349.00, 'butter_chicken.jpg', 1),
(6, 'Paneer Tikka', 'Marinated cottage cheese cubes grilled to perfection with bell peppers and onions, served with mint chutney.', 279.00, 'paneer_tikka.jpg', 1),
(6, 'Biryani', 'Fragrant basmati rice layered with tender meat, caramelized onions, saffron, and whole spices. Served with raita.', 299.00, 'biryani.jpg', 1);

-- Sample reviews
INSERT INTO `reviews` (`user_id`, `food_item_id`, `rating`, `comment`) VALUES
(1, 1, 5, 'Absolutely delicious! The crust was perfect and the cheese was so fresh.'),
(1, 4, 4, 'Great burger, juicy patty. Could use a bit more sauce though.'),
(2, 7, 5, 'Best Hakka noodles I have ever had! Perfect spice level.'),
(2, 10, 5, 'The chocolate lava cake is to die for! Must try!'),
(1, 16, 5, 'Authentic butter chicken! Reminds me of home cooking.'),
(2, 18, 4, 'Biryani was flavorful and aromatic. Generous portion size.');

-- Sample orders
INSERT INTO `orders` (`user_id`, `total_amount`, `status`, `payment_method`, `delivery_address`, `phone`, `created_at`) VALUES
(1, 748.00, 'delivered', 'cod', '123 MG Road, Mumbai, Maharashtra 400001', '9876543210', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(1, 449.00, 'delivered', 'online', '123 MG Road, Mumbai, Maharashtra 400001', '9876543210', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 528.00, 'preparing', 'cod', '456 Brigade Road, Bangalore, Karnataka 560001', '9876543211', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 349.00, 'pending', 'online', '456 Brigade Road, Bangalore, Karnataka 560001', '9876543211', NOW());

INSERT INTO `order_items` (`order_id`, `food_item_id`, `quantity`, `price`) VALUES
(1, 1, 1, 299.00),
(1, 2, 1, 399.00),
(1, 14, 1, 99.00),
(2, 3, 1, 449.00),
(3, 4, 1, 249.00),
(3, 18, 1, 299.00),
(4, 16, 1, 349.00);

COMMIT;
