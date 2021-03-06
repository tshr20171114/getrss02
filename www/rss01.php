<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

error_log(getenv('USER_AGENT'));

$cookie = tempnam("/tmp", time());

$ch = curl_init();

$options = [
  CURLOPT_URL => 'https://' . parse_url(getenv('URL_010'))['host'],
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
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
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

for ($j = 0; $j < 4; $j++) {
  $mh = curl_multi_init();
  $list_ch = [];
  for ($i = 0; $i < 3; $i++) {
    if ($j % 2 === 0) {
      $url = getenv('URL_010') . ($i + 1);
    } else {
      $url = getenv('URL_011') . ($i + 1);
    }
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
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
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
      if (strlen($tmp) > 100000) {
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

for ($i = 0; $i < 170; $i++) {
  $url = getenv('URL_010') . ($i + 1);
  if (array_key_exists($url, $list_res)) {
    continue;
  }
  $file_name = '/tmp/' . hash('sha512', $url);
  if (file_exists($file_name)) {
    $list_res[$url] = file_get_contents($file_name);
  }
}

for ($i = 0; $i < 20; $i++) {
  $url = getenv('URL_011') . ($i + 1);
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
  $tmp1 = explode('<div class="innerHeaderSubMenu langTextSubMenu">', $res, 2);
  $tmp1 = explode('<div class="pagination3">', $tmp1[1]);

  $list = explode('</li>', $tmp1[0]);

  foreach ($list as $item) {
    $rc = preg_match('/data-thumb_url = "(.+?)"/s', $item, $match);
    if ($rc === 0) {
      continue;
    }
    $thumbnail = $match[1];

    $rc = preg_match('/<var class="duration">(.+?):/s', $item, $match);
    $time = (int)$match[1];
    if ($time < 50) {
      continue;
    }

    $rc = preg_match('/<a href="(.+?)".+?title="(.+?)"/s', $item, $match);
    $url_parts = parse_url($url);
    $link = $url_parts['scheme'] . '://' . $url_parts['host'] . $match[1];
    $title = $match[2];
    // $guid = hash('sha512', $link);
    $guid = explode('=', $link)[1];
    $items[] = "<item><title>${time}min ${title}</title><link>${link}</link><description>&lt;img src='${thumbnail}'&gt;</description><pubDate/><guid isPermaLink='false'>${guid}</guid></item>";
  }
}

$items = array_unique($items);

$xml_root_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss01</title>
    <link>http://www.yahoo.co.jp</link>
    <description>none</description>
    <language>ja</language>
    __ITEMS__
  </channel>
</rss>
__HEREDOC__;

file_put_contents('/tmp/' . getenv('RSS_010_FILE'), str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text));
$rc = filesize('/tmp/' . getenv('RSS_010_FILE'));
error_log('file size : ' . $rc);

if (count($items) > 0) {
  $ftp_link_id = ftp_connect(getenv('FC2_FTP_SERVER'));
  $rc = ftp_login($ftp_link_id, getenv('FC2_FTP_ID'), getenv('FC2_FTP_PASSWORD'));
  error_log('ftp_login : ' . $rc);

  $rc = ftp_pasv($ftp_link_id, true);
  error_log('ftp_pasv : ' . $rc);

  $rc = ftp_nlist($ftp_link_id, '.');
  error_log(print_r($rc, true));

  $rc = ftp_put($ftp_link_id, getenv('RSS_010_FILE'), '/tmp/' . getenv('RSS_010_FILE'), FTP_ASCII);
  error_log('ftp_put : ' . $rc);

  $rc = ftp_close($ftp_link_id);
  error_log('ftp_close : ' . $rc);
  
  $url = 'inoreader.superfeedr.com';
  $post_data = ['hub.mode' => 'publish',
                'hub.url' => 'https://' . getenv('FC2_FTP_SERVER') . '/'. getenv('RSS_010_FILE')
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
  error_log($res);
}

$api_key = getenv('HEROKU_API_KEY');
$url = 'https://api.heroku.com/account';

$options = [
  CURLOPT_URL => $url,
  CURLOPT_USERAGENT => getenv('USER_AGENT'),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_FOLLOWLOCATION => 1,
  CURLOPT_MAXREDIRS => 3,
  CURLOPT_HTTPHEADER => [
      'Accept: application/vnd.heroku+json; version=3',
      "Authorization: Bearer ${api_key}",
      ]
];

$ch = curl_init();
$rc = curl_setopt_array($ch, $options);
error_log('curl_setopt_array : ' . $rc);
$res = curl_exec($ch);
curl_close($ch);

error_log($url . ' : ' . $res);

$data = json_decode($res, true);
$account = explode('@', $data['email'])[0];

$url = "https://api.heroku.com/accounts/${data['id']}/actions/get-quota";

$options = [
  CURLOPT_URL => $url,
  CURLOPT_USERAGENT => getenv('USER_AGENT'),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_FOLLOWLOCATION => 1,
  CURLOPT_MAXREDIRS => 3,
  CURLOPT_HTTPHEADER => [
      'Accept: application/vnd.heroku+json; version=3.account-quotas',
      "Authorization: Bearer ${api_key}",
      ]
];

$ch = curl_init();
$rc = curl_setopt_array($ch, $options);
$res = curl_exec($ch);
curl_close($ch);

error_log($url . ' : ' . $res);

$data = json_decode($res, true);

$dyno_used = (int)$data['quota_used'];
$dyno_quota = (int)$data['account_quota'];

$tmp = $dyno_quota - $dyno_used;
$tmp = floor($tmp / 86400) . 'd ' . ($tmp / 3600 % 24) . 'h ' . ($tmp / 60 % 60) . 'm';

header('Content-Type: application/xml');

$xml_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>quota</title>
    <link>https://www.heroku.com/</link>
    <description>none</description>
    <language>ja</language>
    <item><title>__TITLE__</title><link>https://www.heroku.com/</link><description>__DESCRIPTION__</description><pubDate/></item>
  </channel>
</rss>
__HEREDOC__;

$xml_text = str_replace('__TITLE__', $tmp, $xml_text);
$xml_text = str_replace('__DESCRIPTION__', 'count : ' . count($items) . date(' Y/m/d H:i', strtotime('+9 hours')), $xml_text);

echo $xml_text;

$ch = curl_init();
$url = $url = 'https://' . getenv('TARGET_APP_NAME') . '.herokuapp.com/getcache01.php';
$rc = curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_TIMEOUT => 2]);
$res = curl_exec($ch);
curl_close($ch);

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');

