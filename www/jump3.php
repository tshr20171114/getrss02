<?php

$jump_url = $_GET['u'];

$res = get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = get_contents($jump_url2);

for ($i = 0; $i < 2; $i++) {
  if ($i == 0) {
     $pattern = explode(',', getenv('LINK_PATTERN1'));
  } else {
     $pattern = explode(',', getenv('LINK_PATTERN2'));
  }
  $rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
  if ($rc != 1) {
    continue;
  }
  $replace = end($matches);
  error_log($replace);
  $embed_url = str_replace($pattern[1], $replace, $pattern[2]);
  error_log($embed_url);
 
  echo '<HTML><HEAD>';
  echo '<TITLE>' . $title . '</TITLE>';
  echo '</HEAD><BODY>';
  echo '<iframe src="';
  echo $embed_url;
  echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
  echo '</BODY></HTML>';
  exit;
}

header('Location: ' . $jump_url2);

exit();

function get_contents($url_) {
  $ch = curl_init();
  curl_setopt_array($ch,
                    [CURLOPT_URL => $url_,
                     CURLOPT_RETURNTRANSFER => TRUE,
                     CURLOPT_ENCODING => '',
                     CURLOPT_CONNECTTIMEOUT => 20,
                     CURLOPT_FOLLOWLOCATION => TRUE,
                     CURLOPT_MAXREDIRS => 3,
                     CURLOPT_FILETIME => TRUE,
                     // CURLOPT_TCP_FASTOPEN => TRUE,
                     CURLOPT_SSL_FALSESTART => TRUE,
                     CURLOPT_PATH_AS_IS => TRUE,
                     CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; rv:56.0) Gecko/20100101 Firefox/61.0',
                    ]);
  $contents = curl_exec($ch);
  
  curl_close($ch);
  
  return $contents;
}
?>
