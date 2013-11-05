<?php
 
$command = "python  ./skos_importer_pythonLib_clean.py"
$command .= " 2>&1";
header('Content-Type: text/html; charset=utf-8');
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
echo "<style type='text/css'>
 body{
 background:#000;
 color: #7FFF00;
 font-family:'Lucida Console',sans-serif !important;
 font-size: 12px;
 }
 </style>";
 
$pid = popen( $command,"r");
 
echo "<body><pre>";
while( !feof( $pid ) ) {
 echo fread($pid, 256);
 echo 'out';
 flush();
 ob_flush();
 echo "<script>window.scrollTo(0,99999);</script>";
 usleep(100000);
}
pclose($pid);

echo "</pre><script>window.scrollTo(0,99999);</script>";
echo "<br /><br />--------------------------END -------------------------------<br /><br />";

?>
