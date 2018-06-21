<?php
/**
 * WHMCS Cuepay Payment Callback File
 * *
 * This callback verifies that the Cuepay Payment Gateway Module is active,
 * validates an Invoice ID, checks for the existence of a Transaction ID,
 * Logging the Transaction for debugging and adding the Payment to an Invoice.
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

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.

if (!$gatewayParams['type']) {

    die("Module not activated");
}

// Retrieve data returned in payment gateway callback

$merchant  = $gatewayParams['merchant'];

$secret    = $gatewayParams['secret'  ];


$reference = filter_input(INPUT_POST, 'reference');

$query     = http_build_query( array('merchant'=>$merchant, 'reference'=>$reference, 'token'=> hash ('sha512', $merchant.$reference.$secret) ) );

$request   = 'https://cuepay.com/secure/query/?'.$query;

$response  = json_decode(@file_get_contents($request));

$success   = $response != null && $response->status === 'success' && $response->code === '00';


$data = (object) array(

    'status'        =>  $response == null ? 'Unknown': $response->status,
    'invoice_id'    =>  $response == null ? $reference: $response->reference,
    'transaction_id'=>  $response == null ? null: $response->invoice,
    'amount'        =>  $response == null ? null: $response->amount,
    'fees'          =>  $response == null ? null: $response->total - $response->amount
);

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($data->invoice_id, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($data->transaction_id);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $_POST, $data->status);

if ($success === true) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $data->invoice_id,
        $data->transaction_id,
        $data->amount,
        $data->fees,
        $gatewayModuleName
    );

}
