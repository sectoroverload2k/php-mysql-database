# Real-World Examples

Practical examples of using php-mysql-database in production applications.

## Table of Contents
- [CRUD Operations](#crud-operations)
- [User Authentication](#user-authentication)
- [Blog System](#blog-system)
- [E-commerce](#e-commerce)
- [API Responses](#api-responses)

## CRUD Operations

### Complete User Management

```php
class UserModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create
    public function create($data) {
        $this->db->query(
            'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())',
            [$data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT)]
        );

        return $this->db->insert_id();
    }

    // Read - single
    public function findById($id) {
        $result = $this->db->query('SELECT * FROM users WHERE id = ?', [$id]);
        return $result->fetchAssoc();  // Returns null if not found
    }

    // Read - multiple
    public function findAll($limit = 100) {
        $result = $this->db->query('SELECT * FROM users ORDER BY created_at DESC LIMIT ?', [$limit]);
        return $result->fetchAll();  // Returns [] if empty
    }

    // Update
    public function update($id, $data) {
        $this->db->query(
            'UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?',
            [$data['name'], $data['email'], $id]
        );

        return $this->findById($id);
    }

    // Delete
    public function delete($id) {
        $this->db->query('DELETE FROM users WHERE id = ?', [$id]);
    }
}
```

## User Authentication

### Login System

```php
class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function login($email, $password) {
        // Find user by email
        $result = $this->db->query('SELECT * FROM users WHERE email = ?', [$email]);
        $user = $result->fetchAssoc();

        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }

        // Update last login
        $this->db->query('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);

        // Create session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        return ['success' => true, 'user' => $user];
    }

    public function register($data) {
        // Check if email exists
        $result = $this->db->query('SELECT id FROM users WHERE email = ?', [$data['email']]);

        if ($result->fetchAssoc()) {
            return ['success' => false, 'error' => 'Email already registered'];
        }

        // Create user
        $this->db->query(
            'INSERT INTO users (name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())',
            [$data['name'], $data['email'], password_hash($data['password'], PASSWORD_BCRYPT)]
        );

        return ['success' => true, 'user_id' => $this->db->insert_id()];
    }
}
```

## Blog System

### Post Management with Relations

```php
class BlogModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get post with author info
    public function getPost($id) {
        $result = $this->db->query('
            SELECT
                p.*,
                u.name as author_name,
                u.email as author_email,
                COUNT(c.id) as comment_count
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            LEFT JOIN comments c ON p.id = c.post_id
            WHERE p.id = ?
            GROUP BY p.id
        ', [$id]);

        return $result->fetchAssoc();
    }

    // Get posts with pagination
    public function getPosts($page = 1, $perPage = 10, $category = null) {
        $offset = ($page - 1) * $perPage;

        $sql = '
            SELECT
                p.*,
                u.name as author_name,
                c.name as category_name
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = ?
        ';

        $params = ['published'];

        if ($category) {
            $sql .= ' AND p.category_id = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY p.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $perPage;
        $params[] = $offset;

        $result = $this->db->query($sql, $params);
        return $result->fetchAll();
    }

    // Create post with tags
    public function createPost($data) {
        // Insert post
        $this->db->query('
            INSERT INTO posts (user_id, title, content, category_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', [
            $data['user_id'],
            $data['title'],
            $data['content'],
            $data['category_id'],
            $data['status']
        ]);

        $postId = $this->db->insert_id();

        // Add tags
        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tagId) {
                $this->db->query(
                    'INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)',
                    [$postId, $tagId]
                );
            }
        }

        return $postId;
    }

    // Search posts
    public function searchPosts($query) {
        $searchTerm = "%$query%";

        $result = $this->db->query('
            SELECT DISTINCT p.*
            FROM posts p
            WHERE p.status = ?
            AND (p.title LIKE ? OR p.content LIKE ?)
            ORDER BY p.created_at DESC
            LIMIT 50
        ', ['published', $searchTerm, $searchTerm]);

        return $result->fetchAll();
    }
}
```

## E-commerce

### Order Processing

```php
class OrderModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Create order with items
    public function createOrder($userId, $items, $shippingAddress) {
        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Create order
        $this->db->query('
            INSERT INTO orders (
                user_id, total_amount, status,
                shipping_address, created_at
            ) VALUES (?, ?, ?, ?, NOW())
        ', [$userId, $total, 'pending', json_encode($shippingAddress)]);

        $orderId = $this->db->insert_id();

        // Add order items
        foreach ($items as $item) {
            $this->db->query('
                INSERT INTO order_items (
                    order_id, product_id, quantity,
                    price, subtotal
                ) VALUES (?, ?, ?, ?, ?)
            ', [
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity']
            ]);

            // Update inventory
            $this->db->query('
                UPDATE products
                SET stock = stock - ?
                WHERE id = ?
            ', [$item['quantity'], $item['product_id']]);
        }

        return $orderId;
    }

    // Get order details
    public function getOrderDetails($orderId) {
        // Get order
        $result = $this->db->query('
            SELECT o.*, u.name as customer_name, u.email as customer_email
            FROM orders o
            INNER JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ', [$orderId]);

        $order = $result->fetchAssoc();

        if (!$order) {
            return null;
        }

        // Get order items
        $result = $this->db->query('
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            INNER JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ', [$orderId]);

        $order['items'] = $result->fetchAll();

        return $order;
    }
}
```

## API Responses

### RESTful API Endpoints

```php
class ApiController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // GET /api/users
    public function getUsers() {
        $result = $this->db->query('
            SELECT id, name, email, created_at
            FROM users
            WHERE status = ?
            ORDER BY created_at DESC
            LIMIT 100
        ', ['active']);

        $users = $result->fetchAll();

        return $this->jsonResponse([
            'success' => true,
            'data' => $users,
            'count' => count($users)
        ]);
    }

    // GET /api/users/:id
    public function getUser($id) {
        $result = $this->db->query('
            SELECT id, name, email, created_at, last_login
            FROM users
            WHERE id = ?
        ', [$id]);

        $user = $result->fetchAssoc();

        if (!$user) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'User not found'
            ], 404);
        }

        return $this->jsonResponse([
            'success' => true,
            'data' => $user
        ]);
    }

    // POST /api/users
    public function createUser($data) {
        // Validate
        if (empty($data['email']) || empty($data['name'])) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Email and name are required'
            ], 400);
        }

        // Check duplicate
        $result = $this->db->query('SELECT id FROM users WHERE email = ?', [$data['email']]);
        if ($result->fetchAssoc()) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Email already exists'
            ], 409);
        }

        // Create
        $this->db->query('
            INSERT INTO users (name, email, created_at)
            VALUES (?, ?, NOW())
        ', [$data['name'], $data['email']]);

        $userId = $this->db->insert_id();

        // Get created user
        $result = $this->db->query('SELECT * FROM users WHERE id = ?', [$userId]);
        $user = $result->fetchAssoc();

        return $this->jsonResponse([
            'success' => true,
            'data' => $user
        ], 201);
    }

    private function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }
}
```

## Advanced Patterns

### Transaction-like Operations

```php
// While this library doesn't have explicit transaction methods,
// you can structure operations carefully:

class TransferService {
    private $db;

    public function transferFunds($fromUserId, $toUserId, $amount) {
        try {
            // Deduct from sender
            $this->db->query('
                UPDATE wallets
                SET balance = balance - ?
                WHERE user_id = ? AND balance >= ?
            ', [$amount, $fromUserId, $amount]);

            // Add to receiver
            $this->db->query('
                UPDATE wallets
                SET balance = balance + ?
                WHERE user_id = ?
            ', [$amount, $toUserId]);

            // Log transaction
            $this->db->query('
                INSERT INTO transactions (from_user, to_user, amount, created_at)
                VALUES (?, ?, ?, NOW())
            ', [$fromUserId, $toUserId, $amount]);

            return ['success' => true];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
```

### Batch Operations

```php
class BatchImport {
    private $db;

    public function importUsers($csvData) {
        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                // Check if exists
                $result = $this->db->query('SELECT id FROM users WHERE email = ?', [$row['email']]);

                if ($result->fetchAssoc()) {
                    $errors[] = "Row $index: Email {$row['email']} already exists";
                    continue;
                }

                // Insert
                $this->db->query('
                    INSERT INTO users (name, email, created_at)
                    VALUES (?, ?, NOW())
                ', [$row['name'], $row['email']]);

                $imported++;

            } catch (Exception $e) {
                $errors[] = "Row $index: " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}
```

## Testing Patterns

### Mock Database for Tests

```php
class UserModelTest extends PHPUnit\Framework\TestCase {
    private $db;
    private $userModel;

    protected function setUp(): void {
        $this->db = new Database([
            'server' => 'localhost',
            'username' => 'test',
            'password' => 'test',
            'database' => 'test_db'
        ]);

        $this->userModel = new UserModel($this->db);

        // Clean up
        $this->db->query('DELETE FROM users WHERE email LIKE ?', ['test%']);
    }

    public function testCreateUser() {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $userId = $this->userModel->create($data);

        $this->assertIsNumeric($userId);
        $this->assertGreaterThan(0, $userId);

        $user = $this->userModel->findById($userId);
        $this->assertEquals('Test User', $user['name']);
        $this->assertEquals('test@example.com', $user['email']);
    }
}
```
