<?php
$f='c:/xampp/htdocs/privaccess/public/request_create.php';
$lines=file($f);
$open=0;$close=0;
for($i=0;$i<count($lines);$i++){
  $line=$lines[$i];
  if(strpos($line,'?>')!==false){ break;}
  $o = substr_count($line,'{');
  $c = substr_count($line,'}');
  if($o||$c) printf('%4d: +%d -%d %s', $i+1,$o,$c,rtrim($line,PHP_EOL).PHP_EOL);
  $open += $o; $close += $c;
}
echo "TOTAL in PHP block: opens=$open closes=$close\n";
