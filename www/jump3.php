<?php

$embed_url = $_GET['u'];
  
echo '<HTML><HEAD>';
echo '<TITLE>TEST</TITLE>';
echo '</HEAD><BODY>';
echo '<iframe src="';
echo $embed_url;
echo '" frameborder=0 width=100% height=480 scrolling=no></iframe>';
echo '</BODY></HTML>';

?>
