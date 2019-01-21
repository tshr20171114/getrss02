<?php

$ch = curl_init();
$options = [
        CURLOPT_URL => 'https://www.sunday-webry.com/series/1352',
        CURLOPT_USERAGENT => getenv('USER_AGENT'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: ja,en-US;q=0.7,en;q=0.3',
            'Cache-Control: no-cache',
            'Connection: keep-alive',
            'DNT: 1',
            'Upgrade-Insecure-Requests: 1',
            ],
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
];
  
foreach ($options as $key => $value) {
  $rc = curl_setopt($ch, $key, $value);
}
$res = curl_exec($ch);
curl_close($ch);
error_log($res);
