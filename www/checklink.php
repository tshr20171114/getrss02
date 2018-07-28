<?php

$pid = getmypid();

$url = $_GET['u'];

error_log("${pid} ${url}");

header('Content-Type: text/plain');

$url2 = get_contents(getenv('CHECKURL') . $url);

error_log("${pid} ${url2}");

if ($url2 === 'NONE') {
  echo 'NONE';
  exit;
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

$statement->execute([':b_uri' => $url2 ]);
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
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($http_code != '200') {
    $contents = 'NONE';
  }
  return $contents;
}
?>
