CREATE DATABASE IF NOT EXISTS tms_db;
USE tms_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(50) NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'on_trip', 'maintenance') DEFAULT 'available'
);

-- 3. Drivers Table
CREATE TABLE IF NOT EXISTS drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    license_number VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- 4. Routes Table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_point VARCHAR(100) NOT NULL,
    end_point VARCHAR(100) NOT NULL,
    distance DECIMAL(10, 2),
    fare DECIMAL(10, 2)
);

-- 5. Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    vehicle_id INT,
    route_id INT,
    driver_id INT DEFAULT NULL,
    booking_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (driver_id) REFERENCES drivers(id)
);

-- 6. Payments Table (Ethiopian Mobile Money Support)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    user_id INT,
    amount DECIMAL(10, 2),
    payment_method ENUM('Telebirr', 'CBE Birr', 'E-Birr', 'HelloCash', 'Amole', 'CASH') DEFAULT 'CASH',
    transaction_id VARCHAR(100),
    status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert a default admin user (password is 'admin123' hashed)
INSERT IGNORE INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@tms.com', 'admin');

-- Default Demo Data
INSERT IGNORE INTO routes (start_point, end_point, distance, fare) VALUES 
('Addis Ababa', 'Bishoftu', 45, 150.00),
('Addis Ababa', 'Adama', 100, 350.00),
('Addis Ababa', 'Hawassa', 270, 800.00);

INSERT IGNORE INTO vehicles (vehicle_number, model, capacity) VALUES 
('AA-B123', 'Toyota Hiace', 12),
('AD-C456', 'Higer Bus', 45);
