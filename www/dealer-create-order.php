<?php

//set POST variables
$url = 'http://new.mt.dev/insert/order';
$clientId = 'zlabn0wrsh9k562pkz8ku5i95iaz0f980jiyxyxqmbakweeuq2';
$currency = 'czk';
$shippingId = '1'; 
$paymentId = '1'; 
// STOCK_ID => QUANTITY
$stocks = [
	'682' => '1',
	'50' => '2',
];

$data = [
	'client_id' => urlencode($clientId),
	'currency' => urlencode($currency),
	'shipping' => urlencode($shippingId),
	'payment' => urlencode($paymentId),
];
foreach ($stocks as $stockId => $quantity) {
	$data['stocks[' . $stockId . ']'] = urlencode($quantity);
}

//url-ify the data for the POST
$dataString = NULL;
foreach ($data as $key => $value) {
	$dataString .= $key . '=' . $value . '&';
}
rtrim($dataString, '&');

//open connection
$connection = curl_init();

//set the url, number of POST vars, POST data
curl_setopt($connection, CURLOPT_URL, $url);
curl_setopt($connection, CURLOPT_POST, count($data));
curl_setopt($connection, CURLOPT_POSTFIELDS, $dataString);

//execute post
$result = curl_exec($connection);

//close connection
curl_close($connection);
