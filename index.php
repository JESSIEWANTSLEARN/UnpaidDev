<!--
    Index.php - Main landing page for the WalangBrownout portal system.
    This page serves as the entry point for users, providing an overview of the system's features
    and a navigation menu to access different sections of the portal.
-->

<?php
    require_once 'session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walang Brown Out Portal</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <link rel="stylesheet" href="index_style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="page-shell">

    <!-- Header -->
    <header class="site-header">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="https://img.sanishtech.com/u/6559c6ed2b30023d94b79a0932f09814.png"
                    alt="Walang Brown Out Logo"
                    width="45"
                    height="45">

                <div class="hidden sm:block leading-tight">
                    <span class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]">
                        Republic of the Philippines
                    </span>

                    <div class="text-lg font-extrabold text-blue-700">
                        WALANG BROWN OUT
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="login.php" class="nav-button">Login</a>
                <a href="signin.php" class="nav-button primary-nav-button">Sign in</a>
            </div>
        </div>

        <nav class="main-nav" aria-label="Main navigation">
            <a href="#">Home</a>
            <a href="#">Solutions</a>
            <a href="#">Features</a>
            <a href="#">Inventory</a>
            <a href="#">About</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="storefront">
        <section class="promo-banner reveal-up">
            <div class="promo-copy">
                <strong><h1>Comfort at home, all year round.</h1></strong>
                <p>Walang BrownOut Appliances helps homes and businesses stay cool, clean, and efficient with trusted portable AC units, air purifiers, smart thermostats, and replacement filters.</p>
                    
                <div class="promo-actions">
                    <a href="#" class="primary-link">Explore Solutions</a>
                    <a href="#" class="secondary-link">View Dashboard</a>
                </div>
            </div>
            <div class="promo-visual" aria-label="Appliance display image"></div>
        </section>

        <section class="categories-section">
            <div class="section-heading">
                <strong><h2>About Walang BrownOut</h2></strong>
                
                <a href="#">Learn more</a>

                <button class="section-toggle" aria-expanded="true" aria-label="Toggle section">
                    <span class="chev">▾</span>
                </button>
            </div>

            <div class="about-box reveal-up">
                <p>
                    Walang BrownOut Appliances is a regional distributor of home comfort products dedicated to improving everyday living through dependable cooling, clean air, and smarter energy use. We serve households, offices, and retail partners with efficient solutions built for comfort, health, and performance.
                </p>
            </div>
        </section>

        <section class="categories-section">
            <div class="section-heading">
                <strong><h2>Our services</h2></strong>
                    
                <a href="#">Explore</a>
                    
                <button class="section-toggle" aria-expanded="true" aria-label="Toggle section">
                    <span class="chev">▾</span>
                </button>
            </div>

            <div class="category-grid">
                <article class="category-card reveal-up">
                    <div class="category-thumb thumb-one"></div>
                    <strong><h3>Portable AC Units</h3></strong>
                </article>

                <article class="category-card reveal-up stagger-2">
                    <div class="category-thumb thumb-two"></div>
                    <strong><h3>Air Purifiers</h3></strong>
                </article>

                <article class="category-card reveal-up stagger-3">
                    <div class="category-thumb thumb-three"></div>
                    <strong><h3>Replacement Filters</h3></strong>
                </article>

                <article class="category-card reveal-up stagger-4">
                    <div class="category-thumb thumb-four"></div>
                    <strong><h3>Smart Thermostats</h3></strong>
                </article>
            </div>
        </section>

        <section class="products-section">
            <div class="section-heading">
                <strong><h2>Featured home comfort essentials</h2></strong>
                
                <a href="#">View all</a>
                
                <button class="section-toggle" aria-expanded="true" aria-label="Toggle section">
                    <span class="chev">▾</span>
                </button>
            </div>

            <div class="product-grid">
                <article class="product-card reveal-up">
                    <div class="product-image image-one"></div>
                    
                    <div class="product-info">
                        <strong><h3>Portable AC Pro</h3></strong>
                        
                        <p>Cool rooms fast and quietly</p>
                        
                        <div class="product-meta">
                            <span class="price">₱18,500</span>
                            <button>Add to cart</button>
                        </div>
                    </div>
                </article>

                <article class="product-card reveal-up stagger-2">
                    <div class="product-image image-two"></div>
                    
                    <div class="product-info">
                        <strong><h3>Air Purifier Plus</h3></strong>
                        
                        <p>Cleaner indoor air</p>
                        
                        <div class="product-meta">
                            <span class="price">₱12,900</span>
                            <button>Add to cart</button>
                        </div>
                    </div>
                </article>

                <article class="product-card reveal-up stagger-3">
                    <div class="product-image image-three"></div>
                    
                    <div class="product-info">
                        <strong><h3>Carbon Filter Pack</h3></strong>
                        
                        <p>High-efficiency replacement</p>
                        
                        <div class="product-meta">
                            <span class="price">₱2,400</span>
                            <button>Add to cart</button>
                        </div>
                    </div>
                </article>

                <article class="product-card reveal-up stagger-4">
                    <strong><div class="product-image image-four"></div></strong>
                    
                    <div class="product-info">
                        <strong><h3>Smart Thermostat</h3></strong>

                        <p>Energy-saving control</p>
                        
                        <div class="product-meta">
                            <span class="price">₱8,750</span>
                            <button>Add to cart</button>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="promo-strip reveal-up">
            <div>
                <span class="promo-badge">Free shipping</span>
                <strong><h3>On orders over $100</h3></strong>
            </div>

            <div>
                <span class="promo-badge">Easy returns</span>
                <strong><h3>30-day guarantee</h3></strong>
            </div>

            <div>
                <span class="promo-badge">Secure payment</span>
                <strong><h3>Protected checkout</h3></strong>
            </div>
        </section>

        <section class="newsletter-box reveal-up">
            <div>
                <strong><span class="newsletter-tag">Newsletter</span></strong>
                <strong><h2>Save 15% on your first order.</h2></strong>
            </div>
            
            <form class="newsletter-form">
                <input type="email" placeholder="Enter your email">
                <button type="submit">Join now</button>
            </form>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer reveal-up">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="brand">Walang BrownOut</div>
                <p>
                    A secure, role-based warehouse and inventory management
                    platform designed for administrators, warehouse staff,
                    suppliers, and employees.
                </p>
            </div>

            <div class="footer-column">
                <h4>Shop</h4>

                <a href="#">New arrivals</a>
                <a href="#">Best sellers</a>
                <a href="#">Accessories</a>
                <a href="#">Sale</a>
            </div>

            <div class="footer-column">
                <h4>Company</h4>

                <a href="#">About us</a>
                <a href="#">Journal</a>
                <a href="#">Careers</a>
                <a href="#">Contact</a>
            </div>

            <div class="footer-column">
                <h4>Support</h4>

                <a href="#">Shipping</a>
                <a href="#">Returns</a>
                <a href="#">FAQs</a>
                <a href="#">Privacy</a>
            </div>
        </div>

        <div class="footer-bottom">
            <span><strong>&copy; 2026 WalangBrownOut.</strong> All rights reserved.</span>

            <div class="social-links">
                <a href="#">Instagram</a>
                <a href="#">Facebook</a>
                <a href="#">X</a>
            </div>
        </div>
    </footer>

    <script>
        const revealItems = document.querySelectorAll('.reveal-up');

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const item = entry.target;
                    item.classList.remove('is-visible');
                    void item.offsetWidth;
                    item.classList.add('is-visible');
                } else {
                    entry.target.classList.remove('is-visible');
                }
            });
        }, {
            threshold: 0.18,
            rootMargin: '0px 0px -8% 0px'
        });

        revealItems.forEach((item) => revealObserver.observe(item));
        
        // Section collapse/expand toggles for small screens
        const sectionToggles = document.querySelectorAll('.section-toggle');

        sectionToggles.forEach((btn) => {
            btn.addEventListener('click', () => {
                const section = btn.closest('section');
                const expanded = btn.getAttribute('aria-expanded') === 'true';
                btn.setAttribute('aria-expanded', (!expanded).toString());
                if (expanded) {
                    section.classList.add('collapsed');
                } else {
                    section.classList.remove('collapsed');
                }
            });
        });

        function applyInitialCollapse() {
            const isSmall = window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
            sectionToggles.forEach((btn) => {
                const section = btn.closest('section');
                if (isSmall) {
                    // collapse large content areas by default on phones
                    section.classList.add('collapsed');
                    btn.setAttribute('aria-expanded', 'false');
                } else {
                    section.classList.remove('collapsed');
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        }

        applyInitialCollapse();
        window.addEventListener('resize', applyInitialCollapse);
    </script>
</body>
</html>