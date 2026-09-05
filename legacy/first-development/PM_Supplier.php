<!--
Supplier Management Page -
    This webpage allows the Purchasing Manager to register and monitor suppliers,
    review supplier performance and delivery lead times, verify supplier status,
    and select reliable suppliers when replenishing low-stock products.
-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchasing Manager - Walang Brown Out Supplier</title>
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
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Inventory</span>
                    </a>

                    <a href="PM_Supplier.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10 text-white">
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
                <!-- Page Introduction -->
                <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#1D4ED8]">
                            Purchasing Manager
                        </p>
                        <h1 class="mt-1 text-3xl font-bold text-[#2C3E50]">
                            Supplier Management
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-[#374151]">
                            Maintain supplier records, compare delivery performance, and identify the best supplier
                            for every product that needs replenishment.
                        </p>
                    </div>
                </div>

                <!-- Supplier Summary -->
                <section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">Total Suppliers</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">18</p>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-[#1D4ED8]">All records</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">Active Suppliers</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">14</p>
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Available</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">Pending Review</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">3</p>
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Needs action</span>
                        </div>
                    </article>

                    <article class="rounded-lg border border-[#374151] bg-[#FFFFFF] p-5">
                        <p class="text-sm font-medium text-[#374151]">Average Lead Time</p>
                        <div class="mt-3 flex items-end justify-between">
                            <p class="text-3xl font-bold text-[#2C3E50]">5.4</p>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-[#374151]">Days</span>
                        </div>
                    </article>
                </section>

                <!-- Supplier Directory -->
                <section class="overflow-hidden rounded-lg border-2 border-[#374151] bg-[#FFFFFF]">
                    <div class="flex flex-col gap-4 border-b border-[#374151] p-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-[#2C3E50]">Supplier Directory</h2>
                            <p class="mt-1 text-sm text-[#374151]">Compare supplier availability, performance, and delivery time.</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <input type="search" placeholder="Search supplier..."
                                   class="rounded-lg border border-[#374151] px-4 py-2.5 text-sm outline-none focus:border-[#1D4ED8] focus:ring-2 focus:ring-[#1D4ED8]/20">
                            <select class="rounded-lg border border-[#374151] bg-[#FFFFFF] px-4 py-2.5 text-sm text-[#374151] outline-none">
                                <option>All statuses</option>
                                <option>Active</option>
                                <option>Pending Review</option>
                                <option>Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[1050px] w-full text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-4 font-semibold">Supplier</th>
                                    <th class="px-5 py-4 font-semibold">Products</th>
                                    <th class="px-5 py-4 font-semibold">Contact</th>
                                    <th class="px-5 py-4 font-semibold">Lead Time</th>
                                    <th class="px-5 py-4 font-semibold">On-Time Rate</th>
                                    <th class="px-5 py-4 font-semibold">Rating</th>
                                    <th class="px-5 py-4 font-semibold">Status</th>
                                    <th class="px-5 py-4 font-semibold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-[#374151]">
                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-[#2C3E50]">NorthCool Appliances Inc.</p>
                                        <p class="mt-1 text-xs text-slate-500">SUP-001</p>
                                    </td>
                                    <td class="px-5 py-4">Portable AC Units</td>
                                    <td class="px-5 py-4">
                                        <p>Maria Santos</p>
                                        <p class="text-xs text-slate-500">0917 234 5681</p>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">4 days</td>
                                    <td class="px-5 py-4"><span class="font-bold text-emerald-700">96%</span></td>
                                    <td class="px-5 py-4"><span class="text-amber-500">★★★★★</span> 4.8</td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Active</span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex gap-2">
                                            <button class="rounded-md border border-[#374151] px-3 py-2 text-xs font-bold hover:bg-slate-100">View</button>
                                            <button class="rounded-md bg-[#1D4ED8] px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">Select</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-[#2C3E50]">PureAir Distribution Corp.</p>
                                        <p class="mt-1 text-xs text-slate-500">SUP-002</p>
                                    </td>
                                    <td class="px-5 py-4">Purifiers & Filters</td>
                                    <td class="px-5 py-4">
                                        <p>Jose Reyes</p>
                                        <p class="text-xs text-slate-500">0928 441 9073</p>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">6 days</td>
                                    <td class="px-5 py-4"><span class="font-bold text-emerald-700">91%</span></td>
                                    <td class="px-5 py-4"><span class="text-amber-500">★★★★</span><span class="text-slate-300">★</span> 4.3</td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Active</span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex gap-2">
                                            <button class="rounded-md border border-[#374151] px-3 py-2 text-xs font-bold hover:bg-slate-100">View</button>
                                            <button class="rounded-md bg-[#1D4ED8] px-3 py-2 text-xs font-bold text-white hover:bg-blue-800">Select</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-[#2C3E50]">SmartHome Technologies</p>
                                        <p class="mt-1 text-xs text-slate-500">SUP-003</p>
                                    </td>
                                    <td class="px-5 py-4">Smart Thermostats</td>
                                    <td class="px-5 py-4">
                                        <p>Anna Lim</p>
                                        <p class="text-xs text-slate-500">0998 127 6504</p>
                                    </td>
                                    <td class="px-5 py-4 font-semibold">8 days</td>
                                    <td class="px-5 py-4"><span class="font-bold text-amber-700">78%</span></td>
                                    <td class="px-5 py-4"><span class="text-amber-500">★★★★</span><span class="text-slate-300">★</span> 3.9</td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">Under Review</span></td>
                                    <td class="px-5 py-4">
                                        <div class="flex gap-2">
                                            <button class="rounded-md border border-[#374151] px-3 py-2 text-xs font-bold hover:bg-slate-100">View</button>
                                            <button class="rounded-md border border-[#1D4ED8] px-3 py-2 text-xs font-bold text-[#1D4ED8] hover:bg-blue-50">Review</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
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