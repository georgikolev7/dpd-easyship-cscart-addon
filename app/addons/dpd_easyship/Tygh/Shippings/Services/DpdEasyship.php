<?php

namespace Tygh\Shippings\Services;

use Tygh\Shippings\IService;
use Tygh\Shippings\IPickupService;
use Tygh\Shippings\Shippings;
use Tygh\Registry;
use Tygh\Tygh;

class DpdEasyship implements IService, IPickupService
{
    /**
     * Availability multithreading in this module
     *
     * @var bool $_allow_multithreading
     */
    private $allow_multithreading = false;
    
    /** @var array $shipping_info Shipping data */
    protected $shipping_info;
    protected $client;
    protected $weight;

    /**
     * Checks if shipping service allows to use multithreading
     *
     * @return bool true if allow
     */
    public function allowMultithreading()
    {
        return $this->allow_multithreading;
    }

    public function processErrors($response) {}

    /**
     * Sets data to internal class variable
     *
     * @param  array $shipping_info
     *
     * @return array|void
     */
    public function prepareData($shipping_info)
    {
        $this->shipping_info = $shipping_info;
        $this->company_id = Registry::get('runtime.company_id');
    }
    
    /**
     * Prepare request information
     *
     * @return array Prepared data
     */
    public function getRequestData()
    {
        return [];
    }

    /**
     * Process simple request to shipping service server
     *
     * @return string Server response
     */
    public function getSimpleRates()
    {
        return fn_get_shipping_destinations($this->shipping_info['shipping_id'], $this->shipping_info, CART_LANGUAGE);
    }
    
    /**
     * Gets shipping cost and information about possible errors
     *
     * @param array $destination_rates Shipping rates
     *
     * @return array Shipping cost and errors
     */
    public function processResponse($destination_rates)
    {
        $result = [
            'cost'           => 0,
            'error'          => false,
            'delivery_time'  => false,
            'destination_id' => false,
        ];
        
        $location = $this->shipping_info['package_info']['location'];
        
        $destination_id = fn_get_available_destination($location);
        if (empty($destination_id)) {
            $result['error'] = __('destination_nothing_found');
            return $result;
        } else {
            $result['destination_id'] = $destination_id;
        }
        
        return $result;
    }
    
    /**
     * @inheritdoc
     */
    public function getPickupMinCost()
    {
        $min_cost = 0;

        return $min_cost;
    }

    /**
     * @inheritdoc
     */
    public function getPickupPoints()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getPickupPointsQuantity()
    {
        return false;
    }
}