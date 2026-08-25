-- =============================================
-- NoteVault — Database Setup
-- Run this file once to initialize the database
-- =============================================

CREATE DATABASE IF NOT EXISTS notes_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE notes_website;

-- Subjects / Folders
CREATE TABLE IF NOT EXISTS folders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    icon        VARCHAR(100) DEFAULT '📁',
    color       VARCHAR(20)  DEFAULT '#667eea',
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Notes / Files
CREATE TABLE IF NOT EXISTS files (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    folder_id     INT NOT NULL,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    file_path     VARCHAR(500) DEFAULT NULL,
    external_link VARCHAR(500) DEFAULT NULL,
    file_type     ENUM('pdf','link','both') DEFAULT 'pdf',
    file_size     VARCHAR(50)  DEFAULT NULL,
    is_locked     TINYINT(1)   DEFAULT 0,      -- 0 = Unlocked (viewable), 1 = Locked
    downloads     INT          DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE
);

-- Admin Accounts
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin: username=admin  password=admin123  (CHANGE IN PRODUCTION!)
INSERT INTO admins (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- Sample Data
INSERT INTO folders (name, icon, color, description) VALUES
('Mathematics',      '📐', '#667eea', 'Algebra, Calculus, Statistics'),
('Physics',          '⚛️', '#f093fb', 'Mechanics, Optics, Thermodynamics'),
('Chemistry',        '🧪', '#4facfe', 'Organic, Inorganic, Physical Chemistry'),
('Computer Science', '💻', '#43e97b', 'Programming, Data Structures, Algorithms'),
('Biology',          '🧬', '#fa709a', 'Cell Biology, Genetics, Ecology');

INSERT INTO files (folder_id, title, description, external_link, file_type, is_locked) VALUES
(1, 'Calculus Basics',  'Introduction to limits and derivatives', 'https://example.com/calc.pdf',   'link', 0),
(4, 'Python Handbook',  'Complete Python programming guide',      'https://example.com/python.pdf', 'link', 1);
