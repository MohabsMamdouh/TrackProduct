<?php


include('config.php');
include('simple_html_dom.php');

$notificationFile = 'sent_notifications.json';

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

function checkForNewProducts() {
    global $apiToken, $chatId, $notificationFile;

    if (file_exists($notificationFile)) {
        $sentNotifications = json_decode(file_get_contents($notificationFile), true);
    } else {
        $sentNotifications = [];
    }

    try {
        $url = "https://www.dzrt.com/ar/our-products.html";
        
        $html = file_get_html($url);
        
        $newProducts = [];
        
        foreach($html->find('li.item.product.product-item') as $product) {
            if (strpos($product->class, 'unavailable') === false) {
                $productName = trim($product->find('strong.product.name.product-item-name a', 0)->plaintext);
                $productLink = $product->find('a.product-item-link', 0)->href;

                // Check if the product has already been notified
                if (!in_array($productName, $sentNotifications)) {
                    $newProducts[] = [
                        'name' => $productName,
                        'link' => $productLink
                    ];

                    // Add to sent notifications
                    $sentNotifications[] = $productName;
                }
            } else {
                echo "Not Found";
            }
        }
        
        // Send messages for new products
        foreach($newProducts as $newProduct) {
            $message = "منتج جديد متاح الآن: " . $newProduct['name'] . "\n" . $newProduct['link'];
            sendTelegramMessage($chatId, $message, $apiToken);
            echo $message . "<br>";
        } 

        // Save sent notifications
        file_put_contents($notificationFile, json_encode($sentNotifications));
        
    } catch (\Throwable $th) {
        throw $th;
    }
}

while(true) {
    checkForNewProducts();
    sleep(20);
}