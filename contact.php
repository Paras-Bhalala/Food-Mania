<?php
/**
 * Contact Page
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Contact form that stores messages in the contact_messages table.
 * CSRF protected.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    } else {
        $name    = sanitize($_POST['name'] ?? '');
        $email   = sanitize($_POST['email'] ?? '');
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        
        // Validate
        if (empty($name))    $errors[] = 'Name is required.';
        if (empty($email))   $errors[] = 'Email is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
        if (empty($subject)) $errors[] = 'Subject is required.';
        if (empty($message)) $errors[] = 'Message is required.';
        if (strlen($message) < 10) $errors[] = 'Message must be at least 10 characters.';
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (:name, :email, :subject, :message)");
            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':subject' => $subject,
                ':message' => $message,
            ]);
            
            setFlash('success', 'Thank you for your message! We will get back to you soon.');
            redirect(SITE_URL . '/contact.php');
        }
    }
}

$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with Food-Mania. Send us your feedback, queries, or just say hello!';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-envelope me-2"></i> Contact Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Contact Us</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Contact Section -->
<section class="section-padding">
    <div class="container">
        <?php echo displayFlash(); ?>
        
        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="contact-card">
                    <h4 class="mb-4"><i class="fas fa-paper-plane text-orange me-2"></i> Send us a Message</h4>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo $err; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" class="needs-validation" novalidate id="contactForm">
                        <?php echo csrfField(); ?>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       value="<?php echo sanitize($_POST['name'] ?? ''); ?>"
                                       placeholder="John Doe">
                                <div class="invalid-feedback">Please enter your name.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       value="<?php echo sanitize($_POST['email'] ?? ''); ?>"
                                       placeholder="john@example.com">
                                <div class="invalid-feedback">Please enter a valid email.</div>
                            </div>
                            <div class="col-12">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="subject" name="subject" required
                                       value="<?php echo sanitize($_POST['subject'] ?? ''); ?>"
                                       placeholder="How can we help?">
                                <div class="invalid-feedback">Please enter a subject.</div>
                            </div>
                            <div class="col-12">
                                <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" required
                                          minlength="10"
                                          placeholder="Tell us what's on your mind..."><?php echo sanitize($_POST['message'] ?? ''); ?></textarea>
                                <div class="invalid-feedback">Please enter a message (at least 10 characters).</div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary-custom btn-lg rounded-pill">
                                    <i class="fas fa-paper-plane me-2"></i> Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Contact Info Card -->
            <div class="col-lg-5">
                <div class="contact-info-card">
                    <h4>Get In Touch</h4>
                    <p style="opacity:0.85;">We'd love to hear from you! Whether it's feedback, a question, or just saying hello.</p>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Address</strong><br>
                            123 Food Street, Connaught Place,<br>
                            New Delhi, India 110001
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Phone</strong><br>
                            +91 98765 43210
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email</strong><br>
                            hello@foodmania.com
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Hours</strong><br>
                            Mon–Sun: 10:00 AM – 11:00 PM
                        </div>
                    </div>
                    
                    <hr style="border-color:rgba(255,255,255,0.2);">
                    
                    <h6>Follow Us</h6>
                    <div class="social-links mt-2">
                        <a href="#" style="background:rgba(255,255,255,0.15);color:var(--white);"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" style="background:rgba(255,255,255,0.15);color:var(--white);"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="background:rgba(255,255,255,0.15);color:var(--white);"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="background:rgba(255,255,255,0.15);color:var(--white);"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
