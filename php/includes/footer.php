</main>
<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">

            <!-- Brand -->
            <div>
                <div class="footer-logo">Maison Decor</div>
                <p class="footer-desc">
                    Curated home decor for the modern home. 
                    Quality pieces that bring warmth and style to every room.
                </p>
                <div class="social-links">
                    <a href="#" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" title="Pinterest"><i class="fa-brands fa-pinterest"></i></a>
                    <a href="#" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Shop Links -->
            <div>
                <h4 class="footer-title">Shop</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php">All Products</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?sort=price_asc">Best Prices</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/shop.php?sort=name_asc">New Arrivals</a></li>
                </ul>
            </div>

            <!-- Help Links -->
            <div>
                <h4 class="footer-title">Help</h4>
                <ul class="footer-links">
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Shipping Policy</a></li>
                    <li><a href="#">Returns</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h4 class="footer-title">Contact</h4>
                <ul class="footer-links">
                    <li>
                        <a href="mailto:hello@maisondecor.com">
                            <i class="fa-solid fa-envelope" style="margin-right:0.5rem;"></i>
                            hello@maisondecor.com
                        </a>
                    </li>
                    <li>
                        <a href="tel:+254734504035">
                            <i class="fa-solid fa-phone" style="margin-right:0.5rem;"></i>
                            +254 734 504 035
                        </a>
                    </li>
                    <li>
                        <span style="color:rgba(255,255,255,0.55); font-size:0.9rem;">
                            <i class="fa-solid fa-location-dot" style="margin-right:0.5rem;"></i>
                            Nairobi, Kenya
                        </span>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            &copy; <?php echo date('Y'); ?> Maison Decor. All rights reserved.
            &nbsp;&bull;&nbsp; Designed with care.
        </div>
    </div>
</footer>

<!-- Toast Notification -->
<div class="toast" id="toast"></div>

<!-- Main JavaScript -->
<script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>

</body>
</html>