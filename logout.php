<?php
/** 退出登录 */
require_once __DIR__ . '/includes/functions.php';
destroy_session();
redirect('login.php');
