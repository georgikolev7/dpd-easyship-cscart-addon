<?php

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('MODULE_NAME', 'dpd_easyship');
fn_define('DPD_EASYSHIP_API_URL', 'https://easyship.si/api/');

fn_register_hooks(
    'pre_place_order',
    'create_shipment',
    'get_shipments',
    'get_shipments_info_post',
    'checkout_update_steps_before_update_user_data',
    'get_shipping_info_after_select'
);