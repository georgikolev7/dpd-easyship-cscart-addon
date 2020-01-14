<?php

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Pdf;
use Tygh\Http;
use Tygh\Languages\Languages;

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

function fn_dpd_easyship_generate_parcel($order_id)
{
    // DPD Easyship Settings
    $settings = fn_dpd_easyship_get_settings();
    
    // Order info
    $order_info = fn_get_order_info($order_id, false, true, false, true);
    
    if (empty($order_info)) {
        return false;
    }
    
    if ($order_info['shipping'][0]['module'] !== 'dpd_easyship') {
        return false;
    }
    
    $sender_info = $order_info['product_groups'][0]['package_info']['origination'];
    $packages = $order_info['product_groups'][0]['package_info']['packages'][0];
    
    $data = [
        // DPD settings
        'username' => $settings['username'],
        'password' => $settings['password'],
        'parcel_type' => $order_info['shipping'][0]['service_code'],
        
        // Order data
        'name1' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'street' => $order_info['b_address'],
        'city' => $order_info['b_city'],
        'country' => $order_info['b_country'],
        'pcode' => $order_info['b_zipcode'],
        'email' => $order_info['email'],
        'phone' => preg_replace('/\D+/', '', $order_info['b_phone']),
        'weight' => $packages['weight'],
        'num_of_parcel' => 1,
        'order_number' => $order_id
    ];
    
    $response = Http::post(DPD_EASYSHIP_API_URL . 'parcel/parcel_import', $data, ['question_mark' => true]);
    $response = json_decode($response, true);
    
    if (isset($response['status']) && ($response['status'] == 'ok')) {
        return $response['pl_number'][0];
    }
    
    return false;
}


function fn_dpd_easyship_generate_label($order_id)
{
    // DPD Easyship Settings
    $settings = fn_dpd_easyship_get_settings();
    
    // Order info
    $order_info = fn_get_order_info($order_id, false, true, false, true);
    list($shipments) = fn_get_shipments_info(array('order_id' => $order_id, 'advanced_info' => true));
    
    if (empty($order_info)) {
        return false;
    }
    
    if ($order_info['shipping'][0]['module'] !== 'dpd_easyship') {
        return false;
    }
    
    if (empty($shipments[0]['tracking_number'])) {
        return false;
    }
    
    $parcel_number = $shipments[0]['tracking_number'];
    
    $data = [
        'username' => $settings['username'],
        'password' => $settings['password'],
        'parcels' => $parcel_number
    ];
    
    $response = Http::post(DPD_EASYSHIP_API_URL . 'parcel/parcel_print', $data, array(
        'question_mark' => true,
        'binary_transfer' => true
    ));
    
    if (!empty($response)) {
        header('Content-type: application/pdf');
        header('Cache-Control: public'); 
        header("Content-disposition: attachment; filename=parcel_label_dpd_" . $order_id . ".pdf");
        header('Content-Length: '.strlen($response));
        echo $response;
        
        exit;
    }
    
    return false;
}

/**
 * A hook for creating DPD parcel after the creation of a shipment in CS-Cart
 *
 * @param array $shipment_data
 * @param array $order_info
 * @param array $group_key
 */
function fn_dpd_easyship_create_shipment(&$shipment_data, $order_info, $group_key)
{
    if ($shipment_data['carrier'] == 'dpd_easyship') {
        $tracking_number = fn_dpd_easyship_generate_parcel($order_info['order_id']);
        if (!empty($tracking_number)) {
            $shipment_data['tracking_number'] = $tracking_number;
        }
    }
}

function fn_dpd_easyship_get_shipments($params, &$fields_list)
{
    $fields_list[] = '?:shipments.carrier';
    $fields_list[] = '?:shipments.shipping_id';
    $fields_list[] = '?:shipments.tracking_number';
}

/**
 * A hook for adding links to DPD labels to shipments
 *
 * @param array $shipments Array of shipments
 */
function fn_dpd_easyship_get_shipments_info_post(&$shipments)
{
    if (Registry::get('runtime.controller') == 'shipments') {
        foreach ($shipments as &$shipment_data) {
            if ($shipment_data['carrier'] == 'dpd_easyship') {
                $service_params = fn_get_shipping_params($shipment_data['shipping_id']);
            }
        }
    }
}

function fn_dpd_easyship_install()
{
    $services = array(
        array(
            'status' => 'A',
            'module' => 'dpd_easyship',
            'code' => 'D',
            'sp_file' => '',
            'description_code' => 'DPD Classic',
        ),
        array(
            'status' => 'A',
            'module' => 'dpd_easyship',
            'code' => 'd_cod',
            'sp_file' => '',
            'description_code' => 'DPD Classic COD',
        ),
        array(
            'status' => 'A',
            'module' => 'dpd_easyship',
            'code' => 'd_docret',
            'sp_file' => '',
            'description_code' => 'DPD Classic Docret',
        ),
    );

    foreach ($services as $service) {
        $service_id = db_get_field('SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s', $service['module'], $service['code']);
        if (empty($service_id)) {
            $service_id = db_query('INSERT INTO ?:shipping_services ?e', $service);

            foreach (Languages::getAll() as $lang_code => $lang_data) {
                $data = array(
                    'service_id' => $service_id,
                    'description' => $service['description_code'],
                    'lang_code' => $lang_code
                );
                
                db_replace_into('shipping_service_descriptions', $data);
            }
        }
    }
}

function fn_dpd_easyship_uninstall()
{
    $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', 'dpd_easyship');
    if ($service_ids) {
        db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
        db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
    }
}

function fn_dpd_easyship_pre_place_order(&$cart, $allow, $product_groups)
{
    
}

/**
 * Hook handler: sets cart 'calculate_shipping' param according to selected point.
 */
function fn_dpd_easyship_checkout_update_steps_before_update_user_data(&$cart, $auth, $params, $user_id, $user_data)
{
    $cart['calculate_shipping'] = 'A';
}

/**
 * Hook handler: after fetching shipping data
 */
function fn_dpd_easyship_get_shipping_info_after_select($shipping_id, $lang_code, &$shipping)
{
    if (empty($shipping['service_id'])) {
        return;
    }
    
    $service = fn_get_shipping_service_data($shipping['service_id']);
    if ($service['module'] === 'dpd_easyship') {
        $shipping['allow_multiple_locations'] = true;
    }
}