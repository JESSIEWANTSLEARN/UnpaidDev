<!-- 
Inventory Request Page -
    This webpage allows the Purchasing Manager to review products currently in
    storage, submit a new inventory purchase request, and monitor previously
    requested products and their current approval status. 
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchasing Manager - Walang Brown Out Inventory</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <!-- Aside -->
    <aside class="w-64 bg-[#2C3E50] text-white flex-shrink-0 min-h-screen hidden md:flex flex-col">

            <!-- Logo -->
            <div class="h-20 flex items-center px-6 border-b border-white/5">

                <div class="w-9 h-9 rounded-xl bg-400 flex items-center justify-center mr-3 overflow-hidden">
                    <img
                        src="image/Logo.png"
                        alt="Walang Brown Out Logo"
                        class="w-full h-full object-contain">
                </div>

                <div class="text-xl font-bold tracking-tight">
                    Your Name
                </div>

            </div>


            <!-- Navigation -->
            <nav class="flex-1 px-4 py-7">

                <!-- GENERAL -->
                <p class="text-xs text-slate-500 uppercase tracking-wider px-3 mb-3">
                    General
                </p>

                <div class="space-y-1">

                    <a href="Purchasing_Manager.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Dashboard</span>
                    </a>

                    <a href="PM_Inventory.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10 text-white">
                        <span class="text-sm">Inventory</span>
                    </a>

                    <a href="PM_Supplier.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Supplier</span>
                    </a>

                </div>

            </nav>


            <!-- Logout -->
            <div class="px-7 py-6 border-t border-white/5">

                <a href="#"
                class="flex items-center gap-3 text-slate-300 hover:text-white">
                    <span class="text-sm">Logout</span>
                </a>

            </div>

    </aside>

    <!-- Main -->
    <section class="flex-1 min-w-0">
            <!-- Header -->
            <header class="border-b border-[#374151] bg-[#FFFFFF]">

                <!-- Logo Section -->
                <div class="flex items-center gap-4 px-6 py-4">

                    <img
                        src="image/Logo.png"
                        alt="Walang Brown Out Logo"
                        class="h-[50px] w-[50px] object-contain"
                    >

                    <div>
                        <span class="block text-xs font-semibold uppercase tracking-[0.20em] text-[#374151]">
                            Republic of the Philippines
                        </span>

                        <div class="mt-1 text-xl font-extrabold uppercase tracking-wide text-[#1D4ED8]">
                            Walang Brown Out
                        </div>
                    </div>

                </div>

            </header>

            <!-- Main Content -->
            <main class="flex-1 bg-[#FFFFFF] px-6 py-10">
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-[#374151] bg-[#FFFFFF] px-6 py-8">

                <p class="text-center text-sm font-semibold text-[#2C3E50]">
                    &copy; 2026 Walang Brown Out. All rights reserved.
                </p>

            </footer>

    </section>
</body>
</html>