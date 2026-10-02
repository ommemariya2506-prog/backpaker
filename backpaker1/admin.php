<?php
require_once __DIR__ . '/includes/functions.php';

if (is_admin()) {
    redirect('admin/backpacker_admin_dashboard.php');
}

redirect('admin/login.php');
