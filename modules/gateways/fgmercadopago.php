<?php

require_once("fgmercadopagolib/mercadopago.php");

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function fgmercadopago_MetaData()
{
    return array(
        'DisplayName' => 'FG - MercadoPago',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function fgmercadopago_config() {
    $configarray = array(
        "FriendlyName" => array(
            "Type" => "System",
            "Value" => "FG - MercadoPago",
        ),

        "fgmp_clientid" => array(
            "FriendlyName" => "Client ID",
            "Type" => "text",
            "Size" => "30",
            "Description" => "\"Client ID\" de la API (client_id)."
        ),

        "fgmp_clientsecret" => array(
            "FriendlyName" => "Client Secret",
            "Type" => "text",
            "Size" => "60",
            "Description" => "\"Client Secret\" de la API (client_secret)."
        ),

        "fgmp_button" => array(
            "FriendlyName" => "C&oacute;digo HTML a insertar en la factura",
            "Type" => "text",
            "Size" => "50",
            "Description" => "Ejemplo: &lt;img src=\"https://www.dominio.com/botondepago.jpg\" /&gt;"
        ),

        "fgmp_version" => array(
            "FriendlyName" => "Versi&oacute;n",
            "Type" => "dropdown",
            "Options" => "1.0.0",
            "Description" => "Versi&oacute;n del m&oacute;dulo."
        ),

        "fgmp_testmode" => array(
            "FriendlyName" => "Modo Prueba",
            "Type" => "yesno",
            "Description" => "Chequear este campo para el modo de prueba."
        ),
    );

    return $configarray;
}

function fgmercadopago_link($params) {
    // mercadopago api
    $mp = new MP($params["fgmp_clientid"], $params["fgmp_clientsecret"]);

    $preference = $mp->create_preference(array(
        "external_reference" => $params["invoiceid"],
        "items" => array(
            array(
                "title" => $params["description"],
                "quantity" => 1,
                "currency_id" => $params["currency"],
                "unit_price" => (FLOAT)$params["amount"]
            )
        )
    ));

    $link = $preference["response"][$params["fgmp_testmode"] ? "sandbox_init_point" : "init_point"];
    $button = $params["fgmp_button"] ? html_entity_decode($params["fgmp_button"]) : '<img src="https://www.mercadopago.com/org-img/MP3/buy_now_02.gif" alt="Pagar" />';

    return '<a href="' . $link . '">' . $button . '</a>';
}

?>