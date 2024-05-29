<?php

include('config.php');
include('simple_html_dom.php');

$notFound = 0;

function sendTelegramMessage($chat_id, $message, $bot_token) {
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $message
    ];

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        echo "CURL Error: $error";
        return false;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);
    if (isset($responseData['ok']) && $responseData['ok']) {
        return true;
    } else {
        print_r($responseData);
        return false;
    }
}

// $products = ['هايلاند بيريز'];


function checkForNewProducts() {
    global $apiToken, $chatId, $products, $notFound;
    try {
        $url = "https://www.dzrt.com/ar/our-products.html";
        
        $html = file_get_html($url);
        
        $newProducts = [];
        
        foreach($html->find('li.item.product.product-item') as $product) {
            if (strpos($product->class, 'unavailable') === false) {
                $productName = $product->find('strong.product.name.product-item-name a', 0)->plaintext;
                $productLink = $product->find('a.product-item-link', 0)->href;

                // $normalizedProductNames = array_map('trim', $products);

                // if (in_array(trim($productName), $normalizedProductNames)) {
                    $newProducts[] = [
                        'name' => $productName,
                        'link' => $productLink
                    ];
                // }
                $notFound = 1;
            }
        }
        
        foreach($newProducts as $newProduct) {
            $message = "منتج جديد متاح الآن: " . $newProduct['name'] . "\n" . $newProduct['link'];
            sendTelegramMessage($chatId, $message, $apiToken);
            echo $message . "<br>";
        } 

        
    } catch (\Throwable $th) {
        throw $th;
    }
}

function notFound() {
    global $apiToken, $chatId;
    sendTelegramMessage($chatId, 'لا يوجد منتجات متاحة الان', $apiToken);
}

$count = 0;

while(true){
    $count = $count + 20;
    checkForNewProducts();

    if ($notFound == 1 && $count == 60000) {
        notFound();
        $count = 0;
    }
    sleep(20);
}