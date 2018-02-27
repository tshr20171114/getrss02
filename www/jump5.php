<?php

$jump_url = $_GET['u'];

$res = file_get_contents($jump_url);

$rc = preg_match('/<title>(.+)<.title>/', $res, $matches);
$title = $matches[1];

$rc = preg_match('/<div class="gotoBlog"><a href="(.+?)"/', $res, $matches);
$jump_url2 = $matches[1];
 
error_log('TITLETAG ' . $jump_url . ' ' . $title . ' ' . $jump_url2);

$res = file_get_contents($jump_url2);

$pattern=getenv('TEST01');
$rc = preg_match($pattern, $res, $matches);

error_log($matches[1]);

$base_url=getenv('TEST11');
$embed_url=str_replace('__TARGET__', $matches[1], $base_url);
error_log($embed_url);

$test99=getenv('TEST99');

echo '<HTML><HEAD>';
echo '<TITLE>' . $title . '</TITLE>';
echo '</HEAD><BODY>';
echo $embed_url;
echo '</BODY></HTML>';

?>
