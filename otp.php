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
    <!-- FLOATING TOP NOTIFICATION POP-UP -->
    <div class="fixed top-5 left-1/2 -translate-x-1/2 z-50 w-[90%] max-w-md animate-notification">
        <div
            class="bg-white border-l-4 border-blue-600 rounded-xl shadow-xl p-4 border border-gray-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div
                    class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center font-bold text-xl shrink-0">
                    📩
                </div>
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">New Message Notification
                    </h4>
                    <p class="text-xs text-gray-700 font-medium mt-0.5">
                        Your OTP Code is: <span
                            class="font-extrabold text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-sm tracking-wider"></span>
                    </p>
                </div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()"
                class="text-gray-400 hover:text-gray-600 text-sm font-bold pl-2">✕</button>
        </div>
    </div>

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
        
    </main>

    <footer>
        <p><strong>&copy; 2026 WalangBrownout. All rights reserved.</strong></p>
    </footer>
</body>
</html>

<?php

?>