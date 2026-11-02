CREATE TABLE IF NOT EXISTS internal_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_username VARCHAR(50) NOT NULL,
    sender_role VARCHAR(50) NOT NULL,
    receiver_username VARCHAR(50) NOT NULL,
    receiver_role VARCHAR(50) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message_body TEXT NOT NULL,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    FOREIGN KEY (sender_username) REFERENCES user(username) ON DELETE CASCADE,
    FOREIGN KEY (receiver_username) REFERENCES user(username) ON DELETE CASCADE
);