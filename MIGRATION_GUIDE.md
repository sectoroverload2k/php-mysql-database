# Migration Guide

Guide for migrating from other database libraries to php-mysql-database.

## From mysqli (Raw)

### Before (mysqli)
```php
$mysqli = new mysqli('localhost', 'user', 'pass', 'database');

$stmt = $mysqli->prepare('SELECT * FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
```

### After (php-mysql-database)
```php
$db = new Database([
    'server' => 'localhost',
    'username' => 'user',
    'password' => 'pass',
    'database' => 'database'
]);

$result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $result->fetchAssoc();
```

## From PDO

### Before (PDO)
```php
$pdo = new PDO('mysql:host=localhost;dbname=database', 'user', 'pass');

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
```

### After (php-mysql-database)
```php
$db = new Database([
    'server' => 'localhost',
    'username' => 'user',
    'password' => 'pass',
    'database' => 'database'
]);

$result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $result->fetchAssoc();
```

## From Doctrine DBAL

### Before (Doctrine)
```php
$conn = DriverManager::getConnection($params);

$users = $conn->fetchAllAssociative('SELECT * FROM users WHERE status = ?', ['active']);
```

### After (php-mysql-database)
```php
$db = new Database($config);

$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);
$users = $result->fetchAll();
```

## From Eloquent (Laravel)

### Before (Eloquent)
```php
$user = User::find($id);
$users = User::where('status', 'active')->get();
```

### After (php-mysql-database)
```php
// You need to write SQL, this is not an ORM
$result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $result->fetchAssoc();

$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);
$users = $result->fetchAll();
```

## Key Differences

### 1. Result Fetching Required

**Other libraries:** Often return arrays directly
```php
$users = $pdo->query('SELECT * FROM users')->fetchAll();  // One step
```

**php-mysql-database:** Returns MySQL object, then fetch
```php
$result = $db->query('SELECT * FROM users');  // Returns MySQL object
$users = $result->fetchAll();  // Two steps
```

### 2. Parameter Binding

**PDO:** Separate execute step
```php
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
```

**php-mysql-database:** Parameters in query call
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
```

### 3. Error Handling

**mysqli:** Check manually
```php
if ($mysqli->error) {
    echo $mysqli->error;
}
```

**php-mysql-database:** Throws exceptions
```php
try {
    $db->query('SELECT * FROM users');
} catch (DBErrorException $e) {
    echo $e->getMessage();
}
```

## Common Pitfalls When Migrating

### 1. Forgetting to Fetch

```php
// ❌ Wrong (PDO habits)
$users = $db->query('SELECT * FROM users');
foreach ($users as $user) { ... }  // ERROR

// ✅ Correct
$result = $db->query('SELECT * FROM users');
$users = $result->fetchAll();
foreach ($users as $user) { ... }
```

### 2. Expecting Array Access

```php
// ❌ Wrong (Doctrine habits)
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
echo $result['name'];  // ERROR

// ✅ Correct
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
echo $user['name'];
```

### 3. Wrong Fetch for Multiple Rows

```php
// ❌ Wrong (only gets first row)
$result = $db->query('SELECT * FROM users');
$users = $result->fetchAssoc();  // Returns single row

// ✅ Correct
$result = $db->query('SELECT * FROM users');
$users = $result->fetchAll();  // Returns all rows
```

## Conversion Cheat Sheet

| Task | PDO | mysqli | php-mysql-database |
|------|-----|--------|-------------------|
| Connect | `new PDO(...)` | `new mysqli(...)` | `new Database([...])` |
| Query with params | `execute([...])` | `bind_param()` | `query($sql, [...])` |
| Fetch one row | `fetch()` | `fetch_assoc()` | `fetchAssoc()` |
| Fetch all rows | `fetchAll()` | Loop `fetch_assoc()` | `fetchAll()` |
| Insert ID | `lastInsertId()` | `insert_id` | `insert_id()` |
| Row count | `rowCount()` | `num_rows` | `numRows()` |

## Tips for Smooth Migration

1. **Search and replace fetch methods:**
   - `->fetch(PDO::FETCH_ASSOC)` → `->fetchAssoc()`
   - `->fetchAll(PDO::FETCH_ASSOC)` → `->fetchAll()`

2. **Add fetch calls where missing:**
   Find: `$result = $db->query(`
   Check: Is there a `->fetch` call after?
   If not: Add `->fetchAssoc()` or `->fetchAll()`

3. **Update error handling:**
   Wrap queries in try-catch blocks

4. **Test incrementally:**
   Migrate one model/controller at a time

## Need More Help?

- See [README.md](README.md) for complete API documentation
- See [EXAMPLES.md](EXAMPLES.md) for real-world code patterns
- See [AI_AGENT_GUIDE.md](AI_AGENT_GUIDE.md) for detailed technical reference
