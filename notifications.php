<?php
include 'database.php';
require 'MercadoLivre/meli.php';

$notify = json_decode(file_get_contents('php://input'));

if($notify->topic != 'orders') {
    exit('Bad Request');
}

$user = $db->query("SELECT access_token, refresh_token, expires_in FROM auth WHERE user_id = ".$notify->user_id);

if($user->rowCount() > 0) {
    $user = $user->fetchObject();

    $meli = new Meli($APP_ID, $APP_SECRET, $user->access_token, $user->refresh_token);

    $accessToken = $user->access_token;

    // refresh token
    if($user->expires_in > time()) {
        $auth = $meli->refreshAccessToken();

        $userId = $auth['body']->user_id;
        $accessToken = $auth['body']->access_token;
        $refreshToken = $auth['body']->refresh_token;
        $expiresIn = time() + $auth['body']->expires_in; 
        $db->query("UPDATE auth SET access_token = '$accessToken', refresh_token = '$refreshToken', expires_in = $expiresIn WHERE user_id = $userId");
    }

    // process order
    $order = $meli->get($notify->resource, array('access_token' => $accessToken));

    $meli_order_id = $order['body']->id;
    $product = $order['body']->order_items[0]->item->title;
    $status = $order['body']->status;
    $customer = 'email:' . $order['body']->buyer->email . '/ tel :' . $order['body']->buyer->phone->area_code . $order['body']->buyer->phone->number;

    $rows = $db->query("SELECT * FROM my_orders WHERE meli_order_id = $meli_order_id");

    if($rows->rowCount() == 0) {
        $db->query("INSERT INTO my_orders (meli_order_id, product, status, customer) VALUES ($meli_order_id, '$product',  '$status', '$customer')");
    } else {
        $db->query("UPDATE my_orders SET status = '$status' WHERE meli_order_id = $meli_order_id");
    }

} else {
    header("HTTP/1.1 500 Internal Server Error");
    exit('Erro ao processar pedido');
}

?>