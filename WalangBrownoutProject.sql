-- Active: 1785947696841@@127.0.0.1@3306@walangbrownout
drop database WalangBrownout;
CREATE DATABASE  WalangBrownout;
USE WalangBrownout;

DROP TABLE IF EXISTS WBO_Notifications;
DROP TABLE IF EXISTS WBO_PurchaseOrders;
DROP TABLE IF EXISTS WBO_Transactions;
DROP TABLE IF EXISTS WBO_OrderDetails;
DROP TABLE IF EXISTS WBO_Orders;
DROP TABLE IF EXISTS WBO_Batches;
DROP TABLE IF EXISTS WBO_Products;
DROP TABLE IF EXISTS WBO_Suppliers;
DROP TABLE IF EXISTS WBO_Users;

CREATE TABLE WBO_Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
     role ENUM(
    'super_admin',
    'Operations_Manager',
    'Purchasing_Manager',
    'Warehouse_Admin',
    'Sales_Manager',
    'Purchasing_Staff',
    'Inventory_Controller',
    'Sales_Staff',
    'Warehouse_Staff',
    'System_User'
   ) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE WBO_Suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(100),
    lead_time_days INT NOT NULL DEFAULT 7
);

CREATE TABLE WBO_Products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100),
    supplier_id INT,
    abc_class ENUM('A', 'B', 'C') NOT NULL DEFAULT 'C',
    is_seasonal BOOLEAN NOT NULL DEFAULT FALSE,
    unit_cost DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES WBO_Suppliers(supplier_id)
);

CREATE TABLE WBO_Batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    quantity_received INT NOT NULL,
    current_quantity INT NOT NULL,
    received_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NULL,
    FOREIGN KEY (product_id) REFERENCES WBO_Products(product_id)
);

CREATE TABLE WBO_Orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    customer_contact VARCHAR(100),
    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'FULFILLED', 'UNFULFILLED', 'CANCELLED') NOT NULL DEFAULT 'PENDING'
);

CREATE TABLE WBO_OrderDetails (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES WBO_Orders(order_id),
    FOREIGN KEY (product_id) REFERENCES WBO_Products(product_id)
);

CREATE TABLE WBO_Transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    transaction_type ENUM('RECEIVE', 'SALE', 'RESERVE', 'ADJUSTMENT', 'WRITE_OFF') NOT NULL,
    quantity_change INT NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    order_id INT NULL,
    performed_by_user_id INT NOT NULL,
    FOREIGN KEY (batch_id) REFERENCES WBO_Batches(batch_id),
    FOREIGN KEY (order_id) REFERENCES WBO_Orders(order_id),
    FOREIGN KEY (performed_by_user_id) REFERENCES WBO_Users(user_id)
);

CREATE TABLE WBO_PurchaseOrders (
    po_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    status ENUM('DRAFT', 'ORDERED', 'RECEIVED', 'CANCELLED') NOT NULL DEFAULT 'DRAFT',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by_user_id INT NOT NULL,
    FOREIGN KEY (supplier_id) REFERENCES WBO_Suppliers(supplier_id),
    FOREIGN KEY (product_id) REFERENCES WBO_Products(product_id),
    FOREIGN KEY (created_by_user_id) REFERENCES WBO_Users(user_id)
);

CREATE TABLE WBO_Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    alert_tier ENUM('Yellow', 'Orange', 'Red', 'Expiry') NOT NULL,
    related_product_id INT,
    related_batch_id INT NULL,
    recipient_user_id INT NOT NULL,
    triggered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('UNREAD', 'ACKNOWLEDGED', 'RESOLVED') NOT NULL DEFAULT 'UNREAD',
    resolved_at DATETIME NULL,
    FOREIGN KEY (related_product_id) REFERENCES WBO_Products(product_id),
    FOREIGN KEY (related_batch_id) REFERENCES WBO_Batches(batch_id),
    FOREIGN KEY (recipient_user_id) REFERENCES WBO_Users(user_id)
);

INSERT INTO WBO_Users (name, email, password_hash, role) VALUES
('Jerome Raymundo', 'admin@wbo.ph', '$2y$10$EYIYoocFVd6VS8atOr1rL.zlneVWG9CaXTamfXraccqni5jAZRcC6', 'Admin'),
('Jessie Palarao', 'warehouse@wbo.ph', '$2y$10$pISxe0iqLFYHby3TFw3f6eUVqd1hdk1S3kjNnRC1tb1ik1cQZWTWC', 'Warehouse_Staff'),
('Jhon Paul Villasanta', 'hr@wbo.ph', '$2y$10$N0HAiY88zaPMelMpQIzudObZxeOYJSCI4JLcZnE/qQfgqQlUZz0ru', 'Operations_Manager'),
('Taironne James Sieteriales', 'staff@wbo.ph', '$2y$10$wBqzqIp0V.WlgUxgpq9rqOHa9WZuKLx1pYf4ND.F.7qdfLtBwS1wO', 'Purchasing_Manager');

INSERT INTO WBO_Suppliers (name, contact_number, email, lead_time_days) VALUES
('Global Appliance Supply', '0917-000-1111', 'supplier1@example.com', 7),
('Prime Trade Corp', '0917-000-2222', 'supplier2@example.com', 5);

INSERT INTO WBO_Products (sku, name, category, supplier_id, abc_class, is_seasonal, unit_cost, unit_price) VALUES
('SKU-1001', 'Premium Refrigerator', 'Appliances', 1, 'A', FALSE, 30000.00, 42000.00),
('SKU-1002', 'Industrial Fan', 'Cooling', 2, 'B', FALSE, 3800.00, 5200.00),
('SKU-1003', 'Water Heater', 'Heating', 1, 'C', TRUE, 5200.00, 7400.00);

INSERT INTO WBO_Batches (product_id, batch_number, quantity_received, current_quantity, received_date, expiry_date) VALUES
(1, 'BATCH-001', 30, 30, NOW(), NULL),
(2, 'BATCH-002', 50, 50, NOW(), NULL),
(3, 'BATCH-003', 20, 20, NOW(), '2027-12-31');