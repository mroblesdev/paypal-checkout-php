<?php
require __DIR__ . "/vendor/autoload.php";

use PaypalServerSdkLib\PaypalServerSdkClientBuilder;
use PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder;
use PaypalServerSdkLib\Models\Builders\OrderRequestBuilder;
use PaypalServerSdkLib\Models\Builders\PurchaseUnitRequestBuilder;
use PaypalServerSdkLib\Models\Builders\AmountWithBreakdownBuilder;
use PaypalServerSdkLib\Environment;
use PaypalServerSdkLib\Models\Builders\PaypalWalletBuilder;
use PaypalServerSdkLib\Models\Builders\PaypalWalletExperienceContextBuilder;
use PaypalServerSdkLib\Models\Builders\PaymentSourceBuilder;
use PaypalServerSdkLib\Models\PaypalWalletContextShippingPreference;

/**
 * Cambie su Client ID y Client Secret
 * @see https://developer.paypal.com/dashboard/
 */
$PAYPAL_CLIENT_ID = "";
$PAYPAL_CLIENT_SECRET = "";
$PROJECT_NAME = "/paypal-checkout-php"; // Nombre de la carpeta del proyecto, sino tiene dejar vacio

$client = PaypalServerSdkClientBuilder::init()
    ->clientCredentialsAuthCredentials(
        ClientCredentialsAuthCredentialsBuilder::init(
            $PAYPAL_CLIENT_ID,
            $PAYPAL_CLIENT_SECRET
        )
    )
    ->environment(Environment::SANDBOX) // Para producción usar Environment::PRODUCTION
    ->build();


/**
 * Decodifica una respuesta JSON y devuelve una matriz que contiene
 * la respuesta decodificada y el código de estado HTTP.
 */
function handleResponse($response)
{
    $jsonResponse = json_decode($response->getBody(), true);
    return [
        "jsonResponse" => $jsonResponse,
        "httpStatusCode" => $response->getStatusCode(),
    ];
}


$endpoint = $_SERVER["REQUEST_URI"];

if ($endpoint === "{$PROJECT_NAME}/") {
    try {
        $response = [
            "message" => "El servidor está en ejecución",
        ];
        header("Content-Type: application/json");
        echo json_encode($response);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        http_response_code(500);
    }
}

/**
 * Crea una orden para iniciar la transacción
 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create
 */
function createOrder($cart)
{
    global $client;

    $id =  $cart[0]['id'];
    $cantidad = $cart[0]['quantity'];

    $orderBody = [
        "body" => OrderRequestBuilder::init("CAPTURE", [
            PurchaseUnitRequestBuilder::init(
                AmountWithBreakdownBuilder::init("MXN", "35000")
                    ->build()
            )
                ->description('COMPRA') // Descripción general
                ->build()
        ])
            // Opciones para mostrar nombre del negicio, cargar idioma y no solicitar domincio
            ->paymentSource(
                PaymentSourceBuilder::init()
                    ->paypal(
                        PaypalWalletBuilder::init()
                            ->experienceContext(
                                PaypalWalletExperienceContextBuilder::init()
                                    ->brandName('CDP')
                                    ->locale('es')
                                    ->shippingPreference(PaypalWalletContextShippingPreference::NO_SHIPPING)
                                    ->build()
                            )
                            ->build()
                    )
                    ->build()
            )
            ->build(),
        'prefer' => 'return=minimal'
    ];

    $apiResponse = $client->getOrdersController()->createOrder($orderBody);

    return handleResponse($apiResponse);
}

if ($endpoint === "{$PROJECT_NAME}/orders") {
    $data = json_decode(file_get_contents("php://input"), true);
    $cart = $data["cart"];
    header("Content-Type: application/json");
    try {
        $orderResponse = createOrder($cart);
        echo json_encode($orderResponse["jsonResponse"]);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
        http_response_code(500);
    }
}
