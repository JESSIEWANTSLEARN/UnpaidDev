<?php

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WalangBrownout</title>
    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #F2F2F2;
            color: #0E5BA8;
        }

        header {
            margin-bottom: 20px;
            padding: 20px;
            text-align: left;
        }

        footer {
            padding: 10px;
            text-align: center;
        }

        header nav {
            background-color: #2C3E50;
            padding: 10px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0E5BA8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #005fc5;
        }
    </style>

</head>
<body>
    <header>
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="https://img.sanishtech.com/u/6559c6ed2b30023d94b79a0932f09814.png" alt="Walang Brown Out Logo" width="45" height="45" loading="lazy" style="max-width:100%;height:auto;">
                
                <div class="hidden sm:block leading-tight">
                    <span class="font-bold text-gray-700 text-[11px] uppercase tracking-[0.18em]">Republic of the Philippines</span>
                    <div class="sss-text-blue text-lg font-extrabold">WALANG BROWN OUT</div>
                </div>
            </div>

            <a href="login.php" class="btn">
                Login to Portal
            </a>
        </div>
        
        <nav>
            
        </nav>
    </header>

    <main>
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
    </main>

    <footer>
        <p><strong>&copy; 2026 WalangBrownout. All rights reserved.</strong></p>
    </footer>
</body>
</html>

<?php

?>