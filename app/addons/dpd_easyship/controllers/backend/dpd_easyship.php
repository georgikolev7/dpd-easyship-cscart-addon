<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'generate_parcel') {
    fn_dpd_easyship_generate_parcel($_GET['order_id']);
    exit();
}

if ($mode == 'generate_label') {
    fn_dpd_easyship_generate_label($_GET['order_id']);
    exit();
}
