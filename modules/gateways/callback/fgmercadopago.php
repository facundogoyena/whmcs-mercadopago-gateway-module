<?php

require_once("../fgmercadopagolib/mercadopago.php");

if (file_exists("../../../init.php")) {
    include("../../../init.php");

    $whmcs->load_function("gateway");
    $whmcs->load_function("invoice");
} else {
    include("../../../dbconnect.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");
    include("../../../includes/invoicefunctions.php");
}

use WHMCS\Database\Capsule;

$gatewaymodule = "fgmercadopago";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) {
    logTransaction($GATEWAY["name"], $_REQUEST, "Module not activated.");
    http_response_code(500);
    die();
}

$mp = new MP($GATEWAY['fgmp_clientid'], $GATEWAY['fgmp_clientsecret']);

if ($GATEWAY["fgmp_testmode"]) {
    $mp->sandbox_mode(TRUE);
}

try {
    $response = $mp->get_payment_info($_GET["id"]);

    if ($response["status"] !== 200) {
        logTransaction($GATEWAY["name"], $response, "MercadoPago API error.");
        http_response_code(500);
        die();
    }

    $payment = $response["response"]["collection"];

    if ($payment["status"] !== "approved") {
        http_response_code(200);
        die();
    }

    $operationDetails = array(
        "ID" => $payment["external_reference"],
        "TransactionNumber" => $payment["id"],
        "Payment" => (FLOAT)$payment["transaction_amount"],
        "Taxes" => (FLOAT)$payment["transaction_amount"] - (FLOAT)$payment["net_received_amount"]
    );

    $invoiceId = checkCbInvoiceID($operationDetails["ID"], $GATEWAY["name"]);
    checkCbTransID($operationDetails["TransactionNumber"]);

    addInvoicePayment(
        $operationDetails["ID"],
        $operationDetails["TransactionNumber"],
        $operationDetails["Payment"],
        $operationDetails["Taxes"],
        $gatewaymodule
    );

    logTransaction($GATEWAY["name"], $operationDetails, "Pago imputado exitosamente.");
    http_response_code(200);
    die();
} catch (MercadoPagoException $e) {
    $message = $e->getMessage();

    logTransaction($GATEWAY["name"], $message, "MercadoPago API error.");
    http_response_code($message === "not_found" ? 400 : 500);
    die();
}

logTransaction($GATEWAY["name"], $_REQUEST, "Error no esperado.");
http_response_code(500);
die();
