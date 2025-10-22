<?php
// Simple database setup script
// Run this file once to create the database and tables

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection without selecting a specific database first
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Setting up TechCompare Database...</h2>";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $statements = explode(';', $schema);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Database and tables created successfully!</p>";
    
    // Read and execute sample data
    $sampleData = file_get_contents(__DIR__ . '/sample_data.sql');
    $statements = explode(';', $sampleData);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "<p>✓ Sample data inserted successfully!</p>";
    echo "<p><strong>Setup completed!</strong></p>";
    echo "<p>You can now access your website at: <a href='../index.html'>../index.html</a></p>";
    echo "<p>Admin panel: <a href='../admin/'>../admin/</a> (admin/admin123)</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please make sure MySQL is running and the credentials are correct.</p>";
}
?>