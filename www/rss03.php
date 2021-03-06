<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

error_log(getenv('USER_AGENT'));

$cookie = tempnam("/tmp", time());

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
  // error_log(print_r($list, true));

  foreach ($list as $item) {
    $rc = preg_match('/ <img src="(.+?)".+?<h3><a href="(.+?)".+?>(.+?)<.+?<dl>.+?<dd>(\d+)\w</s', $item, $match);
    if ($rc == 1) {
      array_shift($match);
      error_log(print_r($match, true));
      $page = (int)$match[3];
      if ($page < 50) {
        continue;
      }

      $title = htmlspecialchars($match[2]);
      $link = $url = 'http://' . parse_url(getenv('URL_030'))['host'] . $match[1];
      $link2 = str_replace('/detail/', '/detail/download_zip/', $link);
      $thumbnail = $match[0];
      if (strpos($thumbnail, 'noimage') > 0) {
        continue;
      }
      $items[] = "<item><title>${title}</title><link>${link}</link><description>&lt;img src='${thumbnail}'&gt;${page}&lt;a href='${link2}'&gt;_zip&lt;/a&gt;</description><pubDate/></item>";
    }
  }
}

$items = array_unique($items);

$xml_root_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss03</title>
    <link>http://www.yahoo.co.jp</link>
    <description>none</description>
    <language>ja</language>
    __ITEMS__
  </channel>
</rss>
__HEREDOC__;

$tmp = str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text);
file_put_contents('/tmp/' . getenv('RSS_030_FILE'), $tmp);
$rc = filesize('/tmp/' . getenv('RSS_030_FILE'));
error_log('file size : ' . $rc);

if (count($items) > 0) {
  $ftp_link_id = ftp_connect(getenv('FC2_FTP_SERVER'));
  if ($ftp_link_id == false) {
    $ftp_link_id = ftp_connect(getenv('FC2_FTP_SERVER_ACTIVE'));
  }
  $rc = ftp_login($ftp_link_id, getenv('FC2_FTP_ID'), getenv('FC2_FTP_PASSWORD'));
  error_log('ftp_login : ' . $rc);

  $rc = ftp_pasv($ftp_link_id, true);
  error_log('ftp_pasv : ' . $rc);

  $rc = ftp_nlist($ftp_link_id, '.');
  error_log(print_r($rc, true));

  $rc = ftp_put($ftp_link_id, getenv('RSS_030_FILE'), '/tmp/' . getenv('RSS_030_FILE'), FTP_ASCII);
  error_log('ftp_put : ' . $rc);

  $rc = ftp_close($ftp_link_id);
  error_log('ftp_close : ' . $rc);
  
  $url = 'https://inoreader.superfeedr.com/';
  $post_data = ['hub.mode' => 'publish',
                'hub.url' => 'https://' . getenv('FC2_FTP_SERVER') . '/'. getenv('RSS_030_FILE')
               ];
  $options = [
    CURLOPT_URL => $url,
    CURLOPT_USERAGENT => getenv('USER_AGENT'),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post_data),
  ];
  $ch = curl_init();
  $rc = curl_setopt_array($ch, $options);
  error_log('curl_setopt_array : ' . $rc);
  $res = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  error_log($http_code);
}

header('Content-Type: application/xml');

$xml_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss03trigger</title>
    <link>https://www.yahoo.com/</link>
    <description>none</description>
    <language>ja</language>
    <item><title>rss03trigger</title><link>https://www.yahoo.com/</link><description>__DESCRIPTION__</description><pubDate/></item>
  </channel>
</rss>
__HEREDOC__;

$xml_text = str_replace('__DESCRIPTION__', 'count : ' . count($items) . date(' Y/m/d H:i', strtotime('+9 hours')), $xml_text);

echo $xml_text;

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');

