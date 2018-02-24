<?php

$jump_url = $_GET['u'];

$res = file_get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = file_get_contents($jump_url2);

$rc = preg_match(getenv('PATTERN1'), $res, $matches);

if ($rc === 1) {
  $jump_url3 = $matches[1];
  error_log($jump_url3);
  header('Location: ' . $jump_url3);
} else {
  $rc = preg_match(getenv('PATTERN2'), $res, $matches);
  if ($rc === 1) {
    $jump_url3 = $matches[1];
    error_log($jump_url3);
    header('Location: ' . $jump_url3);
  } else {
  header('Location: ' . $jump_url2);
  }
}
?>
