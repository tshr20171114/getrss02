<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

$mh = curl_multi_init();

error_log(getenv('USER_AGENT'));

for ($i = 0; $i < 30; $i++) {
  $url = getenv('URL_010') . ($i + 1);
  error_log($url);

  $ch = curl_init();
  
  $options = [
          CURLOPT_URL => $url,
          CURLOPT_USERAGENT => getenv('USER_AGENT'),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_FOLLOWLOCATION => 1,
          CURLOPT_MAXREDIRS => 3,
          CURLOPT_SSL_FALSESTART => true,
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
    $list_res[] = curl_multi_getcontent($ch);
  }
}

foreach ($list_res as $res) {
  error_log(strlen($res));
  $tmp1 = explode('<div class="innerHeaderSubMenu langTextSubMenu">', $res, 2);
  $tmp1 = explode('<div class="pagination3">', $tmp1[1]);

  // error_log($tmp1[0]);

  $list = explode('</li>', $tmp1[0]);

  // error_log(print_r($list, true));

  foreach ($list as $item) {
    // error_log($item);
    $rc = preg_match('/data-thumb_url = "(.+?)"/s', $item, $match);
    if ($rc === 0) {
      continue;
    }
    // error_log(print_r($match, true));
    $thumbnail = $match[1];

    $rc = preg_match('/<var class="duration">(.+?):/s', $item, $match);
    // error_log(print_r($match, true));
    $time = (int)$match[1];
    if ($time < 50) {
      continue;
    }

    $rc = preg_match('/<a href="(.+?)".+?title="(.+?)"/s', $item, $match);
    // error_log(print_r($match, true));
    $url_parts = parse_url($url);
    $link = $url_parts['scheme'] . '://' . $url_parts['host'] . $match[1];
    $title = $match[2];
    $items[] = "<item><title>${time}min ${title}</title><link>${link}</link><description>&lt;img src='${thumbnail}'&gt;</description><pubDate/></item>";
  }
}

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

$ftp_link_id = ftp_connect(getenv('FC2_FTP_SERVER'));
$rc = ftp_login($ftp_link_id, getenv('FC2_FTP_ID'), getenv('FC2_FTP_PASSWORD'));

error_log($rc);

$rc = ftp_pasv($ftp_link_id, true);

error_log($rc);

$rc = ftp_nlist($ftp_link_id, '.');

error_log(print_r($rc, true));

$rc = ftp_put($ftp_link_id, getenv('RSS_010_FILE'), '/tmp/' . getenv('RSS_010_FILE'), FTP_ASCII);

error_log($rc);

ftp_close($ftp_link_id);

header('Content-Type: application/xml');

echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"/>';

$time_finish = time();
error_log("${pid} FINISH " . date('s', $time_finish - $time_start) . 's');

