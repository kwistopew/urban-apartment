CREATE DATABASE IF NOT EXISTS apartment_rental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apartment_rental;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS apartment_images;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS admin_promotion_verifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS rentals;
DROP TABLE IF EXISTS rental_requests;
DROP TABLE IF EXISTS apartments;
DROP TABLE IF EXISTS tenants;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    reset_token VARCHAR(100) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    role ENUM('admin','tenant') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(30) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tenant_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE apartments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartment_number VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('Studio','1 Bedroom','2 Bedroom') NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    status ENUM('Available','Reserved','Occupied') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE apartment_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    apartment_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_apartment_image_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_apartment_images (apartment_id)
) ENGINE=InnoDB;

CREATE TABLE rental_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    apartment_id INT UNSIGNED NOT NULL,
    request_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_request_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_request_status (status),
    INDEX idx_request_tenant_status (tenant_id,status)
) ENGINE=InnoDB;

CREATE TABLE rentals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    apartment_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    next_due_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    status ENUM('Active','Ended') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rental_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rental_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_rental_tenant_status (tenant_id,status),
    INDEX idx_rental_apartment_status (apartment_id,status)
) ENGINE=InnoDB;

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rental_id INT UNSIGNED NOT NULL,
    tenant_id INT UNSIGNED NOT NULL,
    apartment_id INT UNSIGNED NOT NULL,
    rent_amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    payment_date DATE NOT NULL,
    early_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Early Payment','Paid','Overdue') NOT NULL,
    payment_method ENUM('Card','QR Payment','Cash') NOT NULL DEFAULT 'Cash',
    reference_number VARCHAR(80) DEFAULT NULL,
    received_at DATETIME DEFAULT NULL,
    received_by INT UNSIGNED DEFAULT NULL,
    receipt_sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_rental FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payment_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_payment_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_payment_tenant (tenant_id),
    INDEX idx_payment_status (status),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method),
    CONSTRAINT fk_payment_received_by FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_password_reset_active (user_id,used_at,expires_at)
) ENGINE=InnoDB;

CREATE TABLE admin_promotion_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requested_by INT UNSIGNED NOT NULL,
    target_user_id INT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_promo_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_admin_promo_target FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_admin_promo_lookup (requested_by,verified_at,expires_at)
) ENGINE=InnoDB;

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    sender_role ENUM('tenant','admin') NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_message_tenant_date (tenant_id,created_at),
    INDEX idx_message_admin_read (admin_id,is_read)
) ENGINE=InnoDB;

CREATE TABLE leave_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    rental_id INT UNSIGNED NOT NULL,
    apartment_id INT UNSIGNED NOT NULL,
    request_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    processed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_leave_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_leave_rental FOREIGN KEY (rental_id) REFERENCES rentals(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_leave_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_leave_status (status),
    INDEX idx_leave_tenant_status (tenant_id,status)
) ENGINE=InnoDB;

INSERT INTO users (username,email,password,role) VALUES
('admin','admin@apartment.local','$2y$12$chm25l8QYZfpo/E/LtdS5eOtvUkIX61cTFF8T.ab5qp0rzFEqjH5O','admin'),
('juan','juan@example.com','$2y$12$6oiKjRYTuad2MJSfnz/Dy.vG93Dv7GHcdglq/wVzt1dup5AOeW3xW','tenant'),
('maria','maria@example.com','$2y$12$6oiKjRYTuad2MJSfnz/Dy.vG93Dv7GHcdglq/wVzt1dup5AOeW3xW','tenant');

INSERT INTO tenants (user_id,full_name,contact_number,address) VALUES
((SELECT id FROM users WHERE username='juan'),'Juan Dela Cruz','09171234567','Pateros, Metro Manila'),
((SELECT id FROM users WHERE username='maria'),'Maria Santos','09181234567','Taguig City, Metro Manila');

INSERT INTO apartments (apartment_number,type,monthly_rent,status) VALUES
('A101','Studio',6000.00,'Available'),
('A102','1 Bedroom',8000.00,'Occupied'),
('A103','2 Bedroom',10000.00,'Available'),
('A104','1 Bedroom',8000.00,'Available'),
('A105','Studio',6000.00,'Available'),
('A106','2 Bedroom',10000.00,'Occupied');

INSERT INTO rentals (tenant_id,apartment_id,start_date,next_due_date,monthly_rent,status) VALUES
((SELECT id FROM tenants WHERE full_name='Juan Dela Cruz'),(SELECT id FROM apartments WHERE apartment_number='A102'),'2026-08-01','2026-09-01',8000.00,'Active');

INSERT INTO payments (rental_id,tenant_id,apartment_id,rent_amount,due_date,payment_date,early_discount,late_fee,total_amount,status) VALUES
((SELECT id FROM rentals WHERE tenant_id=(SELECT id FROM tenants WHERE full_name='Juan Dela Cruz') AND status='Active'),
 (SELECT id FROM tenants WHERE full_name='Juan Dela Cruz'),
 (SELECT id FROM apartments WHERE apartment_number='A102'),
 8000.00,'2026-08-05','2026-08-05',0.00,0.00,8000.00,'Paid');
