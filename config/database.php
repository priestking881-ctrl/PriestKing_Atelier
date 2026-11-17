<?php
if (!defined('PRIEST_BASE_URL')) {
    define('PRIEST_BASE_URL', '/priest');
}

function getDbConnection(): PDO
{
    $host = 'localhost';
    $db = 'priest_db';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage());
    }
}

function getBusinessSettings(): array
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT * FROM business_settings ORDER BY id DESC LIMIT 1');
        $settings = $stmt->fetch();

        return $settings ?: [
            'business_name' => 'PRIEST Atelier',
            'business_address' => 'Accra, Ghana',
            'contact_phone' => '+233 00 000 0000',
            'contact_email' => 'support@priest.local',
            'currency' => 'GHS',
            'measurement_unit' => 'cm',
        ];
    } catch (Throwable $e) {
        return [
            'business_name' => 'PRIEST Atelier',
            'business_address' => 'Accra, Ghana',
            'contact_phone' => '+233 00 000 0000',
            'contact_email' => 'support@priest.local',
            'currency' => 'GHS',
            'measurement_unit' => 'cm',
        ];
    }
}
