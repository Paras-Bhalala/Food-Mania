<?php
/**
 * Common Utility Functions
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Contains helper functions used across the application:
 * - Sanitization, redirection, authentication checks
 * - CSRF token handling, flash messages
 * - Cart helpers, formatting utilities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sanitize user input to prevent XSS
 * 
 * @param string $data Raw input string
 * @return string Sanitized string
 */
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a given URL
 * 
 * @param string $url Target URL
 * @return void
 */
function redirect(string $url): void {
    header("Location: $url");
    exit();
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if user session exists
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Check if an admin is logged in
 * 
 * @return bool True if admin session exists
 */
function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

/**
 * Require user login — redirects to login page if not authenticated
 * 
 * @return void
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect(SITE_URL . '/auth/login.php');
    }
}

/**
 * Require admin login — redirects to admin login if not authenticated
 * 
 * @return void
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        redirect(SITE_URL . '/admin/login.php');
    }
}

/**
 * Generate a CSRF token and store it in the session
 * 
 * @return string CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form submission
 * 
 * @param string $token Token from POST data
 * @return bool True if token is valid
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output hidden CSRF token field for forms
 * 
 * @return string HTML hidden input element
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Set a flash message in the session
 * 
 * @param string $type    Message type: 'success', 'error', 'warning', 'info'
 * @param string $message The message text
 * @return void
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Get and clear flash message from session
 * 
 * @return array|null Flash message array or null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message as Bootstrap alert HTML
 * 
 * @return string HTML for the flash alert
 */
function displayFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    
    $typeMap = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'info',
    ];
    
    $bsType = $typeMap[$flash['type']] ?? 'info';
    $message = sanitize($flash['message']);
    
    return '<div class="alert alert-' . $bsType . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Get the number of items in a user's cart
 * 
 * @param PDO $pdo     Database connection
 * @param int $userId  User ID
 * @return int Number of items in cart
 */
function getCartCount(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS count FROM cart WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Get all items in a user's cart with food details
 * 
 * @param PDO $pdo     Database connection
 * @param int $userId  User ID
 * @return array Cart items with food_item details
 */
function getCartItems(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT c.id AS cart_id, c.quantity, 
               f.id AS food_id, f.name, f.price, f.image, f.is_available,
               cat.name AS category_name
        FROM cart c
        JOIN food_items f ON c.food_item_id = f.id
        JOIN categories cat ON f.category_id = cat.id
        WHERE c.user_id = :user_id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Get cart total price
 * 
 * @param PDO $pdo     Database connection
 * @param int $userId  User ID
 * @return float Total price
 */
function getCartTotal(PDO $pdo, int $userId): float {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(c.quantity * f.price), 0) AS total
        FROM cart c
        JOIN food_items f ON c.food_item_id = f.id
        WHERE c.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    return (float) $stmt->fetchColumn();
}

/**
 * Format price in Indian Rupees
 * 
 * @param float $price Price value
 * @return string Formatted price string
 */
function formatPrice(float $price): string {
    return '₹' . number_format($price, 2);
}

/**
 * Format a date string
 * 
 * @param string $date   Date string from database
 * @param string $format PHP date format string
 * @return string Formatted date
 */
function formatDate(string $date, string $format = 'd M Y, h:i A'): string {
    return date($format, strtotime($date));
}

/**
 * Get Bootstrap badge HTML for an order status
 * 
 * @param string $status Order status value
 * @return string HTML badge element
 */
function getStatusBadge(string $status): string {
    $badges = [
        'pending'          => '<span class="badge bg-warning text-dark">Pending</span>',
        'confirmed'        => '<span class="badge bg-info">Confirmed</span>',
        'preparing'        => '<span class="badge bg-primary">Preparing</span>',
        'out_for_delivery' => '<span class="badge bg-orange">Out for Delivery</span>',
        'delivered'        => '<span class="badge bg-success">Delivered</span>',
        'cancelled'        => '<span class="badge bg-danger">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get the logged-in user's data
 * 
 * @param PDO $pdo Database connection
 * @return array|null User data or null
 */
function getLoggedInUser(PDO $pdo): ?array {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Get the logged-in admin's data
 * 
 * @param PDO $pdo Database connection
 * @return array|null Admin data or null
 */
function getLoggedInAdmin(PDO $pdo): ?array {
    if (!isAdmin()) return null;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Upload an image file safely
 * 
 * @param array  $file       $_FILES array element
 * @param string $uploadDir  Target directory for uploaded file
 * @return string|false  New filename on success, false on failure
 */
function uploadImage(array $file, string $uploadDir): string|false {
    // Allowed image types
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5 MB
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if (!in_array($file['type'], $allowedTypes)) return false;
    if ($file['size'] > $maxSize) return false;
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('img_', true) . '.' . strtolower($extension);
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Move uploaded file
    $destination = rtrim($uploadDir, '/') . '/' . $newName;
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $newName;
    }
    
    return false;
}

/**
 * Get the average rating for a food item
 * 
 * @param PDO $pdo    Database connection
 * @param int $foodId Food item ID
 * @return array ['average' => float, 'count' => int]
 */
function getFoodRating(PDO $pdo, int $foodId): array {
    $stmt = $pdo->prepare("
        SELECT COALESCE(AVG(rating), 0) AS average, COUNT(*) AS count
        FROM reviews WHERE food_item_id = :food_id
    ");
    $stmt->execute([':food_id' => $foodId]);
    $result = $stmt->fetch();
    return [
        'average' => round((float)$result['average'], 1),
        'count'   => (int)$result['count'],
    ];
}

/**
 * Render star rating HTML
 * 
 * @param float $rating Rating value (0-5)
 * @return string HTML stars
 */
function renderStars(float $rating): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $html;
}
