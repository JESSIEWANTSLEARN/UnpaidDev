<?php
require_once __DIR__ . '/session.php';

if (!empty($_SESSION['logged_in']) && !empty($_SESSION['role'])) {
    redirect_to_dashboard($_SESSION['role']);
}

$error = '';
$success = '';
$demo_users = [
    'admin@wbo.ph' => ['id' => 1, 'password' => 'admin123', 'name' => 'Jerome Raymundo', 'role' => 'super_admin'],
    'warehouse@wbo.ph' => ['id' => 2, 'password' => 'warehouse123', 'name' => 'Jessie Palarao', 'role' => 'warehouse_admin'],
    'hr@wbo.ph' => ['id' => 3, 'password' => 'hr123', 'name' => 'Jhon Paul Villasanta', 'role' => 'staff_admin'],
    'staff@wbo.ph' => ['id' => 4, 'password' => 'staff123', 'name' => 'Taironne James Sieteriales', 'role' => 'staff'],
    'user@wbo.ph' => ['id' => 5, 'password' => 'user123', 'name' => 'John Lorena', 'role' => 'user']
];





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
            background-color: #F2F2F2;
            color: #0E5BA8;
        }

        header {
            text-align: left;
        }

        footer {
            text-align: center;
        }

        header nav {
            background-color: #2C3E50;
            padding: 10px;
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
        </div>
        
        <nav>
            
        </nav>
    </header>

    <main>
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Welcome Back</h2>
            <p class="text-xs text-gray-500 mt-1">Sign in to access your account portal</p>
        </div>

        <form action="login.php" method="POST" class="space-y-4">
            <input type="hidden" name="action_type" value="login_password">

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Email Address</label>
                <input type="email" name="email" required placeholder="name@example.com" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <button type="submit" class="w-full sss-blue hover:bg-blue-800 text-white font-bold py-2.5 rounded-lg text-xs transition shadow">
                Continue to OTP
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-gray-100">
            <p class="text-[10px] uppercase tracking-wider font-bold text-gray-400 mb-2">
                Demo Logins (Pass: <code>admin123</code> | OTP: <code>123456</code>)
            </p>
            <div class="grid grid-cols-2 gap-1.5 text-[11px] text-gray-600">
                <span class="bg-gray-50 p-1.5 rounded border border-gray-200">👑 admin@wbo.ph</span>
                <span class="bg-gray-50 p-1.5 rounded border border-gray-200">📦 warehouse@wbo.ph</span>
                <span class="bg-gray-50 p-1.5 rounded border border-gray-200">👔 hr@wbo.ph</span>
                <span class="bg-gray-50 p-1.5 rounded border border-gray-200">🛠️ staff@wbo.ph</span>
            </div>
        </div>
    </main>
    
    <footer>
        <p><strong>&copy; 2026 WalangBrownout. All rights reserved.</strong></p>
    </footer>
</body>
</html>

<?php

?>