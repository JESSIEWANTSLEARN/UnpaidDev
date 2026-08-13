<?php
// session.php


// ==========================================
// START SESSION
// ==========================================


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Idle timeout: 20 minutes
$idle_timeout = 1 * 60;

if (!empty($_SESSION['logged_in'])) {

  
    if (isset($_SESSION['last_activity'])) {

        $inactive_time = time() - $_SESSION['last_activity'];

        if ($inactive_time >= $idle_timeout) {

             session_unset();
            session_destroy();

            header("Location: login.php?timeout=1");
            exit;
        }
    }
    // User is still active
    $_SESSION['last_activity'] = time();
}




require_once __DIR__ . '/config.php';


// ==========================================
// NORMALIZE / VALIDATE ROLE
// ==========================================

function normalize_role($role)
{
    $valid_roles = [

        'super_admin',

        'Operations_Manager',

        'Purchasing_Manager',

        'Warehouse_Admin',

        'Sales_Manager',

        'Purchasing_Staff',

        'Inventory_Controller',

        'Sales_Staff',

        'User_Admin',

        'System_User'

    ];


    if (in_array($role, $valid_roles, true)) {
        return $role;
    }


    // Safe fallback
    return 'System_User';
}


// ==========================================
// REDIRECT USER TO DASHBOARD
// ==========================================

function redirect_to_dashboard($role)
{
    $role = normalize_role($role);


    $redirects = [

        'super_admin' =>
            'super_admin.php',

        'Operations_Manager' =>
            'operations_manager.php',

        'Purchasing_Manager' =>
            'purchasing_manager.php',

        'Warehouse_Admin' =>
            'warehouse_admin.php',

        'Sales_Manager' =>
            'sales_manager.php',

        'Purchasing_Staff' =>
            'purchasing_staff.php',

        'Inventory_Controller' =>
            'inventory_controller.php',

        'Sales_Staff' =>
            'sales_staff.php',

        'User_Admin' =>
            'user_admin.php',

        'System_User' =>
            'user.php'

    ];


    $target =
        $redirects[$role] ?? 'user.php';


    header(
        'Location: ' . $target
    );

    exit();
}


// ==========================================
// CHECK IF USER IS LOGGED IN
// ==========================================

function check_login()
{
    if (
        empty($_SESSION['logged_in']) ||
        empty($_SESSION['user_id'])
    ) {

        header(
            'Location: login.php'
        );

        exit();
    }


    return true;
}


// ==========================================
// CHECK ROLE ACCESS
// ==========================================

function check_access($required_role)
{
    // User must be logged in
    check_login();


    $user_role =
        normalize_role(
            $_SESSION['role'] ?? ''
        );


    $required_role =
        normalize_role(
            $required_role
        );


    // ======================================
    // SUPER ADMIN
    // ======================================
    //
    // Super admin can access every page.
    //
    // ======================================

    if ($user_role === 'super_admin') {

        return true;
    }


    // ======================================
    // ROLE MUST MATCH
    // ======================================

    if ($user_role !== $required_role) {

        redirect_to_dashboard(
            $user_role
        );
    }


    return true;
}
?>