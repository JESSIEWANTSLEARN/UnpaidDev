<!--
Purchasing Staff Dashboard -
    This webpage serves as the operational workspace for the Purchasing Staff.
    It enables staff to prepare and encode purchase orders, manage and update
    supplier records, and monitor the status of incoming purchases. All tasks
    displayed are assigned by the Purchasing Manager and are intended to
    support day-to-day procurement operations.
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->

    <script src="https://cdn.tailwindcss.com"></script>

    <title>Purchasing Stuff - Walang Brown Out</title>
</head>

<body class="min-h-screen flex">
    <!-- Aside -->
    <aside class="w-64 bg-[#2C3E50] text-white flex-shrink-0 min-h-screen hidden md:flex flex-col">

            <!-- Logo -->
            <div class="h-[82px] flex items-center px-6 border-b border-[#374151]">

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
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10 text-white">
                        <span class="text-sm">Dashboard</span>
                    </a>

                </div>

                <div class="space-y-1">

                    <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Incoming Purchases</span>
                    </a>

                </div>

                <div class="space-y-1">

                    <a href="#"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Purchase Order</span>
                    </a>

                </div>

                <div class="space-y-1">

                    <a href="#"
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
            <header class="h-[82px] flex items-center border-b border-[#E5E7EB] bg-[#FFFFFF]">

                <!-- Logo Section -->
                <div class="flex items-center gap-4 px-6">

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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-7">
                    <!-- PAGE TITLE -->
                    <div class="mb-7">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#1D4ED8]">
                                Role
                            </p>
                            <h1 class="mt-1 text-3xl font-bold text-[#2C3E50]">
                                Dashboard
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-[#374151]">
                                Lorem ipsum dolor sit amet consectetur adipisicing elit. Recusandae dolore est voluptatum voluptates! Tempora nulla, ex deleniti dolores dolor a, quam vitae commodi doloremque voluptates fugit illo, autem explicabo aperiam.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3">

                        <button class="bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-sm">
                            Previous Year
                            <span class="ml-4">⌄</span>
                        </button>

                        <button class="text-white rounded-lg px-5 py-2.5 text-sm bg-[#1D4ED8] px-5 py-3 text-sm font-bold transition hover:bg-blue-800">
                            View All Time
                        </button>

                    </div>

                </div>
                
                <!-- article Summary -->
                <section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">[Name Holder]</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">[Number]</p>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#1D4ED8]">[Status Holder]</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">[Name Holder]</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">[Number]</p>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">[Status Holder]</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">[Name Holder]</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">[Number]</p>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">[Status Holder]</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">[Name Holder]</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">[Number]</p>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-[#374151]">[Status Holder]</span>
                        </div>
                    </article>
                </section>
                
                <!-- Product Request Log -->
                <section class="mb-8 overflow-hidden rounded-md border-2 border-[#374151] bg-[#FFFFFF]">

                    <div class="border-b border-[#374151] px-6 py-5">
                        <h1 class="text-xl font-bold text-[#2C3E50]">
                            Requested Product
                        </h1>

                        <p class="mt-1 text-sm text-[#374151]">
                            Product that need to be restock.
                        </p>
                    </div>

                </section>

                <!-- Add Supplier Form -->
                <section id="supplier-form" class="mb-8 overflow-hidden rounded-lg border-2 border-[#374151] bg-[#FFFFFF]">
                    <div class="border-b border-[#374151] px-6 py-5">
                        <h2 class="text-xl font-bold text-[#2C3E50]">Register Supplier</h2>
                        <p class="mt-1 text-sm text-[#374151]">
                            Add the supplier's business, contact, delivery, and product information.
                        </p>
                    </div>

                    <form action="" method="POST" class="p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label for="supplier_name" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Supplier Name <span class="text-red-600">*</span>
                                </label>
                                <input type="text" id="supplier_name" name="supplier_name" required
                                       placeholder="Enter company name"
                                       class="w-full rounded-lg border border-[#374151] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                            </div>

                            <div>
                                <label for="contact_person" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Contact Person <span class="text-red-600">*</span>
                                </label>
                                <input type="text" id="contact_person" name="contact_person" required
                                       placeholder="Full name"
                                       class="w-full rounded-lg border border-[#374151] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                            </div>

                            <div>
                                <label for="contact_number" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Contact Number <span class="text-red-600">*</span>
                                </label>
                                <input type="tel" id="contact_number" name="contact_number" required
                                       placeholder="09XX XXX XXXX"
                                       class="w-full rounded-lg border border-[#374151] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                            </div>

                            <div>
                                <label for="supplier_email" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Email Address <span class="text-red-600">*</span>
                                </label>
                                <input type="email" id="supplier_email" name="supplier_email" required
                                       placeholder="supplier@example.com"
                                       class="w-full rounded-lg border border-[#374151] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                            </div>

                            <div>
                                <label for="product_category" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Supplied Product <span class="text-red-600">*</span>
                                </label>
                                <select id="product_category" name="product_category" required
                                        class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                                    <option value="" selected disabled>Select product category</option>
                                    <option value="portable_ac">Portable AC Unit</option>
                                    <option value="air_purifier">Air Purifier</option>
                                    <option value="replacement_filter">Replacement Filter</option>
                                    <option value="smart_thermostat">Smart Thermostat</option>
                                    <option value="multiple">Multiple Categories</option>
                                </select>
                            </div>

                            <div>
                                <label for="lead_time" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Expected Lead Time <span class="text-red-600">*</span>
                                </label>
                                <div class="relative">
                                    <input type="number" id="lead_time" name="lead_time" min="1" required
                                           placeholder="Number of days"
                                           class="w-full rounded-lg border border-[#374151] px-4 py-3 pr-16 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                                    <span class="absolute right-4 top-3 text-sm text-slate-500">days</span>
                                </div>
                            </div>

                            <div class="md:col-span-2 xl:col-span-3">
                                <label for="supplier_address" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Business Address <span class="text-red-600">*</span>
                                </label>
                                <textarea id="supplier_address" name="supplier_address" rows="3" required
                                          placeholder="Enter complete supplier address"
                                          class="w-full resize-none rounded-lg border border-[#374151] px-4 py-3 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"></textarea>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[#374151] pt-6 sm:flex-row sm:justify-end">
                            <button type="reset"
                                    class="rounded-lg border border-[#374151] px-6 py-3 text-sm font-bold text-[#374151] transition hover:bg-slate-50">
                                Clear Form
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-[#1D4ED8] px-6 py-3 text-sm font-bold text-[#FFFFFF] transition hover:bg-blue-800">
                                Save Supplier
                            </button>
                        </div>
                    </form>
                </section>
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-[#E5E7EB] bg-[#FFFFFF] px-6 py-8">

                <p class="text-center text-sm font-semibold text-[#2C3E50]">
                    &copy; 2026 Walang Brown Out. All rights reserved.
                </p>

            </footer>

    </section>
</body>
</html>