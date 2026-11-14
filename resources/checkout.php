<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/stripe-php/init.php');
require_once(__DIR__ . '/secrets.php');

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input) || empty($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No cart items provided.']);
    exit;
}

try {
    $stripe = new \Stripe\StripeClient($stripeSecretKey);

    // Automatically detect current protocol + host (e.g. http://localhost or Mercury server)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $returnBase = !empty($input['returnUrl']) ? rtrim($input['returnUrl'], '/') : ($scheme . '://' . $host);

    $lineItems = [];
    $subtotalCents = 0;

    foreach ($input['items'] as $item) {
        $title = !empty($item['title']) ? (string)$item['title'] : 'Nexus Game';
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $unitAmount = (int)round(((float)($item['price'] ?? 0)) * 100); // Convert RM to sen

        if ($unitAmount <= 0) {
            continue;
        }

        $subtotalCents += ($unitAmount * $qty);

        $productData = [
            'name' => $title,
        ];

        // Stripe requires a publicly accessible https:// or http:// URL for images
        if (!empty($item['img']) && preg_match('/^https?:\/\//i', $item['img'])) {
            $productData['images'] = [$item['img']];
        }

        $lineItems[] = [
            'price_data' => [
                'currency' => 'myr',
                'product_data' => $productData,
                'unit_amount' => $unitAmount,
            ],
            'quantity' => $qty,
        ];
    }

    if (empty($lineItems)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart total must be greater than RM 0.00 to use Stripe.']);
        exit;
    }

    // Add 6% Estimated Tax as a line item to match Checkout.vue summary
    $taxCents = (int)round($subtotalCents * 0.06);
    if ($taxCents > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'myr',
                'product_data' => [
                    'name' => 'Estimated Tax (6%)',
                ],
                'unit_amount' => $taxCents,
            ],
            'quantity' => 1,
        ];
    }

    $sessionParams = [
        'line_items' => $lineItems,
        'mode' => 'payment',
        'adaptive_pricing' => [
            'enabled' => true,
        ],
        'success_url' => $returnBase . '/checkout?success=true&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $returnBase . '/checkout?canceled=true',
    ];

    if (!empty($input['email'])) {
        $sessionParams['customer_email'] = $input['email'];
    }

    $checkout_session = $stripe->checkout->sessions->create($sessionParams);

    // Return JSON URL so Vue fetch() can redirect cleanly without CORS/303 issues
    echo json_encode(['url' => $checkout_session->url]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>