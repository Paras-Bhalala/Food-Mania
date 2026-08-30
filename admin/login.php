<?php
/**
 * Admin Login
 * Food-Mania - Separate admin authentication
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Session is already started by functions.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isAdmin()) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$pdo = getDBConnection();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate fields
    if ($email === '') {
        $errors[] = 'Email is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare("
                SELECT id, name, email, password
                FROM admins
                WHERE email = :email
                LIMIT 1
            ");

            $stmt->execute([
                ':email' => $email
            ]);

            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {

                $storedPassword = $admin['password'];

                /*
                 * Support both password formats:
                 *
                 * ID 1 = hashed password
                 * ID 2 = plain-text password
                 * ID 3 = plain-text password
                 */

                $passwordInfo = password_get_info($storedPassword);

                if ($passwordInfo['algo'] !== 0) {

                    // Hashed password
                    $passwordValid = password_verify(
                        $password,
                        $storedPassword
                    );

                } else {

                    // Plain-text password
                    $passwordValid = hash_equals(
                        $storedPassword,
                        $password
                    );
                }

                if ($passwordValid) {

                    // Set admin session
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];

                    // Save session before redirect
                    session_write_close();

                    // Go to dashboard
                    header(
                        'Location: ' .
                        SITE_URL .
                        '/admin/dashboard.php'
                    );

                    exit;

                } else {

                    $errors[] = 'Invalid email or password.';
                }

            } else {

                $errors[] = 'Invalid email or password.';
            }

        } catch (PDOException $e) {

            $errors[] = 'Unable to process login. Please try again.';
        }
    }
}

$pageTitle = 'Admin Login';

include __DIR__ . '/../includes/header.php';
?>

<section class="auth-section">

    <div class="container">

        <div class="auth-card fade-in">

            <!-- Header -->
            <div class="brand-header">

                <i
                    class="fas fa-user-shield"
                    style="color: var(--secondary);"
                ></i>

                <h2>Admin Login</h2>

                <p class="text-muted">
                    Access the Food-Mania admin dashboard
                </p>

            </div>


            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        <?php foreach ($errors as $err): ?>

                            <li>
                                <?php
                                echo htmlspecialchars(
                                    $err,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- Login Form -->
            <form method="POST" action="">

                <!-- Email -->
                <div class="mb-3">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email Address
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>

                        <input
                            type="email"
                            class="form-control"
                            id="email"
                            name="email"
                            required
                            autocomplete="email"
                            value="<?php
                                echo htmlspecialchars(
                                    $_POST['email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                            ?>"
                            placeholder="krina@foodmania.com"
                        >

                    </div>

                </div>


                <!-- Password -->
                <div class="mb-4">

                    <label
                        for="password"
                        class="form-label"
                    >
                        Password
                    </label>

                    <div class="input-group">

                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter admin password"
                        >

                    </div>

                </div>


                <!-- Login Button -->
                <button
                    type="submit"
                    class="btn btn-auth"
                    style="
                        background:
                        linear-gradient(
                            135deg,
                            var(--secondary),
                            var(--secondary-dark)
                        );
                    "
                >

                    <i class="fas fa-sign-in-alt me-2"></i>

                    Login to Dashboard

                </button>

            </form>


            <!-- Demo Credentials -->
            <div
                class="mt-4 p-3"
                style="
                    background: var(--gray-100);
                    border-radius: var(--radius-sm);
                "
            >

                <p
                    class="mb-1 fw-600 text-muted"
                    style="font-size: 0.8rem;"
                >

                    <i class="fas fa-info-circle me-1"></i>

                    Demo Admin Credentials:

                </p>

                <p
                    class="mb-0"
                    style="
                        font-size: 0.8rem;
                        color: var(--gray-500);
                    "
                >

                    Email:
                    <code>krina@foodmania.com</code>

                    |

                    Password:
                    <code>krina123</code>

                </p>

            </div>


            <!-- Back to Website -->
            <div class="text-center mt-3">

                <a
                    href="<?php echo SITE_URL; ?>/"
                    class="text-muted"
                >

                    <i class="fas fa-arrow-left me-1"></i>

                    Back to Website

                </a>

            </div>

        </div>

    </div>

</section>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>