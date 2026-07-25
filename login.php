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
    </style>

</head>
<body>
    <main>
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
    </main>
</body>
</html>

<?php

?>