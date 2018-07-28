<?php

$jump_url = $_GET['u'];

$res = get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = get_contents($jump_url2);

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
INSERT INTO t_link ( uri ) VALUES ( :b_uri )
__HEREDOC__;
$statement = $pdo->prepare($sql);

/*
$pattern = explode(',', getenv('LINK_PATTERN1'));
$rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
if ($rc === 1) {
  $jump_url3 = str_replace($pattern[1], $matches[1], $pattern[2]);
  error_log($jump_url3);
  $statement->execute([':b_uri' => $jump_url3]);
  $pdo = null;
  header('Location: ' . $jump_url3);
  exit();
}
*/

$pattern = explode(',', getenv('LINK_PATTERN4'));
$rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
if ($rc === 1) {
  $embed_url = str_replace($pattern[1], $matches[3], $pattern[2]);
  error_log($embed_url);
  $statement->execute([':b_uri' => $embed_url]);
  $pdo = null;
  
  echo '<HTML><HEAD>';
  echo '<TITLE>' . $title . '</TITLE>';
  echo '</HEAD><BODY>';
  echo '<iframe src="';
  echo $embed_url;
  echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
  echo '</BODY></HTML>';
 
  exit();
}

$pattern = explode(',', getenv('LINK_PATTERN2'));
$rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
if ($rc === 1) {
  $embed_url = str_replace($pattern[1], $matches[1], $pattern[2]);
  error_log($embed_url);
  $statement->execute([':b_uri' => $embed_url]);
  $pdo = null;
  
  echo '<HTML><HEAD>';
  echo '<TITLE>' . $title . '</TITLE>';
  echo '</HEAD><BODY>';
  echo '<iframe src="';
  echo $embed_url;
  echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
  echo '</BODY></HTML>';
 
  exit();
}

$pattern = explode(',', getenv('LINK_PATTERN3'));
$rc = preg_match('/' . $pattern[0] . '/', $res, $matches);
if ($rc === 1) {
  $embed_url = str_replace($pattern[1], $matches[2], $pattern[2]);
  error_log($embed_url);
  $statement->execute([':b_uri' => $embed_url]);
  $pdo = null;
  
  echo '<HTML><HEAD>';
  echo '<TITLE>' . $title . '</TITLE>';
  echo '</HEAD><BODY>';
  echo '<iframe src="';
  echo $embed_url;
  echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
  echo '</BODY></HTML>';
 
  exit();
}

$pdo = null;

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
