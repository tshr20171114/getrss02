<?php

$pid = getmypid();

$url = $_GET['u'];

error_log("${pid} ${url}");

$res = file_get_contents($url);

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);

if ($rc != 1) {
  exit;
}

$url2 = $matches[1];

error_log("${pid} ${url2}");

$res = file_get_contents($url2);

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


header('Content-Type: text/plain');

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
?>
