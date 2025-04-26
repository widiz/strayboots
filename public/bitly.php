<?php
$long_url = urlencode($url);
$apiv4 = 'https://api-ssl.bitly.com/v4/bitlinks';
$genericAccessToken = '05a2f639b504e870e43f8db100097b091ba04f0d';

$data = array(
    'long_url' => $long_url
);
$payload = json_encode($data);

$header = array(
    'Authorization: Bearer ' . $genericAccessToken,
    'Content-Type: application/json',
    'Content-Length: ' . strlen($payload)
);


$ch = curl_init($apiv4);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
$result = curl_exec($ch);


$resultToJson = json_decode($result);

if (isset($resultToJson->link)) {
    echo $resultToJson->link;
}
else {
    echo 'Not found';
}






$get = 'https://api-ssl.bitly.com/v3/shorten?login=' . $this->config->bitly->login . '&apiKey=' . $this->config->bitly->APIKey . '&longUrl=' . urlencode($url);
        if (is_object($response = json_decode(file_get_contents($get))))
            return $response->status_code == 200 ? $response->data->url : $url;
        return $url;


        ?>