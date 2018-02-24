<?php

$jump_url = $_GET['u'];

$url = 'https://logs-01.loggly.com/inputs/' . getenv('LOGGLY_TOKEN') . "/tag/JUMP/";

$context = [
  'http' => [
    'method' => 'POST',
    'header' => [
      'Content-Type: text/plain'
    ],
  'content' => $jump_url
  ]];

file_get_contents($url, false, stream_context_create($context));

header('Location: ' . $jump_url);
?>
