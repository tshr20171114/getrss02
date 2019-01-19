<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$cookie = $tmpfname = tempnam("/tmp", time());

$ch = curl_init();

$options = [
  CURLOPT_URL => 'http://' . parse_url(getenv('URL_030'))['host'],
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

error_log(strlen($res));
error_log(file_get_contents($cookie));

$list_res = [];

for ($j = 0; $j < 2; $j++) {
  $mh = curl_multi_init();
  $list_ch = [];
  for ($i = 0; $i < 5; $i++) {
    $url = getenv('URL_030') . ($i + 1);
    if (array_key_exists($url, $list_res)) {
      continue;
    }
    error_log($url);

    $ch = curl_init();

    $options = [
      CURLOPT_URL => $url,
      CURLOPT_USERAGENT => getenv('USER_AGENT'),
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
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
    ];
    curl_setopt_array($ch, $options);

    curl_multi_add_handle($mh, $ch);
    $list_ch[$url] = $ch;
  }

  $active = null;
  $rc = curl_multi_exec($mh, $active);

  while ($active && $rc == CURLM_OK) {
    if (curl_multi_select($mh) == -1) {
      usleep(1);
    }
    $rc = curl_multi_exec($mh, $active);
  }

  foreach ($list_ch as $url => $ch) {
    $res = curl_getinfo($ch);
    error_log($res['http_code'] . " ${url}");
    if ($res['http_code'] == '200') {
      $tmp = curl_multi_getcontent($ch);
      error_log('size : ' . strlen($tmp));
      if (strlen($tmp) > 50000) {
        $list_res[$url] = $tmp;
      }
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
  }
  curl_multi_close($mh);
}

unlink($cookie);

error_log('count : ' . count($list_res));
$items = [];
foreach ($list_res as $res) {
  $list = explode('<div class="list" id="', $res);
  array_shift($list);

  foreach ($list as $item) {
    $rc = preg_match('/<h3><a href="(.+?)".+?>(.+?)<.+?<dl>.+?<dd>(\d+)\w</s', $item, $match);
    if ($rc == 1) {
      array_shift($match);
      error_log(print_r($match, true));
      $page = (int)$match[2];
      $url = 'http://' . parse_url(getenv('URL_030'))['host'] . $match[0];
      $file_name = '/tmp/' . hash('sha512', $url);
      if ($page < 50 || file_exists($file_name)) {
        continue;
      }
      
      error_log($url);
      $ch = curl_init();

      $options = [
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => getenv('USER_AGENT'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
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
      ];
      curl_setopt_array($ch, $options);
      $res = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      error_log($http_code);
      if ($http_code == '200') {
        file_put_contents($file_name);
      }
    }
  }
}

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');

