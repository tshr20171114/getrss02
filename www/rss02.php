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
  for ($i = 0; $i < 10; $i++) {
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

for ($i = 0; $i < 100; $i++) {
  $url = getenv('URL_020') . ($i + 1);
  if (array_key_exists($url, $list_res)) {
    continue;
  }
  $file_name = '/tmp/' . hash('sha512', $url);
  if (file_exists($file_name)) {
    $list_res[$url] = file_get_contents($file_name);
  }
}

error_log('count : ' . count($list_res));
$items = [];
foreach ($list_res as $res) {
  $tmp1 = explode('<hr />', $res);
  $list = explode('<div class="itemTitle">', $tmp1[0]);
  array_shift($list);
  // error_log(print_r($tmp1, true));
  foreach ($list as $item) {
    $rc = preg_match('/.+?<a href="(.+?)" title="(.+?)".+?<img src="(.+?)".+?<span class="movieTime">(\d+).+?<span class="proName".+?>(.+?)<.+?<span class="movieCnt".+?>(.+?)</s', $item, $match);
    if ($rc == 1 && strpos($match[6], '1') > 0) {
      array_shift($match);
      // error_log(print_r($match, true));
      $time = (int)$match[3];
      if ($time < 40) {
        continue;
      }
      $title = $match[4] . ' ' . $match[1];
      $link = getenv('URL_021'). $match[0];
      $thumbnail = 'https:' . $match[2];
      $items[] = "<item><title>${time}min ${title}</title><link>${link}</link><description>&lt;img src='${thumbnail}'&gt;</description><pubDate/></item>";
    }
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

$tmp = str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text);
$tmp = str_replace('&hellip;', '', $tmp);
$tmp = str_replace('&laquo;', '', $tmp);
$tmp = str_replace('&raquo;', '', $tmp);
$tmp = str_replace('&hearts;', '', $tmp);
file_put_contents('/tmp/' . getenv('RSS_020_FILE'), $tmp);
$rc = filesize('/tmp/' . getenv('RSS_020_FILE'));
error_log('file size : ' . $rc);

if (count($items) > 0) {
  $ftp_link_id = ftp_connect(getenv('FC2_FTP_SERVER'));
  $rc = ftp_login($ftp_link_id, getenv('FC2_FTP_ID'), getenv('FC2_FTP_PASSWORD'));
  error_log('ftp_login : ' . $rc);

  $rc = ftp_pasv($ftp_link_id, true);
  error_log('ftp_pasv : ' . $rc);

  $rc = ftp_nlist($ftp_link_id, '.');
  error_log(print_r($rc, true));

  $rc = ftp_put($ftp_link_id, getenv('RSS_020_FILE'), '/tmp/' . getenv('RSS_020_FILE'), FTP_ASCII);
  error_log('ftp_put : ' . $rc);

  $rc = ftp_close($ftp_link_id);
  error_log('ftp_close : ' . $rc);
  
  $url = 'https://pubsubhubbub.appspot.com/';
  $post_data = ['hub.mode' => 'publish',
                'hub.url' => 'https://' . getenv('FC2_FTP_SERVER') . '/'. getenv('RSS_020_FILE')
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
    <title>rss02trigger</title>
    <link>https://www.yahoo.com/</link>
    <description>none</description>
    <language>ja</language>
    <item><title>rss02trigger</title><link>https://www.yahoo.com/</link><description>__DESCRIPTION__</description><pubDate/></item>
  </channel>
</rss>
__HEREDOC__;

$xml_text = str_replace('__DESCRIPTION__', 'count : ' . count($items) . date(' Y/m/d H:i', strtotime('+9 hours')), $xml_text);

echo $xml_text;

$ch = curl_init();
$url = $url = 'https://' . getenv('TARGET_APP_NAME') . '.herokuapp.com/getcache02.php';
$rc = curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_TIMEOUT => 2]);
$res = curl_exec($ch);
curl_close($ch);

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');

