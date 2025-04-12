<?php

$host    = 'localhost';
$db      = 'db';
$user    = 'db';
$pass    = 'db';
$charset = 'utf8mb4';

$dsn     = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO('mysql:host=db;port=3306;dbname=db', 'db', 'db');

    $username = 'newadmin2';
    $email    = 'newadmin2@mautic.local';
    $password = password_hash('Pine@Tundra42_Moonlight', PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("
        INSERT INTO users 
            (role_id, is_published, username, password, first_name, last_name, email, date_added, preferences) 
        VALUES 
            (1, 1, :username, :password, 'Super', 'Admin', :email, NOW(), 'a:0:{}')
    ");

    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':email'    => $email,
    ]);

    echo "✅ Admin user created successfully!\n";
} catch (Exception $e) {
    echo '❌ Error: '.$e->getMessage();
}
