<?php
$p = proc_open('php -l', array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
// socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $socks);
// $p = proc_open('php -l', array(0 => array('pipe', 'r'), 1 => $socks[0], 2 => $socks[0], $pipes));
// the following don't work
// $strm = fopen('var://tmp', 'w');
// $p = proc_open('php -l', array(array('pipe', 'r'), $strm, $strm), $pipes);
fwrite($pipes[0], "<\x3fphp echo 'syntax error'; \x3f>\n");
fclose($pipes[0]);
var_dump($pipes);
echo "pipe 1: " . fread($pipes[1], 8192) . "\n";
echo "pipe 2: " . fread($pipes[2], 8192) . "\n";
var_dump(proc_close($p));
// socket_close($socks[0]);
// socket_close($socks[1]);

echo "floof\n";
