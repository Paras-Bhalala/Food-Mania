<?php
/**
 * Admin Login
 * Food-Mania
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();
$errors = [];

// Redirect if already logged in
if (isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/dashboard.php');
    exit;
}


// ======================================================
// LOGIN
// ======================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';


    // --------------------------------------------------
    // VALIDATION
    // --------------------------------------------------

    if ($email === '') {
        $errors[] = 'Email is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    }


    // --------------------------------------------------
    // AUTHENTICATION
    // --------------------------------------------------

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


            if (!$admin) {

                $errors[] = 'Invalid email or password.';

            } else {

                $storedPassword = (string) $admin['password'];

                $passwordValid = false;


                // --------------------------------------------------
                // FIRST: TRY SECURE HASH
                // --------------------------------------------------

                if (password_verify($password, $storedPassword)) {

                    $passwordValid = true;

                }


                // --------------------------------------------------
                // SECOND: SUPPORT OLD PLAIN-TEXT PASSWORD
                // --------------------------------------------------

                elseif (hash_equals($storedPassword, $password)) {

                    $passwordValid = true;


                    /*
                     * Convert the old plain-text password
                     * into a secure hash.
                     */

                    $newHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $update = $pdo->prepare("
                        UPDATE admins
                        SET password = :password
                        WHERE id = :id
                    ");

                    $update->execute([
                        ':password' => $newHash,
                        ':id' => $admin['id']
                    ]);
                }


                // --------------------------------------------------
                // LOGIN SUCCESS
                // --------------------------------------------------

                if ($passwordValid) {

                    /*
                     * Create a fresh session ID.
                     */
                    session_regenerate_id(true);


                    /*
                     * Store admin information.
                     */
                    $_SESSION['admin_id'] = (int) $admin['id'];
                    $_SESSION['admin_name'] = $admin['name'];


                    /*
                     * Save session before redirect.
                     */
                    session_write_close();


                    /*
                     * Redirect to dashboard.
                     */
                    header(
                        'Location: ' .
                        SITE_URL .
                        '/admin/dashboard.php'
                    );

                    exit;

                } else {

                    $errors[] = 'Invalid email or password.';
                }
            }

        } catch (PDOException $e) {

            $errors[] = 'Unable to process login. Please try again.';
        }
    }
}


$pageTitle = 'Admin Login';

include __DIR__ . '/../includes/header.php';
?>


<!-- ======================================================
     ADMIN LOGIN PAGE
====================================================== -->

<section class="auth-section">

    <div class="container">

        <div class="auth-card fade-in">


            <!-- HEADER -->
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


            <!-- ERRORS -->
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


            <!-- LOGIN FORM -->
            <form method="POST" action="">


                <!-- EMAIL -->
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
                            autocomplete="username"
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


                <!-- PASSWORD -->
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


                <!-- LOGIN BUTTON -->
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


            <!-- DEMO CREDENTIALS -->
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


            <!-- BACK TO WEBSITE -->
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


<!-- BOOTSTRAP -->
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>