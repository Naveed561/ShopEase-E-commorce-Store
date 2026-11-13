
<?php
$conn = new mysqli("localhost", "root", "", "shopease");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function ensurePasswordResetTable(mysqli $conn): void
{
    $schema = "CREATE TABLE IF NOT EXISTS password_resets (
        user_id INT NOT NULL PRIMARY KEY,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB";

    if (!$conn->query($schema)) {
        throw new RuntimeException('Could not create password reset table: ' . $conn->error);
    }
}
?>
