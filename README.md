# php-mysql-database

A lightweight PHP MySQL database wrapper with prepared statement support.

## Installation

```bash
composer require sectoroverload2k/php-mysql-database
```

## Basic Usage

```php
// Create a new database connection
$config = [
    'server' => 'localhost',
    'username' => 'username',
    'password' => 'password',
    'database' => 'database_name'
];

$db = new \PhpMysqlDatabase\Database($config);

// Basic query execution
$result = $db->query('SELECT * FROM users WHERE active = 1');

// Fetch all results
$users = $result->fetchAll();

// Query with parameters using prepared statement
$userId = 5;
$result = $db->query('SELECT * FROM users WHERE id = ?', [$userId]);
$user = $result->fetchAssoc();
```

## Prepared Statements

The library supports fully prepared statements with typed parameter binding.

### Basic Prepared Statement

```php
// Prepare statement
$stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND status = ?');

// Bind parameters and execute
$db->bind_param($stmt, ['john_doe', 'active']);
$result = $db->execute($stmt);

// Fetch the result
$user = $result->fetchAssoc();
```

### Explicitly Defining Parameter Types

```php
// Prepare statement
$stmt = $db->prepare('INSERT INTO products (name, price, in_stock) VALUES (?, ?, ?)');

// Bind parameters with explicit types: string, double, integer
$db->bind_param($stmt, ['Laptop', 999.99, 10], 'sdi');
$db->execute($stmt);

// Get the inserted ID
$productId = $db->insert_id();
```

### Parameter Types

When binding parameters, the following type identifiers are available:

- `i` - Integer
- `d` - Double (floating-point number)
- `s` - String
- `b` - Blob (binary data)

If you don't specify types, they will be automatically detected based on the PHP variable types.