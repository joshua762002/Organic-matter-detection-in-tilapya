CREATE DATABASE IF NOT EXISTS organic_tilapia;
USE organic_tilapia;

CREATE TABLE users (
user_id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50) NOT NULL UNIQUE,
password VARCHAR(255) NOT NULL,
full_name VARCHAR(100),
role ENUM('admin','staff') DEFAULT 'staff',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO users (username, password, full_name, role)
VALUES
('admin', '1234', 'System Administrator', 'admin'),
('staff1', '1234', 'Juan Dela Cruz', 'staff');


CREATE TABLE detections (
detection_id INT AUTO_INCREMENT PRIMARY KEY,
sample_code VARCHAR(50) NOT NULL,
organic_level DECIMAL(5,2) NOT NULL,
water_temperature DECIMAL(5,2),
ph_level DECIMAL(4,2),
status VARCHAR(50),
created_by INT,
detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (created_by) REFERENCES users(user_id)
);


INSERT INTO detections (sample_code, organic_level, water_temperature, ph_level, status, created_by)
VALUES
('SAMPLE-001', 35.50, 28.4, 7.5, 'Normal', 1),
('SAMPLE-002', 78.20, 30.1, 6.8, 'High Organic Matter', 1),
('SAMPLE-003', 45.10, 27.9, 7.2, 'Moderate', 2);



CREATE TABLE alerts (
alert_id INT AUTO_INCREMENT PRIMARY KEY,
detection_id INT NOT NULL,
alert_message VARCHAR(255),
alert_level VARCHAR(50),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (detection_id) REFERENCES detections(detection_id)
);


INSERT INTO alerts (detection_id, alert_message, alert_level)
VALUES
(2, 'High Organic Matter detected in SAMPLE-002. Water change recommended.', 'High');


CREATE TABLE activity_logs (
log_id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
action TEXT,
log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id)
);


INSERT INTO activity_logs (user_id, action)
VALUES
(1, 'Admin logged into the system'),
(2, 'Staff logged into the system'),
(2, 'Staff created a new detection record'),
(1, 'Admin viewed dashboard');
