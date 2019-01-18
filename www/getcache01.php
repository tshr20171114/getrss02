<?php

$time_start = time();
error_log("START ${requesturi} " . date('Y/m/d H:i:s', $time_start));

for ($i = 0; $i < 40; $i++) {

}

$time_finish = time();
error_log("FINISH " . date('s', $time_finish - $time_start) . 's');
