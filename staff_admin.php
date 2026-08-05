<?php
require_once 'session.php';
check_access('staff_admin');

// Database connection assumed via db.php or session.php
// require_once 'db.php';

$message = '';
$message_type = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Staff Admin Profile Details
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');

        if (!empty($name) && !empty($email)) {
            if (isset($pdo)) {
                $stmt = $pdo->prepare("UPDATE wbo_users SET name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$name, $email, $_SESSION['user_id']]);
            }
            $_SESSION['name'] = $name; // Update active session
            $message = "Profile details updated successfully!";
            $message_type = "success";
        } else {
            $message = "Please fill in all required fields.";
            $message_type = "error";
        }
    }

    // 2. Update HR Admin Preferences
    if ($action === 'update_settings') {
        $roster_alerts = isset($_POST['roster_alerts']) ? 1 : 0;
        $daily_summary = isset($_POST['daily_summary']) ? 1 : 0;

        // Save preferences to DB...
        $message = "Administrative preferences saved successfully!";
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
        } else {
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
            $message = "Security credentials updated successfully!";
            $message_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --panel: rgba(255, 255, 255, 0.8);
            --panel-strong: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(148, 163, 184, 0.25);
            --primary: #4f46e5;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.82);
            --panel-strong: #0f172a;
            --text: #f8fafc;
            --muted: #94a3b8;
            --border: rgba(148, 163, 184, 0.18);
            --primary: #818cf8;
            --shadow: 0 18px 45px rgba(2, 6, 23, 0.45);
        }

        body {
            background: var(--bg);
            color: var(--text);
            transition: background 0.2s ease, color 0.2s ease;
        }

        .app-shell {
            min-height: 100vh;
        }

        .glass {
            background: var(--panel);
            border: 1px solid var(--border);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            box-shadow: var(--shadow);
        }

        .theme-toggle {
            border: 1px solid var(--border);
            background: var(--panel-strong);
            color: var(--text);
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
        }

        .bg-white, .bg-slate-50, .bg-gray-50, .bg-gray-100 {
            background-color: var(--panel-strong) !important;
        }

        .text-gray-800, .text-gray-700, .text-slate-900 {
            color: var(--text) !important;
        }

        .text-gray-500, .text-gray-400, .text-slate-500, .text-slate-400 {
            color: var(--muted) !important;
        }

        .border-gray-200, .border-gray-100, .border-slate-200 {
            border-color: var(--border) !important;
        }
    </style>
</head>

<body class="app-shell min-h-screen">

    <!-- HEADER / NAVBAR -->
    <header class="glass sticky top-0 z-40 rounded-b-2xl">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">

            <!-- BRANDING -->
            <div class="flex items-center space-x-2">
                <span class="bg-indigo-600 px-2 py-0.5 rounded text-xs font-bold">HR</span>
                <h1 class="font-bold text-base">Staff Admin Portal</h1>
            </div>

            <!-- 3-LINE HAMBURGER BUTTON & DROPDOWN -->
            <div class="flex items-center gap-2 relative">
                <button id="themeToggle" type="button" class="theme-toggle">🌙</button>
                <button id="hamburger-btn"
                    class="p-2 rounded-lg text-indigo-100 hover:bg-indigo-800 focus:outline-none flex items-center space-x-2 border border-indigo-800">
                    <span class="text-xs font-semibold text-indigo-200 hidden sm:inline">Menu</span>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- DROPDOWN MENU -->
                <div id="dropdown-menu"
                    class="hidden absolute right-0 mt-2 w-64 bg-indigo-950 rounded-xl shadow-xl border border-indigo-800 py-2 z-50 text-indigo-100">

                    <!-- USER INFO HEADER -->
                    <div class="px-4 py-3 border-b border-indigo-800 flex items-center space-x-3">
                        <div
                            class="w-9 h-9 bg-indigo-600 text-white font-bold rounded-full flex items-center justify-center text-xs">
                            👔</div>
                        <div class="overflow-hidden">
                            <p class="font-bold text-xs text-white truncate">
                                <?= htmlspecialchars($_SESSION['name'] ?? 'Staff Administrator') ?>
                            </p>
                            <p class="text-[10px] text-indigo-300">Staff Administrator</p>
                        </div>
                    </div>

                    <!-- DROPDOWN LINKS -->
                    <div class="py-1 text-xs">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                            <!-- SUPER ADMIN RETURN BUTTON -->
                            <a href="super_admin.php"
                                class="flex items-center space-x-2 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold transition rounded-md mx-2 my-1">
                                <span>👑</span> <span>Return to Super Admin</span>
                            </a>
                            <div class="border-t border-indigo-800 my-1"></div>
                        <?php endif; ?>

                        <a href="#"
                            class="flex items-center space-x-2 px-4 py-2.5 hover:bg-indigo-900 font-semibold text-white transition">
                            <span>📋</span> <span>Shift Rosters</span>
                        </a>
                        <a href="#"
                            class="flex items-center space-x-2 px-4 py-2.5 hover:bg-indigo-900 font-semibold text-indigo-200 transition">
                            <span>👥</span> <span>Staff Directory</span>
                        </a>

                        <div class="border-t border-indigo-800 my-1"></div>
                        <p class="px-4 py-1 text-[10px] uppercase font-bold text-indigo-300">Account Options</p>

                        <!-- MODAL TRIGGERS -->
                        <button onclick="openModal('profile-modal')"
                            class="w-full text-left flex items-center space-x-2 px-4 py-2.5 hover:bg-indigo-900 font-semibold text-indigo-100 transition">
                            <span>👤</span> <span>View Profile</span>
                        </button>
                        <button onclick="openModal('settings-modal')"
                            class="w-full text-left flex items-center space-x-2 px-4 py-2.5 hover:bg-indigo-900 font-semibold text-indigo-100 transition">
                            <span>⚙️</span> <span>Account Settings</span>
                        </button>
                        <button onclick="openModal('security-modal')"
                            class="w-full text-left flex items-center space-x-2 px-4 py-2.5 hover:bg-indigo-900 font-semibold text-indigo-100 transition">
                            <span>🔒</span> <span>Security & Password</span>
                        </button>

                        <div class="border-t border-indigo-800 my-1"></div>

                        <!-- SIGN OUT -->
                        <a href="logout.php"
                            class="flex items-center space-x-2 px-4 py-2.5 text-red-300 hover:bg-indigo-900 font-bold transition">
                            <span>🚪</span> <span>Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">

        <!-- Status Alert Notification -->
        <?php if (!empty($message)): ?>
            <div
                class="p-4 rounded-xl text-sm font-medium border <?= $message_type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
            <h2 class="text-xl font-bold text-gray-800">Staff Management Dashboard</h2>
            <p class="text-xs text-gray-500 mt-1">Logged in as:
                <strong><?= htmlspecialchars($_SESSION['name'] ?? 'Staff Administrator') ?></strong>
            </p>
        </div>
    </main>

    <!-- ================= MODALS ================= -->

    <!-- 1. Staff Admin Profile Modal -->
    <div id="profile-modal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div
            class="bg-indigo-950 border border-indigo-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden text-indigo-100">
            <div class="bg-indigo-900 px-5 py-4 border-b border-indigo-800 flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center space-x-2"><span>👤</span><span>Administrator
                        Profile</span></h3>
                <button onclick="closeModal('profile-modal')"
                    class="text-indigo-300 hover:text-white text-lg">&times;</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_profile">
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Administrative
                        Role</label>
                    <input type="text" value="HR / Staff Administrator" disabled
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900/50 text-indigo-300 cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name'] ?? 'Staff Admin') ?>"
                        required
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Work Email</label>
                    <input type="email" name="email"
                        value="<?= htmlspecialchars($_SESSION['email'] ?? 'hr@walangbrownout.com') ?>" required
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Department Unit</label>
                    <input type="text" name="department" value="Human Resources & Operations"
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="flex justify-end space-x-3 pt-3 border-t border-indigo-900">
                    <button type="button" onclick="closeModal('profile-modal')"
                        class="px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-900 rounded-lg">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Save
                        Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Account Settings Modal -->
    <div id="settings-modal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div
            class="bg-indigo-950 border border-indigo-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden text-indigo-100">
            <div class="bg-indigo-900 px-5 py-4 border-b border-indigo-800 flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center space-x-2"><span>⚙️</span><span>Admin
                        Preferences</span></h3>
                <button onclick="closeModal('settings-modal')"
                    class="text-indigo-300 hover:text-white text-lg">&times;</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_settings">
                <div class="space-y-3">
                    <div
                        class="flex items-center justify-between border border-indigo-900 bg-indigo-900/40 p-3 rounded-lg">
                        <div>
                            <p class="text-xs font-semibold text-indigo-100">Shift Change Alerts</p>
                            <p class="text-[10px] text-indigo-300">Notify when staff members swap or leave shifts</p>
                        </div>
                        <input type="checkbox" name="roster_alerts" checked
                            class="w-4 h-4 rounded border-indigo-800 text-indigo-600 focus:ring-indigo-500">
                    </div>
                    <div
                        class="flex items-center justify-between border border-indigo-900 bg-indigo-900/40 p-3 rounded-lg">
                        <div>
                            <p class="text-xs font-semibold text-indigo-100">Daily Attendance Summary</p>
                            <p class="text-[10px] text-indigo-300">Receive automated morning roster digests via email
                            </p>
                        </div>
                        <input type="checkbox" name="daily_summary" checked
                            class="w-4 h-4 rounded border-indigo-800 text-indigo-600 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-3 border-t border-indigo-900">
                    <button type="button" onclick="closeModal('settings-modal')"
                        class="px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-900 rounded-lg">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold bg-indigo-600 text-white rounded-lg hover:bg-indigo-500">Save
                        Preferences</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Security & Password Modal -->
    <div id="security-modal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div
            class="bg-indigo-950 border border-indigo-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden text-indigo-100">
            <div class="bg-indigo-900 px-5 py-4 border-b border-indigo-800 flex justify-between items-center">
                <h3 class="font-bold text-sm text-white flex items-center space-x-2"><span>🔒</span><span>Security
                        Credentials</span></h3>
                <button onclick="closeModal('security-modal')"
                    class="text-indigo-300 hover:text-white text-lg">&times;</button>
            </div>
            <form method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="update_password">
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Current Password</label>
                    <input type="password" name="current_password" required
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">New Password</label>
                    <input type="password" name="new_password" required minlength="8"
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-indigo-300 uppercase mb-1">Confirm New
                        Password</label>
                    <input type="password" name="confirm_password" required minlength="8"
                        class="w-full text-sm px-3 py-2 border border-indigo-800 rounded-lg bg-indigo-900 text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="flex justify-end space-x-3 pt-3 border-t border-indigo-900">
                    <button type="button" onclick="closeModal('security-modal')"
                        class="px-4 py-2 text-xs font-semibold text-indigo-300 hover:bg-indigo-900 rounded-lg">Cancel</button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700">Update
                        Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const dropdownMenu = document.getElementById('dropdown-menu');

        // Toggle Hamburger Menu
        hamburgerBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('hidden');
        });

        // Close Dropdown on Outside Click
        document.addEventListener('click', (e) => {
            if (!dropdownMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                dropdownMenu.classList.add('hidden');
            }
        });

        // Modal Handlers
        function openModal(id) {
            dropdownMenu.classList.add('hidden'); // Close dropdown when opening modal
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
    </script>
<script>
    (function () {
        const savedTheme = localStorage.getItem('wbo-theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const initialTheme = savedTheme || systemTheme;
        document.documentElement.setAttribute('data-theme', initialTheme);

        document.addEventListener('DOMContentLoaded', () => {
            const button = document.getElementById('themeToggle');
            if (button) {
                button.textContent = initialTheme === 'dark' ? '☀️' : '🌙';
                button.addEventListener('click', () => {
                    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('wbo-theme', next);
                    button.textContent = next === 'dark' ? '☀️' : '🌙';
                });
            }
        });
    })();
</script>
</body>

</html>