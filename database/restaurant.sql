-- ============================================================
--  AURA BISTRO — Online Restaurant Management System
--  AIUB | CSC 3215 Web Technologies | Group 08
--  Run this file ONCE to set up the full database.
--  After import, open: http://localhost/restaurant 2/setup_passwords.php
--  to set bcrypt passwords for all demo accounts.
-- ============================================================

DROP DATABASE IF EXISTS restaurant_db;
CREATE DATABASE restaurant_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_db;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  UNIQUE NOT NULL,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','manager','customer','kitchen') NOT NULL DEFAULT 'customer',
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    profile_pic VARCHAR(255)  DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── CATEGORIES ───────────────────────────────────────────────
CREATE TABLE categories (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- ── FOODS ────────────────────────────────────────────────────
CREATE TABLE foods (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL,
    image       VARCHAR(255)  DEFAULT NULL,
    available   TINYINT(1)    DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- ── RESTAURANT TABLES ────────────────────────────────────────
CREATE TABLE restaurant_tables (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    table_no VARCHAR(30) UNIQUE NOT NULL,
    seats    INT NOT NULL,
    status   ENUM('available','reserved','occupied','maintenance') DEFAULT 'available',
    location VARCHAR(100) DEFAULT NULL
);

-- ── RESERVATIONS ─────────────────────────────────────────────
CREATE TABLE reservations (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      INT  NOT NULL,
    table_id         INT  NOT NULL,
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    guests           INT  NOT NULL,
    status           ENUM('pending','approved','cancelled','waitlist') DEFAULT 'pending',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (table_id)    REFERENCES restaurant_tables(id) ON DELETE CASCADE
);

-- ── ORDERS ───────────────────────────────────────────────────
CREATE TABLE orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total       DECIMAL(10,2) DEFAULT 0,
    status      ENUM('pending','preparing','ready','completed','cancelled') DEFAULT 'pending',
    order_type  ENUM('dine-in','takeaway') DEFAULT 'dine-in',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── ORDER ITEMS ──────────────────────────────────────────────
CREATE TABLE order_items (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_id  INT NOT NULL,
    quantity INT NOT NULL,
    price    DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id)  REFERENCES foods(id)  ON DELETE CASCADE
);

-- ── REVIEWS ──────────────────────────────────────────────────
CREATE TABLE reviews (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    food_id     INT NOT NULL,
    rating      TINYINT NOT NULL,
    review      TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_review (customer_id, food_id),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id)     REFERENCES foods(id) ON DELETE CASCADE
);

-- ── INGREDIENTS ──────────────────────────────────────────────
CREATE TABLE ingredients (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    quantity      DECIMAL(10,2) DEFAULT 0,
    unit          VARCHAR(30)   NOT NULL,
    reorder_level DECIMAL(10,2) DEFAULT 0,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ── PAYMENTS ─────────────────────────────────────────────────
CREATE TABLE payments (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount   DECIMAL(10,2) NOT NULL,
    method   ENUM('cash','card','mobile') DEFAULT 'cash',
    status   ENUM('unpaid','paid') DEFAULT 'unpaid',
    paid_at  DATETIME NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ── INVOICES ─────────────────────────────────────────────────
CREATE TABLE invoices (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    subtotal   DECIMAL(10,2) NOT NULL,
    discount   DECIMAL(10,2) DEFAULT 0,
    total      DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ── VOUCHERS ─────────────────────────────────────────────────
CREATE TABLE vouchers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50) UNIQUE NOT NULL,
    discount_type    ENUM('percentage','fixed') DEFAULT 'fixed',
    discount_value   DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    valid_from       DATE NOT NULL,
    valid_to         DATE NOT NULL,
    status           ENUM('active','inactive') DEFAULT 'active'
);

-- ============================================================
--  SEED DATA
--  NOTE: Passwords are placeholder 'x' — run setup_passwords.php
--  after import to set real bcrypt hashes for all accounts.
-- ============================================================

INSERT INTO users (name, email, password, role, status) VALUES
('System Admin',       'admin@restaurant.com',    'x', 'admin',    'active'),
('Restaurant Manager', 'manager@restaurant.com',  'x', 'manager',  'active'),
('Kitchen Staff',      'kitchen@restaurant.com',  'x', 'kitchen',  'active'),
('Demo Customer',      'customer@restaurant.com', 'x', 'customer', 'active');

INSERT INTO categories (name) VALUES
('Pizza'), ('Burger'), ('Drinks'), ('Dessert'), ('Pasta'), ('Salad');

INSERT INTO foods (category_id, name, description, price, image, available) VALUES
(1, 'Chicken Pizza',        'Cheesy chicken pizza with fresh herbs',           450.00, 'chicken-pizza.jpg', 1),
(1, 'Beef Pizza',           'Spicy beef pizza with tomato sauce',              500.00, 'beef-pizza.jpg', 1),
(1, 'Margherita Pizza',     'Classic margherita with fresh basil',             380.00, 'margherita-pizza.jpg', 1),
(2, 'Chicken Burger',       'Crispy fried chicken with lettuce and mayo',      220.00, 'chicken-burger.jpg', 1),
(2, 'Beef Burger',          'Classic beef patty with caramelized onions',      250.00, 'beef-burger.jpg', 1),
(2, 'Veggie Burger',        'Grilled veggie patty with avocado',               200.00, 'veggie-burger.jpg', 1),
(3, 'Coca-Cola',            'Chilled classic cola',                             60.00, 'coca-cola.jpg', 1),
(3, 'Fresh Lemonade',       'Freshly squeezed with mint',                       80.00, 'fresh-lemonade.jpg', 1),
(3, 'Mango Smoothie',       'Blended mango with yogurt',                       100.00, 'mango-smoothie.jpg', 1),
(4, 'Chocolate Cake',       'Rich chocolate cake with ganache',                180.00, 'chocolate-cake.jpg', 1),
(4, 'Creme Brulee',         'Classic French creme brulee',                     200.00, 'creme-brulee.jpg', 1),
(5, 'Spaghetti Carbonara',  'Creamy pasta with pancetta and egg',              320.00, 'spaghetti-carbonara.jpg', 1),
(5, 'Penne Arrabbiata',     'Spicy tomato penne with garlic',                  280.00, 'penne-arrabbiata.jpg', 1),
(6, 'Caesar Salad',         'Romaine, croutons and caesar dressing',           180.00, 'caesar-salad.jpg', 1);

INSERT INTO restaurant_tables (table_no, seats, status, location) VALUES
('T-01', 2, 'available',   'Main Floor'),
('T-02', 4, 'available',   'Main Floor'),
('T-03', 4, 'available',   'Main Floor'),
('T-04', 6, 'available',   'Private Room'),
('T-05', 2, 'available',   'Rooftop'),
('T-06', 4, 'available',   'Rooftop'),
('T-07', 8, 'available',   'Banquet Hall'),
('T-08', 2, 'maintenance', 'Main Floor');

INSERT INTO ingredients (name, quantity, unit, reorder_level) VALUES
('Chicken',       20.00, 'kg',   5.00),
('Cheese',        10.00, 'kg',   2.00),
('Flour',         25.00, 'kg',   5.00),
('Tomato Sauce',  15.00, 'L',    3.00),
('Beef Patty',    30.00, 'pcs', 10.00),
('Pasta',         12.00, 'kg',   3.00),
('Eggs',          48.00, 'pcs', 12.00),
('Milk',           8.00, 'L',    2.00),
('Lettuce',       10.00, 'pcs',  3.00),
('Coca-Cola',     24.00, 'cans', 6.00);

INSERT INTO vouchers (code, discount_type, discount_value, min_order_amount, valid_from, valid_to, status) VALUES
('SAVE50',    'fixed',      50.00,  200.00, '2026-01-01', '2026-12-31', 'active'),
('BISTRO10',  'percentage', 10.00,  300.00, '2026-01-01', '2026-12-31', 'active'),
('WELCOME',   'fixed',     100.00,  500.00, '2026-01-01', '2026-12-31', 'active');
