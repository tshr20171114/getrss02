<?php

$jump_url = $_GET['u'];

$res = file_get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = file_get_contents($jump_url2);

$connection_info = parse_url(getenv('DATABASE_URL'));
$pdo = new PDO(
  "pgsql:host=${connection_info['host']};dbname=" . substr($connection_info['path'], 1),
  $connection_info['user'],
  $connection_info['pass']);

$sql = <<< __HEREDOC__
SELECT M1.preg_match_pattern
  FROM m_pattern M1
 WHERE record_type = 1
 ORDER BY M1.pattern_id
__HEREDOC__;

foreach ($pdo->query($sql) as $row)
{
  $patterns[] = $row['preg_match_pattern'];
}

$pdo = null;

for ($i = 0; $i < 10; $i++) {
  if (getenv('PATTERN_B' . $i) !== FALSE) {
    $patterns_b[] = getenv('PATTERN_B' . $i);
  }
}

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
    exit();
  }
}

foreach ($patterns as $pattern) {
  $rc = preg_match($pattern, $res, $matches);
  if ($rc === 1) {
    $jump_url3 = $matches[1];
    error_log($jump_url3);
    header('Location: ' . $jump_url3);
    exit();
  }
}

header('Location: ' . $jump_url2);

$pdo = null;
?>
