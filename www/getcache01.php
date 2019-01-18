<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

for ($i = 0; $i < 40; $i++) {
  $url = getenv('URL_010') . ($i + 1);
  $file_name = '/tmp/' . hash('sha512', $url);
  if (file_exists($file_name)) {
    continue;
  }
  
  $ch = curl_init();
  
  $options = [
          CURLOPT_URL => $url,
          CURLOPT_USERAGENT => getenv('USER_AGENT'),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => 'gzip, deflate, br',
          CURLOPT_FOLLOWLOCATION => 1,
          CURLOPT_MAXREDIRS => 3,
          CURLOPT_HTTPHEADER => [
              'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
              'Accept-Language: ja,en-US;q=0.7,en;q=0.3',
              ]
  ];
  
  foreach ($options as $key => $value) {
    $rc = curl_setopt($ch, $key, $value);
  }
  
  $res = curl_exec($ch);
  
  curl_close($ch);
  
  error_log('size : ' . strlen($res));
  
  if (strlen($res) > 100000) {
    file_put_contents($file_name, $res);
  }
}

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');
