# Quick Reference Card

One-page reference for php-mysql-database library.

## Setup

```php
use PhpMysqlDatabase\Database;

$db = new Database([
    'server' => 'localhost',
    'username' => 'user',
    'password' => 'pass',
    'database' => 'dbname'
]);
```

## The Golden Rule

```
query() → MySQL object → fetch() → array
```

**Never use query result as an array directly!**

## SELECT Queries

### Single Row
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
$user = $result->fetchAssoc();  // array or null

if ($user) {
    echo $user['name'];
}
```

### Multiple Rows
```php
$result = $db->query('SELECT * FROM users WHERE status = ?', ['active']);
$users = $result->fetchAll();  // array of arrays

foreach ($users as $user) {
    echo $user['name'];
}
```

### With JOIN
```php
$result = $db->query('
    SELECT u.*, p.title
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    WHERE u.id = ?
', [$id]);

$data = $result->fetchAll();
```

## INSERT

```php
$db->query(
    'INSERT INTO users (name, email) VALUES (?, ?)',
    ['John Doe', 'john@example.com']
);

$newId = $db->insert_id();
```

## UPDATE

```php
$db->query(
    'UPDATE users SET status = ? WHERE id = ?',
    ['active', 123]
);
```

## DELETE

```php
$db->query('DELETE FROM users WHERE id = ?', [123]);
```

## Fetch Methods

| Method | Returns | Use For |
|--------|---------|---------|
| `fetchAssoc()` | `array\|null` | Single row |
| `fetchAll()` | `array` | Multiple rows |
| `fetchRow()` | `array\|null` | Numeric array |
| `numRows()` | `int` | Count rows |

## Common Patterns

### Check Exists
```php
$result = $db->query('SELECT id FROM users WHERE email = ?', [$email]);
$exists = ($result->fetchAssoc() !== null);
```

### Get Count
```php
$result = $db->query('SELECT COUNT(*) as total FROM users');
$row = $result->fetchAssoc();
$count = $row['total'];
```

### Pagination
```php
$offset = ($page - 1) * $perPage;
$result = $db->query(
    'SELECT * FROM posts LIMIT ? OFFSET ?',
    [$perPage, $offset]
);
$posts = $result->fetchAll();
```

### Search
```php
$search = "%$term%";
$result = $db->query(
    'SELECT * FROM products WHERE name LIKE ?',
    [$search]
);
$products = $result->fetchAll();
```

## Error Handling

```php
use RestRouter\Exceptions\DBErrorException;
use RestRouter\Exceptions\ForeignKeyException;

try {
    $result = $db->query('SELECT * FROM users');
    $users = $result->fetchAll();
} catch (DBErrorException $e) {
    error_log($e->getMessage());
} catch (ForeignKeyException $e) {
    echo "Cannot delete: related data exists";
}
```

## Type Auto-Detection

Parameters are automatically typed:

```php
$id = 5;           // Detected as integer
$price = 99.99;    // Detected as float/double
$name = 'Product'; // Detected as string
$data = null;      // Detected as string (NULL safe)
```

## Two Query Methods

### Method 1: Direct (Recommended)
```php
$result = $db->query($sql, $params);
$data = $result->fetchAssoc();
```

### Method 2: Prepare/Execute
```php
$stmt = $db->prepare($sql);
$stmt = $db->bind_param($stmt, $params);
$result = $db->execute($stmt);
$data = $result->fetchAssoc();
```

Both return `MySQL` object → Must fetch!

## Common Mistakes

### ❌ Using Result as Array
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$name = $result['name'];  // FATAL ERROR
```

### ✅ Correct Way
```php
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
$name = $user['name'];
```

### ❌ Iterating Result Object
```php
$result = $db->query('SELECT * FROM users');
foreach ($result as $user) { ... }  // ERROR
```

### ✅ Correct Way
```php
$result = $db->query('SELECT * FROM users');
$users = $result->fetchAll();
foreach ($users as $user) { ... }
```

### ❌ Fetching Non-SELECT
```php
$result = $db->query('DELETE FROM users WHERE id = ?', [5]);
$deleted = $result->fetchAssoc();  // Wrong
```

### ✅ Correct Way
```php
$db->query('DELETE FROM users WHERE id = ?', [5]);
// No fetch needed for DELETE/UPDATE
```

## BaseModel Template

```php
class BaseModel {
    protected $db;
    protected $table;

    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->db->query($sql, [$id]);
        return $result ? $result->fetchAssoc() : null;
    }

    public function findAll() {
        $sql = "SELECT * FROM {$this->table}";
        $result = $this->db->query($sql, []);
        return $result ? $result->fetchAll() : [];
    }

    public function create($data) {
        $cols = implode(', ', array_keys($data));
        $vals = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($cols) VALUES ($vals)";

        $this->db->query($sql, array_values($data));
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $sets = [];
        $params = [];

        foreach ($data as $col => $val) {
            $sets[] = "$col = ?";
            $params[] = $val;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";

        $this->db->query($sql, $params);
        return true;
    }
}
```

## Remember

1. ✅ Always fetch after SELECT
2. ✅ `fetchAssoc()` for one row
3. ✅ `fetchAll()` for multiple rows
4. ✅ No fetch for INSERT/UPDATE/DELETE
5. ✅ Use `insert_id()` after INSERT
6. ✅ Parameters auto-typed
7. ✅ Always use `?` placeholders
8. ✅ Wrap in try-catch for errors

## Documentation

- **README.md** - Complete guide
- **EXAMPLES.md** - Real-world code
- **AI_AGENT_GUIDE.md** - Technical reference
- **MIGRATION_GUIDE.md** - From other libraries
- **QUICK_REFERENCE.md** - This file
