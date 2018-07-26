<?php

$pid = getmypid();

$url = $_GET['u'];

error_log("${pid} ${url}");

header('Content-Type: text/plain');

$res = get_contents($url);

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);

if ($rc != 1) {
  echo 'NONE';
  exit;
}

$url2 = $matches[1];

error_log("${pid} ${url2}");

$res = get_contents($url2);

$url3 = 'none';
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

  error_log("${pid} " . $matches[1]);

  $url3 = str_replace($pattern[1], $matches[1], $pattern[2]);

  error_log("${pid} ${url3}");
}

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT 'DUMMY' FROM t_link WHERE uri = :b_uri
__HEREDOC__;
$statement = $pdo->prepare($sql);

$statement->execute([':b_uri' => $url3 ]);
$result = $statement->fetch();
if ($result === FALSE) {
  echo 'NONE';
} else {
  echo 'EXISTS';
}
$pdo = null;
exit;

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
