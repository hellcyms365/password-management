<?php
/** 入口：已登录进入后台，未登录进入登录页 */
require_once __DIR__ . '/includes/functions.php';

if (current_admin()) {
    redirect('dashboard.php');
}
redirect('login.php');
