<?php

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        WalangBrownout - Signup
    </title>

    <link rel="icon" type="image/png" href="image/Logo.png"> <!-- Placeholder for client icon -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #F2F2F2;
            color: #0E5BA8;
            margin: 0;
        }

        header {
            text-align: left;
        }

        header nav {
            background-color: #2C3E50;
            padding: 20px;
            margin-bottom: 20px;
        }

        footer {
            text-align: center;
        }

        .sss-blue {
            background-color: #0E5BA8;
        }

    </style>

</head>


<body>

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
        </div>
        
        <nav>
            
        </nav>

    </header>

    <!-- Main Content --> 
    <main class="max-w-md mx-auto mt-16 mb-16 bg-white shadow-lg rounded-xl p-8" >

        <h1 class="text-2xl font-bold text-gray-700 mb-6">
            Create an Account
        </h1>

        <form
            action="signup.php"
            method="POST"
            class="space-y-4"
        >
            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Username
                </label>

                <input
                    type="text"
                    name="username"
                    required
                    placeholder="Your_Username"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Email Address
                </label>

                <input
                    type="email"
                    name="email"
                    required
                    placeholder="name@example.com"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Contact Number
                </label>

                <input
                    type="Number"
                    name="contact_number"
                    required
                    placeholder="123-456-7890"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <div>

                <label
                    class="block text-xs font-bold text-gray-700 mb-1"
                >
                    Password
                </label>


                <input
                    type="password"
                    name="password"
                    required
                    placeholder="••••••••"

                    class="w-full px-3 py-2 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                >

            </div>

            <button
                type="submit"
                class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Create Account
            </button>
            <p class="text-center text-sm text-gray-600 mt-4">
                Already have an account? <a href="login.php" class="text-blue-500 hover:underline">Login here</a>
            </p>
        </form>

    </main>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-slate-200 bg-#F2F2F2/80 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-6 py-6 text-center text-slate-600 text-sm">
            <strong>&copy; 2026 WalangBrownOut.</strong>
            All rights reserved.
        </div>
    </footer>


</body>

</html>