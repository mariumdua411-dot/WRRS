CREATE DATABASE IF NOT EXISTS WRRSdb;
USE WRRSdb;

-- 1. USERS TABLE
-- Holds Citizens, Data Collectors, Technicians, and Managers
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NULL,
    password_hash VARCHAR(255) NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NULL,
    role ENUM('CITIZEN', 'DATA_COLLECTOR', 'TECHNICIAN', 'MANAGER') DEFAULT 'CITIZEN' NOT NULL,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE' NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
INSERT INTO users (user_id,username, password_hash,phone_number, role, status) 
VALUES (
    '7'
    'Data Collector One', 
    '0712345678', 
    'georgina1', 
    'georgina', 
    'DATA_COLLECTOR', 
    'ACTIVE'
);

-- 2. REPORTS TABLE
-- Links citizens directly to users table via citizen_id (NULL for guest reports)
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    citizen_id INT DEFAULT NULL,
    citizen_name VARCHAR(100) NOT NULL,
    citizen_phone VARCHAR(20) NOT NULL,
    problem_type ENUM('', 'METER', 'TANK', 'PUMP', 'VALVE', 'OTHER') NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT NULL,
    status ENUM('SUBMITTED', 'RECEIVED', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED') DEFAULT 'SUBMITTED' NOT NULL,
    priority ENUM('LOW', 'MEDIUM', 'HIGH', 'URGENT') DEFAULT NULL,
    assigned_technician_id INT DEFAULT NULL,
    data_collector_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reports_citizen FOREIGN KEY (citizen_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_technician FOREIGN KEY (assigned_technician_id) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_reports_collector FOREIGN KEY (data_collector_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. REPORT_WORK_LOGS TABLE
CREATE TABLE report_work_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    technician_id INT NOT NULL,
    previous_status ENUM('SUBMITTED', 'RECEIVED', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED') NOT NULL,
    new_status ENUM('SUBMITTED', 'RECEIVED', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED') NOT NULL,
    work_notes TEXT NOT NULL,
    proof_image_url VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_worklogs_report FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE,
    CONSTRAINT fk_worklogs_technician FOREIGN KEY (technician_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 4. REPORT_UPDATES TABLEALTER TABLE reports 
MODIFY COLUMN problem_type ENUM(
    'Pipe Leakage / Burst',
    'Water Contamination',
    'No Water Supply',
    'Low Water Pressure',
    'Illegal Connection',
    'Other Issue'
) NOT NULL;
CREATE TABLE report_updates (
    update_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    status ENUM( 'Pipe Leakage / Burst',
    'Water Contamination',
    'No Water Supply',
    'Low Water Pressure',
    'Illegal Connection',
    'Other Issue') NOT NULL,
    notes TEXT DEFAULT NULL,
    updated_by_user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_updates_report FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE,
    CONSTRAINT fk_updates_user FOREIGN KEY (updated_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT INTO users (full_name, phone_number, username, password_hash, role, status) 
VALUES (
    'Data Collector One', 
    '0712345678', 
    'georgina1', 
    'georgina', 
    'DATA_COLLECTOR', 
    'ACTIVE'
);