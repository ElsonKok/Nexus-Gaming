<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/secrets.php');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!is_array($input) || empty($input['items']) || !is_array($input['items'])) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(array('error' => 'No cart items provided.'));
    exit;
}

try {
    if (empty($stripeSecretKey)) {
        throw new Exception('Stripe secret key is missing in secrets.php.');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'mercury.swin.edu.au';
    $defaultBase = $scheme . '://' . $host . '/cos30043/s106200220/Project';
    $returnBase = !empty($input['returnUrl']) ? rtrim($input['returnUrl'], '/') : $defaultBase;

    $postFields = array(
        'mode' => 'payment',
        'success_url' => $returnBase . '/checkout?success=true&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $returnBase . '/checkout?canceled=true'
    );

    if (!empty($input['email'])) {
        $postFields['customer_email'] = $input['email'];
    }

    $lineIndex = 0;
    $subtotalCents = 0;

    foreach ($input['items'] as $item) {
        $title = !empty($item['title']) ? (string)$item['title'] : 'Nexus Game';
        $qty = isset($item['quantity']) ? max(1, (int)$item['quantity']) : 1;
        $price = isset($item['price']) ? (float)$item['price'] : 0;
        $unitAmount = (int)round($price * 100);

        if ($unitAmount <= 0) {
            continue;
        }

        $subtotalCents += ($unitAmount * $qty);

        $postFields["line_items[$lineIndex][price_data][currency]"] = 'myr';
        $postFields["line_items[$lineIndex][price_data][product_data][name]"] = $title;
        $postFields["line_items[$lineIndex][price_data][unit_amount]"] = $unitAmount;
        $postFields["line_items[$lineIndex][quantity]"] = $qty;

        if (!empty($item['img']) && preg_match('/^https?:\/\//i', $item['img'])) {
            $postFields["line_items[$lineIndex][price_data][product_data][images][0]"] = $item['img'];
        }

        $lineIndex++;
    }

    if ($lineIndex === 0) {
        ob_end_clean();
        http_response_code(400);
        echo json_encode(array('error' => 'Cart total must be greater than RM 0.00 to use Stripe.'));
        exit;
    }

    // Add 6% Estimated Tax
    $taxCents = (int)round($subtotalCents * 0.06);
    if ($taxCents > 0) {
        $postFields["line_items[$lineIndex][price_data][currency]"] = 'myr';
        $postFields["line_items[$lineIndex][price_data][product_data][name]"] = 'Estimated Tax (6%)';
        $postFields["line_items[$lineIndex][price_data][unit_amount]"] = $taxCents;
        $postFields["line_items[$lineIndex][quantity]"] = 1;
    }

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $stripeSecretKey . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $responseBody = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($responseBody === false) {
        throw new Exception('cURL error connecting to Stripe: ' . $curlErr);
    }

    $stripeData = json_decode($responseBody, true);

    if ($httpCode >= 400 || empty($stripeData['url'])) {
        $msg = isset($stripeData['error']['message']) ? $stripeData['error']['message'] : 'Stripe API returned HTTP ' . $httpCode;
        throw new Exception($msg);
    }

    ob_end_clean();
    echo json_encode(array('url' => $stripeData['url']));
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}
?>