<!--
    Index.php - Main landing page for the WalangBrownout portal system.
    This page serves as the entry point for users, providing an overview of the system's features
    and a navigation menu to access different sections of the portal.
-->

<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WalangBrownout</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <link rel="stylesheet" href="index_style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="app-shell text-slate-800">

    <div class="bg-aurora" aria-hidden="true">
        <div class="aurora-orb one"></div>
        <div class="aurora-orb two"></div>
    </div>

    <!-- Header -->
    <header class="relative z-10">

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

                <button id="themeToggle"
                        type="button"
                        class="theme-toggle hidden md:inline-flex items-center gap-2">

                    🌙

                </button>

                <span class="hidden md:flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-sm text-slate-600 shadow-sm">

                    <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>

                    Live System

                </span>

                <a href="login.php" class="btn">

                    Login to Portal

                </a>

            </div>

        </div>

    </header>

    <!-- Main Content -->
    <main>
        <div class="flex-1 space-y-6">

        <!-- HERO -->

        <section class="gradient-card rounded-[20px] p-6 md:p-8 border border-slate-200 fade-in-up hover-lift shadow-[0_14px_35px_rgba(15,23,42,0.05)]">

            

        </section>

        <section id="features" class="grid md:grid-cols-3 gap-5">

            <aside class="w-64 bg-white rounded-3xl shadow-md border border-slate-200 p-5">
            <h2 class="text-xs font-bold uppercase tracking-[0.3em] text-slate-500 mb-4">
                Menu
            </h2>

            <nav class="space-y-2">

                <a href="login.php"
                    class="flex items-center gap-3 rounded-xl bg-slate-100 border border-slate-200 px-4 py-3 font-medium text-slate-700 hover:bg-slate-200 transition">
                    <span>🔐</span>
                    Login
                </a>

                <a href="#"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span>📦</span>
                    Inventory
                </a>

                <a href="#"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span>🧾</span>
                    Orders
                </a>

                <a href="#"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span>👥</span>
                    Users
                </a>

                <a href="#"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 font-medium text-slate-700 hover:bg-slate-100 transition">
                    <span>📊</span>
                    Reports
                </a>

            </nav>
        </aside>
        
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-[#0b5091] via-[#165ea7] to-[#0f2f56] text-white rounded-3xl p-8 md:p-10 shadow-xl">
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold tracking-[0.18em] uppercase">Enterprise Portal</span>
                
                <h1 class="mt-4 text-4xl md:text-5xl font-extrabold leading-tight">Walang Brown Out Portal System</h1>
                
                <p class="mt-4 text-blue-100 text-base md:text-lg max-w-2xl">A secure, role-based appliance operations platform for staff, warehouse teams, and administrators.</p>
                
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="login.php"
                        class="bg-white text-[#0b5091] font-bold px-6 py-3 rounded-xl shadow-md hover:bg-gray-100 transition text-center">Go
                            to Sign In</a>
                    <a href="#features"
                        class="border border-white/40 text-white font-semibold px-6 py-3 rounded-xl hover:bg-white/10 transition text-center">Explore
                            Features</a>
                </div>

            </div>

            <section id="features" class="grid md:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="text-xl mb-2">🔐</div>
                    <h2 class="font-bold text-slate-800 mb-1">Role-Based Security</h2>
                    <p class="text-sm text-slate-600">Every account is routed to the correct access level for streamlined operations.</p>
                </div>
                    
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="text-xl mb-2">📦</div>
                    <h2 class="font-bold text-slate-800 mb-1">Warehouse Control</h2>
                    <p class="text-sm text-slate-600">Manage stock, products, and logistics through a clean administration flow.</p>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <div class="text-xl mb-2">📊</div>
                    <h2 class="font-bold text-slate-800 mb-1">Operations Overview</h2>
                    <p class="text-sm text-slate-600">Monitor the system with clear, professional dashboards designed for daily use.</p>
                </div>
            </section>
            </div>
        
            <aside class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4">
                <div class="mb-4">
                    <div class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">Calendar</div>
                    <div class="mt-2 flex items-center gap-2">
                        <label class="text-xs font-semibold text-slate-600">Month</label>
                        <select id="calendar-month-select"
                            class="rounded-lg border border-slate-200 px-2 py-1 text-sm text-slate-700 bg-white">
                            <option value="0">January</option>
                            <option value="1">February</option>
                            <option value="2">March</option>
                            <option value="3">April</option>
                            <option value="4">May</option>
                            <option value="5">June</option>
                            <option value="6">July</option>
                            <option value="7">August</option>
                            <option value="8">September</option>
                            <option value="9">October</option>
                            <option value="10">November</option>
                            <option value="11">December</option>
                        </select>

                        <label class="text-xs font-semibold text-slate-600">Year</label>
            
                        <select id="calendar-year-select" class="rounded-lg border border-slate-200 px-2 py-1 text-sm text-slate-700 bg-white"></select>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-2 text-center text-[11px] font-semibold text-slate-500 mb-2">
                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>

                <div id="calendar-days" class="grid grid-cols-7 gap-2 text-center text-sm"></div>

                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-3">
                    <div id="selected-date-label" class="font-semibold text-slate-800">Selected date</div>
                    <div id="selected-date-event" class="text-slate-600 mt-1">Choose a highlighted date.</div>
                </div>
            </aside>
        </div>

        </section>

    </div>
        
    </main>

    <!-- Footer -->
    <footer>
        <p><strong>&copy; 2026 WalangBrownout. All rights reserved.</strong></p>
    </footer>
</body>
</html>

<?php

?>