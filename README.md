# php-mysql-database

[![Latest Version](https://img.shields.io/packagist/v/sectoroverload2k/php-mysql-database.svg)](https://packagist.org/packages/sectoroverload2k/php-mysql-database)

A lightweight PHP MySQL database wrapper with prepared statement support and automatic parameter binding.

## Installation

```bash
composer require sectoroverload2k/php-mysql-database
```

## Quick Start

```php
use PhpMysqlDatabase\Database;

// Create connection
$db = new Database([
    'server' => 'localhost',
    'username' => 'username',
    'password' => 'password',
    'database' => 'database_name'
]);

// Simple query - returns MySQL object
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);

// YOU MUST CALL fetchAssoc() or fetchAll() to get the data
$user = $result->fetchAssoc();  // Returns single row as array
// OR
$users = $result->fetchAll();   // Returns all rows as array of arrays
```

## ⚠️ Important: Understanding Return Types

**KEY CONCEPT:** All query methods return a `MySQL` object, NOT an array. You must call fetch methods to retrieve data.

```php
// ❌ WRONG - This will cause errors
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$name = $result['name'];  // ERROR: Cannot use object as array

// ✅ CORRECT - Call fetch methods
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
$name = $user['name'];  // Works!
```

## Two Ways to Query

### Method 1: Simple Query (Recommended)

Pass parameters directly to `query()` - most concise and preferred method:

```php
// SELECT - single row
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();

// SELECT - multiple rows
$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);
$users = $result->fetchAll();

// INSERT
$db->query('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
$userId = $db->insert_id();

// UPDATE
$db->query('UPDATE users SET status = ? WHERE id = ?', ['inactive', 5]);

// DELETE
$db->query('DELETE FROM users WHERE id = ?', [5]);
```

### Method 2: Prepare/Bind/Execute (Advanced)

Use when you need more control or plan to execute the same query multiple times:

```php
// Prepare
$stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND status = ?');

// Bind parameters and execute
$stmt = $db->bind_param($stmt, ['john_doe', 'active']);
$result = $db->execute($stmt);

// Fetch data
$user = $result->fetchAssoc();
```

## Complete Examples

### Select Single Row

```php
$result = $db->query('SELECT * FROM users WHERE email = ?', ['user@example.com']);
$user = $result->fetchAssoc();

if ($user) {
    echo "Found user: " . $user['name'];
} else {
    echo "User not found";
}
```

### Select Multiple Rows

```php
$result = $db->query('SELECT * FROM products WHERE category = ?', ['electronics']);
$products = $result->fetchAll();

foreach ($products as $product) {
    echo $product['name'] . ': $' . $product['price'] . "\n";
}
```

### Insert with Auto-Increment ID

```php
$db->query(
    'INSERT INTO users (name, email, created_at) VALUES (?, ?, NOW())',
    ['Jane Doe', 'jane@example.com']
);

$userId = $db->insert_id();
echo "Created user with ID: $userId";
```

### Update Records

```php
$db->query(
    'UPDATE users SET last_login = NOW() WHERE id = ?',
    [123]
);
```

### Delete Records

```php
$db->query('DELETE FROM sessions WHERE expires_at < NOW()', []);
```

### Complex Query with Multiple Parameters

```php
$result = $db->query(
    'SELECT * FROM orders WHERE user_id = ? AND status = ? AND created_at > ?',
    [42, 'pending', '2024-01-01']
);

$orders = $result->fetchAll();
```

### Join Query

```php
$result = $db->query('
    SELECT u.name, u.email, p.title, p.created_at
    FROM users u
    INNER JOIN posts p ON u.id = p.user_id
    WHERE u.id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
', [5]);

$posts = $result->fetchAll();
```

## Fetch Methods

After executing a SELECT query, use these methods to retrieve data:

```php
$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);

// Fetch single row as associative array
$user = $result->fetchAssoc();
// Returns: ['id' => 1, 'name' => 'John', 'email' => 'john@example.com']
// Returns: null if no rows

// Fetch all rows as array of associative arrays
$users = $result->fetchAll();
// Returns: [
//   ['id' => 1, 'name' => 'John', ...],
//   ['id' => 2, 'name' => 'Jane', ...],
// ]
// Returns: [] if no rows

// Fetch single row as numeric array
$user = $result->fetchRow();
// Returns: [1, 'John', 'john@example.com']

// Get row count
$count = $result->numRows();

// Get column count
$cols = $result->numFields();
```

## Parameter Type Detection

Parameters are **automatically typed** based on PHP variable type:

```php
$id = 5;              // Auto-detected as integer (i)
$price = 99.99;       // Auto-detected as double (d)
$name = 'Product';    // Auto-detected as string (s)
$data = null;         // Auto-detected as string (s)

$db->query(
    'INSERT INTO products (id, name, price) VALUES (?, ?, ?)',
    [$id, $name, $price]
);
```

### Explicit Type Definition (Optional)

You can manually specify types if needed:

```php
$stmt = $db->prepare('INSERT INTO products (name, price, in_stock) VALUES (?, ?, ?)');
$stmt = $db->bind_param($stmt, ['Laptop', 999.99, 10], 'sdi');
$db->execute($stmt);
```

**Type identifiers:**
- `i` - Integer
- `d` - Double (floating-point)
- `s` - String
- `b` - Blob (binary data)

## Error Handling

The library throws exceptions on database errors:

```php
try {
    $result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
    $user = $result->fetchAssoc();
} catch (RestRouter\Exceptions\DBErrorException $e) {
    echo "Database error: " . $e->getMessage();
} catch (RestRouter\Exceptions\ForeignKeyException $e) {
    echo "Foreign key constraint: " . $e->getMessage();
}
```

## Common Patterns

### Check if Record Exists

```php
$result = $db->query('SELECT id FROM users WHERE email = ?', ['test@example.com']);
$exists = ($result->fetchAssoc() !== null);

if ($exists) {
    echo "Email already registered";
}
```

### Count Records

```php
$result = $db->query('SELECT COUNT(*) as count FROM users WHERE status = ?', ['active']);
$row = $result->fetchAssoc();
$count = $row['count'];
```

### Pagination

```php
$page = 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$result = $db->query(
    'SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?',
    [$perPage, $offset]
);

$posts = $result->fetchAll();
```

### Conditional Queries

```php
$sql = 'SELECT * FROM products WHERE 1=1';
$params = [];

if (!empty($category)) {
    $sql .= ' AND category = ?';
    $params[] = $category;
}

if (!empty($minPrice)) {
    $sql .= ' AND price >= ?';
    $params[] = $minPrice;
}

$result = $db->query($sql, $params);
$products = $result->fetchAll();
```

## Transaction Support

Wrap multiple queries in a transaction to ensure atomicity - either all operations succeed or all fail.

### Basic Transaction

```php
try {
    $db->beginTransaction();

    $db->query('INSERT INTO orders (user_id, total) VALUES (?, ?)', [123, 99.99]);
    $orderId = $db->insert_id();

    $db->query('INSERT INTO order_items (order_id, product_id) VALUES (?, ?)',
               [$orderId, 456]);

    $db->commit();
} catch (RestRouter\Exceptions\DBErrorException $e) {
    $db->rollback();
    echo "Transaction failed: " . $e->getMessage();
}
```

### Money Transfer Example

```php
try {
    $db->beginTransaction();

    // Deduct from sender
    $db->query('UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND balance >= ?',
               [100.00, $fromUser, 100.00]);

    // Add to receiver
    $db->query('UPDATE wallets SET balance = balance + ? WHERE user_id = ?',
               [100.00, $toUser]);

    // Log transaction
    $db->query('INSERT INTO transactions (from_user, to_user, amount) VALUES (?, ?, ?)',
               [$fromUser, $toUser, 100.00]);

    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

### Transaction Methods

#### `beginTransaction(): bool`
Start a new transaction.

**Returns:** True on success
**Throws:** DBErrorException on failure

#### `commit(): bool`
Commit the current transaction.

**Returns:** True on success
**Throws:** DBErrorException on failure or if no transaction is active

#### `rollback(): bool`
Rollback the current transaction.

**Returns:** True on success
**Throws:** DBErrorException on failure or if no transaction is active

#### `inTransaction(): bool`
Check if currently in a transaction.

**Returns:** True if in transaction, false otherwise

### Transaction Best Practices

1. **Always use try-catch** - Catch exceptions and rollback on errors
2. **Commit explicitly** - Don't rely on auto-commit
3. **Keep transactions short** - Minimize lock time on tables
4. **Handle all errors** - Always rollback on any exception
5. **Check state when needed** - Use `inTransaction()` if transaction state is uncertain

## API Reference

### Database Class

#### `__construct(array $config)`
Creates database connection.

**Config array:**
- `server` - Database host
- `username` - Database user
- `password` - Database password
- `database` - Database name

#### `query(string $sql, array $params = []): MySQL|null`
Execute a query with optional parameters.

**Returns:** MySQL object on success, null on failure
**Throws:** DBErrorException, ForeignKeyException

#### `prepare(string $sql): MySQL|null`
Prepare a statement for execution.

**Returns:** MySQL object on success, null on failure

#### `bind_param(MySQL $stmt, mixed $params, string $types = null): MySQL`
Bind parameters to prepared statement.

**Returns:** MySQL object

#### `execute(MySQL $stmt): MySQL`
Execute a prepared statement.

**Returns:** MySQL object with results

#### `insert_id(): int`
Get the last inserted auto-increment ID.

**Returns:** Integer ID

#### `beginTransaction(): bool`
Start a database transaction.

**Returns:** True on success
**Throws:** DBErrorException

#### `commit(): bool`
Commit the current transaction.

**Returns:** True on success
**Throws:** DBErrorException

#### `rollback(): bool`
Rollback the current transaction.

**Returns:** True on success
**Throws:** DBErrorException

#### `inTransaction(): bool`
Check if currently in a transaction.

**Returns:** Boolean transaction state

### MySQL Class (Result Object)

#### `fetchAssoc(): array|null`
Fetch single row as associative array.

**Returns:** Array on success, null if no more rows

#### `fetchAll(): array`
Fetch all remaining rows as array of associative arrays.

**Returns:** Array of arrays (empty array if no rows)

#### `fetchRow(): array|null`
Fetch single row as numeric array.

**Returns:** Array on success, null if no more rows

#### `numRows(): int`
Get number of rows in result set.

**Returns:** Integer count

#### `numFields(): int`
Get number of columns in result set.

**Returns:** Integer count

## Best Practices

1. **Always use parameterized queries** - Never concatenate user input into SQL
2. **Always fetch results** - Call `fetchAssoc()` or `fetchAll()` on SELECT queries
3. **Check for null** - `fetchAssoc()` returns null when no rows found
4. **Use try-catch** - Catch database exceptions in production
5. **Close connections** - Call `$db->disconnect()` when done (optional, happens automatically)

## Common Mistakes to Avoid

❌ **Don't use result object as array:**
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$name = $result['name'];  // ERROR!
```

✅ **Do fetch the data first:**
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
$name = $user['name'];  // Correct!
```

❌ **Don't forget parameters for INSERT/UPDATE/DELETE:**
```php
$result = $db->query('DELETE FROM users WHERE id = ?', [5]);
$deleted = $result->fetchAssoc();  // Wrong - no results from DELETE
```

✅ **INSERT/UPDATE/DELETE don't return data:**
```php
$db->query('DELETE FROM users WHERE id = ?', [5]);
// No fetch needed - deletion complete
```

## License

MIT License

## Contributing

Pull requests are welcome. For major changes, please open an issue first.

## Support

For issues and questions, please use the GitHub issue tracker.
