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

$ch = curl_init();

$url = getenv('URL_110');

$options = [
  CURLOPT_URL => getenv('URL_110'),
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

unlink($cookie);

$items = [];

$tmp1 = explode('<div class="innerHeaderSubMenu langTextSubMenu">', $res, 2);
$tmp1 = explode('<div class="pagination3">', $tmp1[1]);

$list = explode('</li>', $tmp1[0]);

error_log(count($list));

foreach ($list as $item) {
  $rc = preg_match('/data-thumb_url = "(.+?)"/s', $item, $match);
  if ($rc === 0) {
    continue;
  }
  $thumbnail = $match[1];

  $rc = preg_match('/<var class="duration">(.+?):/s', $item, $match);
  $time = (int)$match[1];
  if ($time < 10) {
    continue;
  }

  $rc = preg_match('/<a href="(.+?)".+?title="(.+?)"/s', $item, $match);
  $url_parts = parse_url($url);
  $link = $url_parts['scheme'] . '://' . $url_parts['host'] . $match[1];
  $title = $match[2];
  $guid = hash('sha512', $link);
  $items[] = "<item><title>${time}min ${title}</title><link>${link}</link><description>&lt;img src='${thumbnail}'&gt;</description><pubDate/><guid isPermaLink='false'>${guid}</guid></item>";
}

$xml_root_text = <<< __HEREDOC__
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>rss04</title>
    <link>http://www.yahoo.co.jp/rss04</link>
    <description>none</description>
    <language>ja</language>
    __ITEMS__
  </channel>
</rss>
__HEREDOC__;

header('Content-Type: application/xml');
echo str_replace('__ITEMS__', implode("\r\n", $items), $xml_root_text);

