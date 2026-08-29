<?php
/**
 * About Page
 * Food-Mania - Single Restaurant Food Ordering System
 * 
 * Static page with restaurant story, hours, address, mission statement.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';
$pageDescription = 'Learn about Food-Mania — our story, mission, and the passion behind every dish we serve.';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<meta name="site-url" content="<?php echo SITE_URL; ?>">

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1><i class="fas fa-info-circle me-2"></i> About Us</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Our Story -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div style="width:100%;height:400px;border-radius:var(--radius-lg);background:linear-gradient(135deg,rgba(255,107,53,0.15),rgba(255,193,7,0.15));display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow-md);">
                    <i class="fas fa-utensils" style="font-size:8rem;color:rgba(255,107,53,0.4);"></i>
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="section-title mb-3">Our <span class="highlight">Story</span></h2>
                <p class="text-muted mb-3">
                    Food-Mania was born in 2020 from a simple yet powerful idea — that everyone deserves 
                    access to restaurant-quality food without leaving the comfort of their home. What started 
                    as a small kitchen with a big dream has grown into one of the most loved food destinations 
                    in the city.
                </p>
                <p class="text-muted mb-3">
                    Our team of passionate chefs brings together culinary traditions from around the world. 
                    From the wood-fired ovens producing perfect pizzas to the tandoor crafting smoky kebabs, 
                    every dish at Food-Mania tells a story of dedication and flavor.
                </p>
                <p class="text-muted">
                    We believe in using only the freshest ingredients, sourced locally wherever possible, 
                    and never compromising on quality. Every meal is prepared with love, packed with care, 
                    and delivered with a smile.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Mission, Vision, Values -->
<section class="section-padding" style="background: var(--white);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">What <span class="highlight">Drives Us</span></h2>
            <p class="section-subtitle">Our core values guide everything we do</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-bullseye"></i>
                    <h5 class="mt-3">Our Mission</h5>
                    <p class="text-muted">
                        To serve delicious, affordable food that brings people together and creates 
                        memorable dining experiences, one meal at a time.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-eye"></i>
                    <h5 class="mt-3">Our Vision</h5>
                    <p class="text-muted">
                        To become the most trusted and loved food destination, known for our quality, 
                        variety, and exceptional service across every dish we create.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="about-card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h5 class="mt-3">Our Promise</h5>
                    <p class="text-muted">
                        Fresh ingredients, hygienic preparation, quick delivery, and a 100% satisfaction 
                        guarantee. If you're not happy, we're not done.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Operating Hours & Address -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4">
            <!-- Hours -->
            <div class="col-md-6">
                <div class="checkout-card h-100">
                    <h4 class="mb-4"><i class="fas fa-clock text-orange me-2"></i> Operating Hours</h4>
                    <table class="table table-borderless">
                        <tbody>
                            <tr>
                                <td class="fw-500">Monday – Friday</td>
                                <td class="text-end fw-600 text-orange">10:00 AM – 11:00 PM</td>
                            </tr>
                            <tr>
                                <td class="fw-500">Saturday</td>
                                <td class="text-end fw-600 text-orange">9:00 AM – 11:30 PM</td>
                            </tr>
                            <tr>
                                <td class="fw-500">Sunday</td>
                                <td class="text-end fw-600 text-orange">9:00 AM – 10:00 PM</td>
                            </tr>
                            <tr>
                                <td class="fw-500">Public Holidays</td>
                                <td class="text-end fw-600 text-orange">10:00 AM – 9:00 PM</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="text-muted mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Last orders accepted 30 minutes before closing.
                    </p>
                </div>
            </div>
            
            <!-- Address & Contact -->
            <div class="col-md-6">
                <div class="checkout-card h-100">
                    <h4 class="mb-4"><i class="fas fa-map-marker-alt text-orange me-2"></i> Visit Us</h4>
                    <div class="mb-3">
                        <h6 class="fw-600">Address</h6>
                        <p class="text-muted mb-0">
                            123 Food Street, Connaught Place,<br>
                            New Delhi, India 110001
                        </p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-600">Phone</h6>
                        <p class="text-muted mb-0">+91 98765 43210</p>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-600">Email</h6>
                        <p class="text-muted mb-0">hello@foodmania.com</p>
                    </div>
                    <div>
                        <h6 class="fw-600">Delivery Radius</h6>
                        <p class="text-muted mb-0">We deliver within 10 km of our restaurant. Free delivery on orders above ₹500!</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section style="background:linear-gradient(135deg, var(--primary), var(--secondary));padding:4rem 0;text-align:center;color:var(--white);">
    <div class="container">
        <h2 style="font-weight:800;font-size:2rem;margin-bottom:1rem;">Come Visit Us!</h2>
        <p style="font-size:1.1rem;opacity:0.9;margin-bottom:2rem;">
            Or simply order online and we'll bring the feast to you.
        </p>
        <a href="<?php echo SITE_URL; ?>/user/menu.php" class="btn btn-light btn-lg rounded-pill px-5" 
           style="font-weight:600;color:var(--primary);">
            <i class="fas fa-utensils me-2"></i> Order Online
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
