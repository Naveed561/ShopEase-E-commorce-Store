CREATE TABLE IF NOT EXISTS password_resets (
    user_id INT NOT NULL PRIMARY KEY,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;
