<?php
/**
 * ALMAF BAKERY - Authentication System
 * Dual-tier authentication: Admin (hardcoded) + Staff (password_hash/verify)
 * Uses PHP Sessions for RBAC (Role-Based Access Control)
 */

session_start();
require_once 'db.php';

// Define admin credentials (hardcoded for testing)
define('ADMIN_EMAIL', 'aliyu2k22@gmail.com');
define('ADMIN_PASSWORD', 'Aliyuyunus3971');

/**
 * Authenticate Admin User (Hardcoded)
 * 
 * @param string $email Admin email
 * @param string $password Admin password
 * @return array|false Returns user array on success, false on failure
 */
function authenticateAdmin($email, $password) {
    if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
        return [
            'id' => 'ADMIN_001',
            'name' => 'System Administrator',
            'email' => $email,
            'role' => 'Admin',
            'is_admin' => true
        ];
    }
    return false;
}

/**
 * Authenticate Staff User (Database + password_verify)
 * 
 * @param string $email Staff email
 * @param string $password Staff password (plaintext)
 * @return array|false Returns user array on success, false on failure
 */
function authenticateStaff($pdo, $email, $password) {
    try {
        $query = "SELECT id, uid, name, email, role, is_active FROM staff WHERE email = ? AND is_active = TRUE LIMIT 1";
        $stmt = executeQuery($pdo, $query, [$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        // Verify password using password_verify()
        $passwordQuery = "SELECT password_hash FROM staff WHERE id = ?";
        $passwordStmt = executeQuery($pdo, $passwordQuery, [$user['id']]);
        $result = $passwordStmt->fetch();
        
        if ($result && password_verify($password, $result['password_hash'])) {
            // Password matches
            return $user;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Staff authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Register new staff member
 * 
 * @param PDO $pdo Database connection
 * @param array $staffData Staff information
 * @return array|false Returns created user on success, false on failure
 */
function registerStaff($pdo, $staffData) {
    try {
        // Validate required fields
        $required = ['name', 'email', 'password', 'role', 'hire_date', 'daily_rate'];
        foreach ($required as $field) {
            if (empty($staffData[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Check if email already exists
        $checkQuery = "SELECT id FROM staff WHERE email = ?";
        $checkStmt = executeQuery($pdo, $checkQuery, [$staffData['email']]);
        if ($checkStmt->fetch()) {
            throw new Exception("Email already registered");
        }
        
        // Hash password securely
        $passwordHash = password_hash($staffData['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Generate unique UID
        $uid = generateUniqueID();
        
        // Insert new staff member
        $insertQuery = "INSERT INTO staff (uid, name, email, password_hash, role, contact_details, hire_date, bank_name, account_number, account_name, daily_rate, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)";
        
        executeQuery($pdo, $insertQuery, [
            $uid,
            $staffData['name'],
            $staffData['email'],
            $passwordHash,
            $staffData['role'],
            $staffData['contact_details'] ?? null,
            $staffData['hire_date'],
            $staffData['bank_name'] ?? null,
            $staffData['account_number'] ?? null,
            $staffData['account_name'] ?? null,
            $staffData['daily_rate']
        ]);
        
        return [
            'success' => true,
            'message' => 'Staff member registered successfully',
            'uid' => $uid
        ];
        
    } catch (Exception $e) {
        error_log("Staff registration error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Login Handler - Handles both Admin and Staff authentication
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array Status and message
 */
function handleLogin($email, $password) {
    global $pdo;
    
    try {
        // Try admin authentication first
        $admin = authenticateAdmin($email, $password);
        if ($admin) {
            $_SESSION['user'] = $admin;
            $_SESSION['role'] = 'Admin';
            $_SESSION['is_admin'] = true;
            $_SESSION['login_time'] = time();
            return [
                'success' => true,
                'message' => 'Admin login successful',
                'role' => 'Admin'
            ];
        }
        
        // Try staff authentication
        $staff = authenticateStaff($pdo, $email, $password);
        if ($staff) {
            $_SESSION['user'] = $staff;
            $_SESSION['role'] = $staff['role'];
            $_SESSION['is_admin'] = false;
            $_SESSION['login_time'] = time();
            
            return [
                'success' => true,
                'message' => 'Staff login successful',
                'role' => $staff['role']
            ];
        }
        
        // No matching user found
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred during login'
        ];
    }
}

/**
 * Check if user is authenticated
 * 
 * @return bool True if user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

/**
 * Check if user has required role
 * 
 * @param string|array $roles Required role(s)
 * @return bool True if user has required role
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? null;
    $roles = is_array($roles) ? $roles : [$roles];
    
    return in_array($userRole, $roles);
}

/**
 * Check if user is admin
 * 
 * @return bool True if user is admin
 */
function isAdmin() {
    return $_SESSION['is_admin'] ?? false;
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    session_start();
    $_SESSION = [];
    setcookie(session_name(), '', time() - 3600, '/');
}

/**
 * Require authentication - Redirect if not logged in
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

/**
 * Require specific role - Redirect if user doesn't have role
 */
function requireRole($roles) {
    if (!hasRole($roles)) {
        http_response_code(403);
        die('Access Denied. Insufficient permissions.');
    }
}

/**
 * Get current user info
 * 
 * @return array|null Current user data or null
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user role
 * 
 * @return string|null Current user role or null
 */
function getCurrentRole() {
    return $_SESSION['role'] ?? null;
}

?>