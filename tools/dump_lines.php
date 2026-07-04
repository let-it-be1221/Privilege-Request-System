<?php
$f='c:/xampp/htdocs/privaccess/public/request_create.php';
$lines=file($f);
for($i=0;$i<count($lines);$i++){
  printf('%4d: %s', $i+1, $lines[$i]);
}
