<?php   
// session.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

function normalize_role($role)
{
    $role = $role ?? 'user';

    $map = [
        'warehouse_manager' => 'warehouse_admin',
        'Warehouse_Staff' => 'warehouse_admin',
        'Operations_Manager' => 'staff_admin',
        'Purchasing_Manager' => 'staff',
        'Admin' => 'super_admin',
    ];

    return $map[$role] ?? $role;
}
function redirect_to_dashboard($role)
{
    $role = normalize_role($role);

    $redirects = [
        'super_admin' => 'super_admin.php',
        'warehouse_admin' => 'warehouse_admin.php',
        'staff_admin' => 'staff_admin.php',
        'staff' => 'staff.php',
        'user' => 'user.php'
    ];
 $target = $redirects[$role] ?? 'user.php';
    header('Location: ' . $target);
    exit();
}
function check_access($required_role)
{
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }


?>
