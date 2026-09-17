CREATE DATABASE IF NOT EXISTS eumsolution
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eumsolution;

CREATE TABLE IF NOT EXISTS contact_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    company VARCHAR(100) NULL,
    phone VARCHAR(30) NOT NULL,
    email VARCHAR(255) NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'contacted', 'closed') NOT NULL DEFAULT 'new',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_contact_requests_status_created (status, created_at)
) ENGINE=InnoDB;
