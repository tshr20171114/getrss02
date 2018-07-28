<?php

$jump_url = $_GET['u'];

// $res = file_get_contents($jump_url);
$res = get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

// $res = file_get_contents($jump_url2);
$res = get_contents($jump_url2);

for ($i = 0; $i < 10; $i++) {
  if (getenv('PATTERN_B' . $i) !== FALSE) {
    $patterns_b[] = getenv('PATTERN_B' . $i);
  }
}

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
INSERT INTO t_link ( uri ) VALUES ( :b_uri )
__HEREDOC__;
$statement = $pdo->prepare($sql);

foreach ($patterns_b as $pattern) {
  $ar = explode(',', $pattern);
  $rc = preg_match($ar[0], $res, $matches);
  if ($rc === 1) {
    $base_url = $matches[1];
    error_log('TEMP1 : ' . $base_url);
    $embed_url = preg_replace($ar[1], $ar[2], $base_url);
    error_log('TEMP2 : ' . $embed_url);
    
    echo '<HTML><HEAD>';
    echo '<TITLE>' . $title . '</TITLE>';
    echo '</HEAD><BODY>';
    echo '<iframe src="';
    echo $embed_url;
    echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
    echo '</BODY></HTML>';
   
    $statement->execute([':b_uri' => $embed_url]);
    $pdo = null;
    exit();
  }
}

$pattern = explode(',', getenv('LINK_PATTERN1'));
$rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
if ($rc == 1) {
  $jump_url3 = str_replace($pattern[1], $matches[1], $pattern[2]);
  error_log($jump_url3);
  $statement->execute([':b_uri' => $jump_url3]);
  $pdo = null;
  header('Location: ' . $jump_url3);
  exit();
}

$pdo = null;

header('Location: ' . $jump_url2);

exit();

function get_contents($url_) {
  // $pid = getmypid();
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
  // curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
  $contents = curl_exec($ch);
  // error_log(curl_getinfo($ch, CURLINFO_HEADER_OUT));
  // $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  // $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  
  curl_close($ch);
  
  return $contents;
}
?>
