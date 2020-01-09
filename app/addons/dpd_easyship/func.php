<?php

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Pdf;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * @param $lang_code
 * @return mixed
 */
function fn_dpd_easyship_get_settings($lang_code = DESCR_SL)
{
    $settings = Settings::instance()->getValues('dpd_easyship', 'ADDON');
    return $settings['general'];
}

function fn_dpd_easyship_install()
{
    
}

function fn_dpd_easyship_uninstall()
{
    
}