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
<body class="min-h-screen flex">
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
                <!-- Inventory Request form for Product Restock -->
                <section class="mb-8 overflow-hidden rounded-md border-2 border-[#374151] bg-[#FFFFFF]">

                    <div class="border-b border-[#374151] px-6 py-5">
                        <h1 class="text-xl font-bold text-[#2C3E50]">
                            Request Inventory Product
                        </h1>

                        <p class="mt-1 text-sm text-[#374151]">
                            Complete this form to request additional stock from the purchasing department.
                            All submitted requests will be reviewed before an order is created.
                        </p>
                    </div>

                    <form action="" method="POST" class="p-6">

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                            <!-- Product Name -->
                            <div>
                                <label for="product_name" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Product Name <span class="text-red-600">*</span>
                                </label>

                                <input
                                    type="text"
                                    id="product_name"
                                    name="product_name"
                                    required
                                    placeholder="Example: Portable Air Conditioner"
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                            </div>

                            <!-- Product Category -->
                            <div>
                                <label for="category" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Product Category <span class="text-red-600">*</span>
                                </label>

                                <select
                                    id="category"
                                    name="category"
                                    required
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                                    <option value="" selected disabled>Select a category</option>
                                    <option value="portable_ac">Portable AC Unit</option>
                                    <option value="air_purifier">Air Purifier</option>
                                    <option value="replacement_filter">Replacement Filter</option>
                                    <option value="smart_thermostat">Smart Thermostat</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label for="quantity" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Requested Quantity <span class="text-red-600">*</span>
                                </label>

                                <input
                                    type="number"
                                    id="quantity"
                                    name="quantity"
                                    min="1"
                                    required
                                    placeholder="Enter quantity"
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                            </div>

                            <!-- Priority -->
                            <div>
                                <label for="priority" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Priority Level <span class="text-red-600">*</span>
                                </label>

                                <select
                                    id="priority"
                                    name="priority"
                                    required
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                                    <option value="" selected disabled>Select priority</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <!-- Required Date -->
                            <div>
                                <label for="required_date" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Date Needed <span class="text-red-600">*</span>
                                </label>

                                <input
                                    type="date"
                                    id="required_date"
                                    name="required_date"
                                    required
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                            </div>

                            <!-- Preferred Supplier -->
                            <div>
                                <label for="supplier" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Preferred Supplier
                                </label>

                                <input
                                    type="text"
                                    id="supplier"
                                    name="supplier"
                                    placeholder="Enter supplier name (optional)"
                                    class="w-full rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                >
                            </div>

                            <!-- Request Reason -->
                            <div class="md:col-span-2">
                                <label for="request_reason" class="mb-2 block text-sm font-semibold text-[#374151]">
                                    Reason for Request <span class="text-red-600">*</span>
                                </label>

                                <textarea
                                    id="request_reason"
                                    name="request_reason"
                                    rows="4"
                                    required
                                    placeholder="Explain why this product and quantity are needed..."
                                    class="w-full resize-none rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-3 text-sm text-[#374151] outline-none transition focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20"
                                ></textarea>
                            </div>

                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[#374151] pt-6 sm:flex-row sm:justify-end">

                            <button
                                type="reset"
                                class="rounded-lg border border-[#374151] bg-[#FFFFFF] px-6 py-3 text-sm font-bold text-[#374151] transition hover:bg-[#374151]/5"
                            >
                                Clear Form
                            </button>

                            <button
                                type="submit"
                                class="rounded-lg border border-[#1D4ED8] bg-[#1D4ED8] px-6 py-3 text-sm font-bold text-[#FFFFFF] transition hover:bg-blue-800"
                            >
                                Submit Request
                            </button>

                        </div>

                    </form>

                </section>

                <!-- Record of Product to be Restock -->
                <section id="home" class="border-2 border-[#374151] rounded-md mb-8"></section>

                <!-- Record and Review Product in Stock -->
                <section id="home" class="border-2 border-[#374151] rounded-md mb-8"></section>

                <!-- Record of Purchase Order from Purchasing_Staff -->
                <section id="home" class="border-2 border-[#374151] rounded-md mb-8"></section>
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