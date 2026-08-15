<!--
Purchasing Manager Dashboard -
    This webpage provides an overview of products requiring replenishment,
    purchase requests awaiting approval, active purchase orders, supplier
    performance, and overdue deliveries. It allows the Purchasing Manager
    to review purchasing priorities and take immediate action.
-->

<?php

require_once __DIR__ . '/session.php';

check_access('Purchasing_Manager');



// Database connection assumed via db.php or session.php
// require_once 'db.php';

$message = '';
$message_type = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Staff Profile Details
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!empty($name) && !empty($email)) {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("UPDATE wbo_users SET name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
            }
            $_SESSION['name'] = $name; // Update active session
            $message = "Staff profile details updated successfully!";
            $message_type = "success";
        } else {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        }
    }

    // 2. Update Field Operations Preferences / Settings
    if ($action === 'update_settings') {
        $dispatch_alerts = isset($_POST['dispatch_alerts']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

        // Save preferences to DB...
        $message = "Field operation preferences saved!";
        $message_type = "success";
    }

    // 3. Update Password
    if ($action === 'update_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $message = "All password fields are required.";
            $message_type = "error";
        } elseif ($new_pass !== $confirm_pass) {
            $message = "New passwords do not match.";
            $message_type = "error";
        } elseif (strlen($new_pass) < 8) {
            $message = "Password must be at least 8 characters long.";
            $message_type = "error";
            //Handle password update logic here, including verifying the current password and updating it in the database.

            ////ISSUE IS HERE BUG ///
       } elseif ($pdo instanceof PDO) {
    $stmt = $pdo->prepare("SELECT password_hash FROM wbo_users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pass, $user['password_hash'])) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE wbo_users SET password_hash = ? WHERE user_id = ?");
        $update->execute([$hashed_pass, $_SESSION['user_id']]);
        $message = "Password updated successfully!";
        $message_type = "success";
    } else {
        $message = "Incorrect current password.";
        $message_type = "error";
    }
} else {
    $message = "Service temporarily unavailable. Please try again later.";
    $message_type = "error";
}


    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchasing Manager - Walang Brown Out</title>
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
                    class="flex items-center gap-3 px-4 py-3 rounded-lg bg-white/10 text-white">
                        <span class="text-sm">Dashboard</span>
                    </a>

                    <a href="Inventory.php"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg text-slate-300 hover:bg-white/5">
                        <span class="text-sm">Inventory</span>
                    </a>

                    <a href="Supplier.php"
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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5 mb-7">
                    <!-- PAGE TITLE -->
                    <div class="mb-7">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#1D4ED8]">
                                Purchasing Manager
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
                
                <!-- Supplier Summary -->
                <section class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"></section>
                
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

<?php

?>
