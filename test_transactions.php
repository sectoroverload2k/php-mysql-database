<?php
/**
 * Transaction Support Verification Tests
 *
 * This file tests the newly implemented transaction support.
 * Replace the database credentials before running.
 */

require_once __DIR__ . '/vendor/autoload.php';

use PhpMysqlDatabase\Database;
use RestRouter\Exceptions\DBErrorException;

// Configure your test database
$config = [
    'server' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'database' => 'your_test_database'
];

try {
    echo "Connecting to database...\n";
    $db = new Database($config);
    echo "✓ Connected successfully\n\n";

    // Create test table
    echo "Creating test table...\n";
    $db->query('CREATE TABLE IF NOT EXISTS transaction_test (
        id INT AUTO_INCREMENT PRIMARY KEY,
        value VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )', []);
    echo "✓ Test table ready\n\n";

    // Test 1: Basic transaction with commit
    echo "Test 1: Basic transaction with commit\n";
    echo "----------------------------------------\n";
    var_dump($db->inTransaction()); // Should be false

    $db->beginTransaction();
    echo "Transaction started\n";
    var_dump($db->inTransaction()); // Should be true

    $db->query('INSERT INTO transaction_test (value) VALUES (?)', ['test1']);
    echo "Inserted test1\n";

    $db->commit();
    echo "Transaction committed\n";
    var_dump($db->inTransaction()); // Should be false

    $result = $db->query('SELECT * FROM transaction_test WHERE value = ?', ['test1']);
    $row = $result->fetchAssoc();
    if ($row) {
        echo "✓ Test 1 PASSED: Record exists after commit\n\n";
    } else {
        echo "✗ Test 1 FAILED: Record not found\n\n";
    }

    // Test 2: Rollback test
    echo "Test 2: Transaction with rollback\n";
    echo "----------------------------------------\n";
    $db->beginTransaction();
    echo "Transaction started\n";

    $db->query('INSERT INTO transaction_test (value) VALUES (?)', ['test2']);
    echo "Inserted test2\n";

    $db->rollback();
    echo "Transaction rolled back\n";

    $result = $db->query('SELECT * FROM transaction_test WHERE value = ?', ['test2']);
    $row = $result->fetchAssoc();
    if (!$row) {
        echo "✓ Test 2 PASSED: Record does not exist after rollback\n\n";
    } else {
        echo "✗ Test 2 FAILED: Record found (should have been rolled back)\n\n";
    }

    // Test 3: Error handling - commit without begin
    echo "Test 3: Error handling - commit without begin\n";
    echo "----------------------------------------\n";
    try {
        $db->commit();
        echo "✗ Test 3 FAILED: Should have thrown exception\n\n";
    } catch (DBErrorException $e) {
        echo "✓ Test 3 PASSED: Exception caught: " . $e->getMessage() . "\n\n";
    }

    // Test 4: Error handling - rollback without begin
    echo "Test 4: Error handling - rollback without begin\n";
    echo "----------------------------------------\n";
    try {
        $db->rollback();
        echo "✗ Test 4 FAILED: Should have thrown exception\n\n";
    } catch (DBErrorException $e) {
        echo "✓ Test 4 PASSED: Exception caught: " . $e->getMessage() . "\n\n";
    }

    // Test 5: Multiple operations in transaction
    echo "Test 5: Multiple operations in transaction\n";
    echo "----------------------------------------\n";
    $db->beginTransaction();

    $db->query('INSERT INTO transaction_test (value) VALUES (?)', ['multi1']);
    $id1 = $db->insert_id();
    echo "Inserted multi1 with ID: $id1\n";

    $db->query('INSERT INTO transaction_test (value) VALUES (?)', ['multi2']);
    $id2 = $db->insert_id();
    echo "Inserted multi2 with ID: $id2\n";

    $db->query('UPDATE transaction_test SET value = ? WHERE id = ?', ['multi1_updated', $id1]);
    echo "Updated multi1\n";

    $db->commit();
    echo "Transaction committed\n";

    $result = $db->query('SELECT * FROM transaction_test WHERE id IN (?, ?)', [$id1, $id2]);
    $rows = $result->fetchAll();
    if (count($rows) === 2 && $rows[0]['value'] === 'multi1_updated') {
        echo "✓ Test 5 PASSED: All operations committed successfully\n\n";
    } else {
        echo "✗ Test 5 FAILED: Operations not committed correctly\n\n";
    }

    // Test 6: Exception handling with rollback
    echo "Test 6: Exception handling with automatic rollback\n";
    echo "----------------------------------------\n";
    try {
        $db->beginTransaction();

        $db->query('INSERT INTO transaction_test (value) VALUES (?)', ['exception_test']);
        echo "Inserted exception_test\n";

        // Simulate an error condition
        throw new Exception("Simulated error");

        $db->commit();
    } catch (Exception $e) {
        echo "Caught exception: " . $e->getMessage() . "\n";
        if ($db->inTransaction()) {
            $db->rollback();
            echo "Transaction rolled back\n";
        }
    }

    $result = $db->query('SELECT * FROM transaction_test WHERE value = ?', ['exception_test']);
    $row = $result->fetchAssoc();
    if (!$row) {
        echo "✓ Test 6 PASSED: Record rolled back after exception\n\n";
    } else {
        echo "✗ Test 6 FAILED: Record found (should have been rolled back)\n\n";
    }

    // Cleanup
    echo "Cleaning up...\n";
    $db->query('DROP TABLE IF EXISTS transaction_test', []);
    echo "✓ Test table dropped\n\n";

    echo "========================================\n";
    echo "All tests completed!\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
