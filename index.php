<?php
require 'database.php';
require 'MercadoLivre/meli.php';

$meli = new Meli($APP_ID, $APP_SECRET);

if($_GET['code']) {

	$auth = $meli->authorize($_GET['code'], $REDIRECT_URI);

	$userId = $auth['body']->user_id;
	$accessToken = $auth['body']->access_token;
	$refreshToken = $auth['body']->refresh_token;
	$expiresIn = time() + $auth['body']->expires_in;

	$rows = $db->query("SELECT id FROM auth WHERE user_id = ".$auth['body']->user_id);

	if($rows->rowCount() == 0) {
		$db->query("INSERT INTO auth (user_id, access_token, refresh_token, expires_in) VALUES ($userId, '$accessToken',  '$refreshToken', '$expiresIn')");
	} else {
		$db->query("UPDATE auth SET access_token = '$accessToken', refresh_token = '$refreshToken', expiresIn = $expiresIn WHERE user_id = $userId");
	}
	
	echo 'Autenticado :)';
	
} else {
	echo '<a href="' . $meli->getAuthUrl($REDIRECT_URI) . '">Login using MercadoLibre oAuth 2.0</a>';
}
