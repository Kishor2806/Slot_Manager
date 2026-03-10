CREATE DATABASE IF NOT EXISTS nexus_booking;
USE nexus_booking;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    zoho_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS whitelist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_or_domain VARCHAR(255) NOT NULL UNIQUE,
    type ENUM('email', 'domain') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    added_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS master_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    default_duration INT DEFAULT 60, -- in minutes
    color_code VARCHAR(20) DEFAULT '#3788d8',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    description TEXT,
    status ENUM('approved', 'pending', 'cancelled') DEFAULT 'pending',
    token VARCHAR(64) NULL,
    token_expiry DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES master_events(id) ON DELETE CASCADE
);

-- Default Super Admin for setup
INSERT INTO whitelist (email_or_domain, type, is_active) VALUES ('admin@example.com', 'email', 1) ON DUPLICATE KEY UPDATE is_active=1;

-- Default events
INSERT INTO master_events (title, default_duration, color_code) VALUES 
('Client Meeting', 60, '#28a745'),
('Team Training', 120, '#17a2b8'),
('AI Research', 90, '#6610f2'),
('Interview', 45, '#ffc107'),
('Workshop', 180, '#e83e8c'),
('Internal Review', 30, '#dc3545')
ON DUPLICATE KEY UPDATE title=VALUES(title);
