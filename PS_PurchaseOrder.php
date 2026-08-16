<!--
Purchasing Staff Dashboard -
    
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

                    <a href="purchasing_staff.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Dashboard</span>
                    </a>

                </div>

                <div class="space-y-1">

                    <a href="PS_PurchaseOrder.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10 text-white">
                        <span class="text-sm">Purchase Order</span>
                    </a>

                </div>

                <div class="space-y-1">

                    <a href="PS_Supplier.php"
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
                                Purchase Order
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

                <!-- Filters -->
                <div class="flex flex-wrap gap-3 mb-5">
                    <input type="text" placeholder="Search PO number or supplier…"
                        class="border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm flex-1 min-w-[200px] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8]/30">
                    <select class="border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm text-[#374151] focus:outline-none focus:ring-2 focus:ring-[#1D4ED8]/30">
                        <option value="">All Statuses</option>
                        <option>Draft</option>
                        <option>Submitted</option>
                        <option>Approved</option>
                        <option>Received</option>
                    </select>
                </div>
    
                <!-- PO Table -->
                <div class="bg-white rounded-xl border border-[#E5E7EB] overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[#E5E7EB] text-xs uppercase tracking-wider text-[#6B7280]">
                                <th class="px-5 py-4 text-left">PO Number</th>
                                <th class="px-5 py-4 text-left">Supplier</th>
                                <th class="px-5 py-4 text-left">Date Created</th>
                                <th class="px-5 py-4 text-right">Total Amount</th>
                                <th class="px-5 py-4 text-center">Status</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F3F4F6]">
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-4 font-mono font-semibold text-[#1D4ED8]">PO-0042</td>
                                <td class="px-5 py-4 text-[#374151]">ABC Hardware</td>
                                <td class="px-5 py-4 text-[#6B7280]">Jun 18, 2026</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#2C3E50]">₱12,500.00</td>
                                <td class="px-5 py-4 text-center"><span class="badge badge-submitted">Submitted</span></td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-[#1D4ED8] hover:underline text-xs font-semibold">View</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-4 font-mono font-semibold text-[#1D4ED8]">PO-0041</td>
                                <td class="px-5 py-4 text-[#374151]">XYZ Traders</td>
                                <td class="px-5 py-4 text-[#6B7280]">Jun 15, 2026</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#2C3E50]">₱8,200.00</td>
                                <td class="px-5 py-4 text-center"><span class="badge badge-approved">Approved</span></td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-[#1D4ED8] hover:underline text-xs font-semibold">View</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-4 font-mono font-semibold text-[#1D4ED8]">PO-0040</td>
                                <td class="px-5 py-4 text-[#374151]">FastPack Co.</td>
                                <td class="px-5 py-4 text-[#6B7280]">Jun 12, 2026</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#2C3E50]">₱3,750.00</td>
                                <td class="px-5 py-4 text-center"><span class="badge badge-received">Received</span></td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-[#1D4ED8] hover:underline text-xs font-semibold">View</button>
                                </td>
                            </tr>
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-5 py-4 font-mono font-semibold text-[#1D4ED8]">PO-0039</td>
                                <td class="px-5 py-4 text-[#374151]">SupplyCentral PH</td>
                                <td class="px-5 py-4 text-[#6B7280]">Jun 10, 2026</td>
                                <td class="px-5 py-4 text-right font-semibold text-[#2C3E50]">₱21,000.00</td>
                                <td class="px-5 py-4 text-center"><span class="badge badge-draft">Draft</span></td>
                                <td class="px-5 py-4 text-center">
                                    <button class="text-[#1D4ED8] hover:underline text-xs font-semibold mr-2">Edit</button>
                                    <button class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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