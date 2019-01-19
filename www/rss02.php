<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

error_log(getenv('USER_AGENT'));

$cookie = $tmpfname = tempnam("/tmp", time());

$ch = curl_init();

$options = [
        CURLOPT_URL => 'https://' . parse_url(getenv('URL_020'))['host'],
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
  for ($i = 0; $i < 1; $i++) {
    $url = getenv('URL_020') . ($i + 1);
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
      $file_name = '/tmp/' . hash('sha512', $url);
      $tmp = curl_multi_getcontent($ch);
      error_log('size : ' . strlen($tmp));
      if (strlen($tmp) > 50000) {
        $list_res[$url] = $tmp;
        file_put_contents($file_name, $tmp);
      } elseif ($j === 1 && file_exists($file_name)){
        $list_res[$url] = file_get_contents($file_name);
      } else {
        // error_log($tmp);
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
  $tmp1 = explode('<hr />', $res);
  $list = explode('<div class="itemTitle">', $tmp1[0]);
  array_shift($list);
  error_log(print_r($tmp1, true));
  foreach ($list as $item) {
    $rc = preg_match('/(.+?)<\/div>.+?<img src="(.+?)".+?<span class="movieTime">(.+?)</s', $item, $match);
    error_log(print_r($match, true));
  }
}

$items = array_unique($items);

$xml_root_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss02</title>
    <link>http://www.yahoo.co.jp</link>
    <description>none</description>
    <language>ja</language>
    __ITEMS__
  </channel>
</rss>
__HEREDOC__;

/*
file_put_contents('/tmp/' . getenv('RSS_020_FILE'), str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text));
$rc = filesize('/tmp/' . getenv('RSS_020_FILE'));
error_log('file size : ' . $rc);
*/

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');

