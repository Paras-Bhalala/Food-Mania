<?php
/**
 * Footer Include
 * Food-Mania - Three-column footer with copyright bar
 */
?>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <!-- About Column -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand mb-3">
                    <i class="fas fa-utensils me-2"></i>
                    <span>Food<span class="text-accent">Mania</span></span>
                </div>
                <p class="footer-text">
                    Delicious food crafted with passion and delivered to your doorstep. 
                    From wood-fired pizzas to authentic Indian curries — we serve happiness on a plate.
                </p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <!-- Quick Links Column -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/menu.php"><i class="fas fa-chevron-right me-2"></i>Our Menu</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about.php"><i class="fas fa-chevron-right me-2"></i>About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-chevron-right me-2"></i>Contact Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/user/order_history.php"><i class="fas fa-chevron-right me-2"></i>Track Order</a></li>
                </ul>
            </div>
            
            <!-- Contact Info Column -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-heading">Contact Info</h5>
                <ul class="footer-contact">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Food Street, Connaught Place,<br>New Delhi, India 110001</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+91 98765 43210</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>hello@foodmania.com</span>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <span>Mon–Sun: 10:00 AM – 11:00 PM</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Copyright Bar -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Food-Mania. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        Made with <i class="fas fa-heart text-danger"></i> for food lovers • 
                        <a href="<?php echo SITE_URL; ?>/admin/login.php" class="text-white-50 ms-1 text-decoration-none hover-white">
                            <i class="fas fa-user-shield me-1"></i> Admin Portal
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>

</body>
</html>
