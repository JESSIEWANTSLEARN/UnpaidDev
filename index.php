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
<body class="app-shell text-slate-800">

    <!-- Header -->
    <header class="relative z-10 border-b">
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
    <main class="flex min-h-screen">

        <div class="flex-1 space-y-6 p-6 overflow-y-auto">
            <!-- HERO -->

            <section class="gradient-card rounded-[20px] p-6 md:p-8 border border-slate-200 fade-in-up hover-lift shadow-[0_14px_35px_rgba(15,23,42,0.05)]">
                <div class="grid lg:grid-cols-[1.1fr_0.9fr] gap-8">
                    
                    <!-- LEFT -->

                    <div>
                        
                    </div>

                    <!-- RIGHT SIDE -->

                    <div class="space-y-4">

                    </div>
            </section>
        </div>
        
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
</body>
</html>