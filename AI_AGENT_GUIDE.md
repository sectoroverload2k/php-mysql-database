# AI Agent Guide: php-mysql-database Library

**Target Audience:** AI coding assistants, code generators, and automated development tools

This guide provides clear, unambiguous instructions for using the `php-mysql-database` library correctly.

## Critical Concepts

### 1. Return Type is NOT an Array

**⚠️ MOST IMPORTANT:** Query methods return a `MySQL` object, NOT an array.

```php
// ❌ WRONG - This is the #1 mistake
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$name = $result['name'];  // FATAL ERROR: Cannot use object of type MySQL as array

// ✅ CORRECT - Always fetch first
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();  // Returns array or null
$name = $user['name'];
```

### 2. Two Distinct Usage Patterns

#### Pattern A: Direct Query (Recommended)
```php
$result = $db->query($sql, $params);
```
- Returns: `MySQL` object (or null on error)
- Parameters automatically typed
- Most concise

#### Pattern B: Prepare/Bind/Execute
```php
$stmt = $db->prepare($sql);
$stmt = $db->bind_param($stmt, $params);
$result = $db->execute($stmt);
```
- Returns: `MySQL` object (or null on error)
- More verbose but reusable
- Explicit type control

**Both patterns return the same type: MySQL object**

### 3. Fetching Results

After executing a SELECT query, **you must fetch**:

```php
// Single row (returns array or null)
$row = $result->fetchAssoc();

// All rows (returns array of arrays or empty array)
$rows = $result->fetchAll();

// Numeric array (returns array or null)
$row = $result->fetchRow();
```

### 4. Non-SELECT Queries

INSERT, UPDATE, DELETE don't return data to fetch:

```php
// ✅ CORRECT
$db->query('DELETE FROM users WHERE id = ?', [5]);
// No fetch needed

$db->query('INSERT INTO users (name) VALUES (?)', ['John']);
$userId = $db->insert_id();  // Get auto-increment ID

$db->query('UPDATE users SET status = ? WHERE id = ?', ['active', 5]);
// No fetch needed
```

## Code Generation Templates

### SELECT Single Row

```php
$result = $db->query('SELECT * FROM {table} WHERE {column} = ?', [{value}]);
$row = $result ? $result->fetchAssoc() : null;

if ($row) {
    // Use $row['column_name']
} else {
    // Not found
}
```

### SELECT Multiple Rows

```php
$result = $db->query('SELECT * FROM {table} WHERE {column} = ?', [{value}]);
$rows = $result ? $result->fetchAll() : [];

foreach ($rows as $row) {
    // Use $row['column_name']
}
```

### INSERT

```php
$db->query(
    'INSERT INTO {table} ({columns}) VALUES ({placeholders})',
    [{values}]
);
$insertId = $db->insert_id();
```

### UPDATE

```php
$db->query(
    'UPDATE {table} SET {column} = ? WHERE {id_column} = ?',
    [{new_value}, {id}]
);
```

### DELETE

```php
$db->query('DELETE FROM {table} WHERE {column} = ?', [{value}]);
```

## BaseModel Pattern for ORMs

When building a base model class:

```php
class BaseModel {
    protected $db;
    protected $table;

    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $result = $this->db->query($sql, [$id]);

        // CRITICAL: Call fetchAssoc(), don't return $result directly
        return $result ? $result->fetchAssoc() : null;
    }

    public function findAll($where = []) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $column => $value) {
                $conditions[] = "$column = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->db->query($sql, $params);

        // CRITICAL: Call fetchAll(), don't return $result directly
        return $result ? $result->fetchAll() : [];
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        // INSERT doesn't return data to fetch
        $this->db->query($sql, array_values($data));

        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $sets[] = "$column = ?";
            $params[] = $value;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = ?";

        // UPDATE doesn't return data to fetch
        $this->db->query($sql, $params);

        return true;
    }
}
```

## Error Patterns to Detect

### Pattern 1: Using Result as Array

```php
// ❌ DETECT AND FIX
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$name = $result['name'];  // ERROR

// ✅ FIX TO
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
$name = $user['name'];
```

### Pattern 2: Returning Result Directly

```php
// ❌ DETECT AND FIX
public function findUser($id) {
    return $db->query('SELECT * FROM users WHERE id = ?', [$id]);  // Returns MySQL object!
}

// ✅ FIX TO
public function findUser($id) {
    $result = $db->query('SELECT * FROM users WHERE id = ?', [$id]);
    return $result ? $result->fetchAssoc() : null;  // Returns array or null
}
```

### Pattern 3: Fetching Non-SELECT Results

```php
// ❌ DETECT AND FIX
$result = $db->query('DELETE FROM users WHERE id = ?', [5]);
$deleted = $result->fetchAssoc();  // Wrong - DELETE returns nothing

// ✅ FIX TO
$db->query('DELETE FROM users WHERE id = ?', [5]);
// No fetch needed
```

### Pattern 4: Array Access on Result

```php
// ❌ DETECT AND FIX
$result = $db->query('SELECT * FROM users');
foreach ($result as $user) {  // ERROR: Can't iterate MySQL object
    echo $user['name'];
}

// ✅ FIX TO
$result = $db->query('SELECT * FROM users');
$users = $result->fetchAll();  // Get array first
foreach ($users as $user) {
    echo $user['name'];
}
```

### Pattern 5: Accessing Array Index on Result

```php
// ❌ DETECT AND FIX
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
if ($result[0]) {  // ERROR: Can't use array access on object
    // ...
}

// ✅ FIX TO
$result = $db->query('SELECT * FROM users WHERE id = ?', [5]);
$user = $result->fetchAssoc();
if ($user) {
    // ...
}
```

## Type Signatures for Reference

```typescript
// TypeScript-style signatures for clarity

class Database {
    query(sql: string, params: array = []): MySQL | null
    prepare(sql: string): MySQL | null
    bind_param(stmt: MySQL, params: mixed, types: string = null): MySQL
    execute(stmt: MySQL): MySQL
    insert_id(): int
}

class MySQL {
    fetchAssoc(): array | null        // Single row or null
    fetchAll(): array                 // Array of rows (empty if none)
    fetchRow(): array | null          // Numeric array or null
    numRows(): int                    // Count of rows
    numFields(): int                  // Count of columns
}
```

## Decision Tree

```
1. Is this a SELECT query?
   ├─ YES → Must call fetch method
   │         ├─ Single row? → result.fetchAssoc()
   │         └─ Multiple rows? → result.fetchAll()
   │
   └─ NO (INSERT/UPDATE/DELETE)
             ├─ INSERT? → Get insert_id() if needed
             └─ UPDATE/DELETE? → No action needed

2. Does variable hold result from query()?
   ├─ YES → It's a MySQL object, not an array
   │         └─ Must call fetch to get array
   │
   └─ NO → Check if it's already fetched data
             └─ Can use as array directly
```

## Quick Reference Table

| Query Type | Returns | Action Required | Get Data With |
|------------|---------|-----------------|---------------|
| SELECT | MySQL object | Fetch | `fetchAssoc()` or `fetchAll()` |
| INSERT | MySQL object | Get ID (optional) | `insert_id()` |
| UPDATE | MySQL object | None | N/A |
| DELETE | MySQL object | None | N/A |

## Testing Your Code

To verify correct usage:

```php
// This should work
$result = $db->query('SELECT * FROM users WHERE id = ?', [1]);
assert($result instanceof PhpMysqlDatabase\MySQL);  // TRUE

$user = $result->fetchAssoc();
assert(is_array($user) || is_null($user));  // TRUE

// This should fail
$result = $db->query('SELECT * FROM users WHERE id = ?', [1]);
$name = $result['name'];  // FATAL ERROR
```

## Summary for AI Code Generation

When generating code that uses `php-mysql-database`:

1. ✅ Always call `fetchAssoc()` or `fetchAll()` after SELECT queries
2. ✅ Never treat the result of `query()` as an array
3. ✅ Check for null before accessing fetched data
4. ✅ Don't fetch results from INSERT/UPDATE/DELETE
5. ✅ Use `insert_id()` after INSERT to get the new ID
6. ✅ Both `query()` and `prepare/bind_param/execute` return MySQL objects

**The golden rule:** `query()` → MySQL object → `fetch()` → array
