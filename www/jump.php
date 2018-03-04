<?php

$jump_url = $_GET['u'];

$res = file_get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = file_get_contents($jump_url2);

for ($i = 0; $i < 10; $i++) {
  if (getenv('PATTERN' . $i) !== FALSE) {
    $patterns[] = getenv('PATTERN' . $i);
  }
}

for ($i = 0; $i < 10; $i++) {
  if (getenv('PATTERN_B' . $i) !== FALSE) {
    $patterns_b[] = getenv('PATTERN_B' . $i);
  }
}

foreach ($patterns_b as $pattern) {
  $ar = explode(',', $pattern);
  $rc = preg_match($ar[0], $res, $matches);
  if ($rc === 1) {
    $jump_url3 = $matches[1];
    error_log('TEMP1 : ' . $jump_url3);
    $aaa = preg_replace($ar[1], $ar[2], $jump_url3);
    error_log('TEMP2 : ' . $aaa);
    
    /*
    echo '<HTML><HEAD>';
    echo '<TITLE>' . $title . '</TITLE>';
    echo '</HEAD><BODY>';
    echo '<iframe src="';
    echo $jump_url3;
    echo '"></iframe>';
    echo '</BODY></HTML>';
    exit();
    */
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

?>
