<?php
/* ----------  MINI-SAAS BRIDGE  ---------- */
$shopB = $_ENV['SHOP_B_DOMAIN'] ?? 'gdgf3z-qw.myshopify.com';
$endpoint = "https://{$shopB}/cart/add?return_to=/checkout";

/* récupère le corps POST brut */
$post = file_get_contents('php://input');
if (empty($post)) { http_response_code(400); exit('No body'); }

/* envoi vers Shopify B (zero headers leak) */
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $post,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/x-www-form-urlencoded',
        'Content-Length: ' . strlen($post),
        'User-Agent: Shopify-Bridge/1.0'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER         => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT        => 15
]);
$response = curl_exec($ch);
$info     = curl_getinfo($ch);
curl_close($ch);

/* renvoie la 302 */
http_response_code($info['http_code']);
$headers = explode("\r\n", $response);
foreach ($headers as $h) if (stripos($h, 'Location:') === 0) header($h);
exit;
?>
