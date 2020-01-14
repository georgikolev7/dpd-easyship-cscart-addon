<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'update') {
    if (!empty($_REQUEST['shipping_id'])) {
        $module = !empty($_REQUEST['module']) ? $_REQUEST['module'] : '';
        if ($module == 'dpd_easyship') {
            $shipping = Tygh::$app['view']->getTemplateVars('shipping');
            $shipping['service_params']['enabled'] = true;
        }
    }
}