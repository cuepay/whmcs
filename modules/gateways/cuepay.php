<?php
/**
 * WHMCS Cuepay Payment Gateway Module
 *
 * This Payment Gateway modules allow you to integrate cuepay with the WHMCS platform.
 *
 * For more information, please refer to our online documentation -
 * 
 *  https://cuepay.com/developers
 * 
 *  https://github.com/cuepay/whmcs
 *  
 *  Contact - support@cuepay.com
 *
 * @copyright Copyright (c) Freeman Global Solutions
 * 
 */

if (!defined("WHMCS")) {
    
    die("This file cannot be accessed directly");
}

/**
 * Module related meta data.
 * Values returned here are used to determine module related capabilities and settings.
 *
 * @see https://cuepay.com/developers
 *
 * @return array
 */
function cuepay_MetaData()
{
    return array(
        'DisplayName' => 'Cuepay (Accepts Debit/Credit Cards)',
        'APIVersion' => '1.0', // Use API Version 1.0
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options
 *
 * @return array
 */
function cuepay_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Cuepay (Accepts Debit/Credit Cards)',
        ),
        // a text field type allows for single line text input
        'merchant' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your Merchant ID here',
        ),
        // a password field type allows for masked text input
        'secret' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Enter Secret key here',
        ),
        // the yesno field type displays a single checkbox option
        'sandbox' => array(
            'FriendlyName' => 'Sandbox',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
    );
}

/**
 * Payment link.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://cuepay.com/developers
 *
 * @return string
 */
function cuepay_link($params)
{

    $url = 'https://cuepay.com/secure/?pay=invoice';

    $postfields = array();


    $postfields['merchant' ] = $params['merchant' ];
    $postfields['reference'] = $params['invoiceid'];
    $postfields['amount'   ] = $params["amount"];
    $postfields['customer' ] = $params['clientdetails']['email'];
    $postfields['narration'] = $params["description"];
    $postfields['currency' ] = $params["currency" ];
    $postfields['redirect' ] = $params['returnurl'];
    $postfields['callback' ] = $params['systemurl'].'/modules/gateways/callback/'.$params['paymentmethod'].'.php';
    $postfields['sandbox'  ] = $params['sandbox'];
    
    $htmlOutput = '<form method="post" action="'.$url.'">';

    foreach ($postfields as $k => $v)
    {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
    }

    $htmlOutput .= '<input type="submit" value="'.$params['langpaynow'].'" />';

    $htmlOutput .= '</form>'; return $htmlOutput;

}
