<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$cookie = tempnam("/tmp", time());

get_cookie($cookie);

$restart_flag = false;

for ($j = 0; $j < 3; $j++) {
  for ($i = 0; $i < 100; $i++) {
    $url = getenv('URL_020') . ($i + 1);
    $file_name = '/tmp/' . hash('sha512', $url);
    if (file_exists($file_name)) {
      continue;
    }
    error_log($url);

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
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'DNT: 1',
        'Upgrade-Insecure-Requests: 1',
        ],
      CURLOPT_COOKIEFILE => $cookie,
      CURLOPT_PATH_AS_IS => true,
      CURLOPT_TCP_FASTOPEN => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
    ];

    foreach ($options as $key => $value) {
      $rc = curl_setopt($ch, $key, $value);
    }

    $res = curl_exec($ch);

    curl_close($ch);

    error_log('size : ' . strlen($res));

    if (strlen($res) > 50000) {
      file_put_contents($file_name, $res);
      $restart_flag = true;
    } else {
      sleep(1);
      get_cookie($cookie);
    }
  }
}

error_log(file_get_contents($cookie));

unlink($cookie);

if ($restart_flag) {
  $ch = curl_init();
  $url = $url = 'https://' . getenv('TARGET_APP_NAME') . '.herokuapp.com/rss01.php';
  $rc = curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_TIMEOUT => 2]);
  $res = curl_exec($ch);
  curl_close($ch);
}

$time_finish = time();
error_log("FINISH " . ($time_finish - $time_start) . 's');

function get_cookie($cookie_) {
  $url = 'https://' . parse_url(getenv('URL_020'))['host'] . '/' . '?' . time();
  error_log($url);

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
      'Cache-Control: no-cache',
      'Connection: keep-alive',
      'DNT: 1',
      'Upgrade-Insecure-Requests: 1',
      ],
    CURLOPT_COOKIEJAR => $cookie_,
    CURLOPT_COOKIEFILE => $cookie_,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
  ];

  foreach ($options as $key => $value) {
    $rc = curl_setopt($ch, $key, $value);
  }

  $res = curl_exec($ch);

  curl_close($ch);

  error_log(strlen($res));
  error_log(file_get_contents($cookie_));
}
