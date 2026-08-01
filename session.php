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




?>
