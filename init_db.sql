CREATE DATABASE IF NOT EXISTS book_scanner;
USE book_scanner;
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    author VARCHAR(255),
    publisher VARCHAR(255),
    image_url TEXT,
    description TEXT,
    isbn VARCHAR(20),
    published_date VARCHAR(50),
    official_cover_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_isbn (isbn),
    INDEX idx_created_at (created_at)
);
