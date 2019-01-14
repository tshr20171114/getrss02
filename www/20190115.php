<?php

$mh = curl_multi_init();

$list_ch = [];
for ($i = 0; $i < 2; $i++) {
  $url = getenv('TEST_URL_900') . ($i + 1);
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
    $tmp = curl_multi_getcontent($ch);
    error_log(strlen($tmp));
    $list_res[$url] = $tmp;
  }
  curl_multi_remove_handle($mh, $ch);
  curl_close($ch);
}
curl_multi_close($mh);

