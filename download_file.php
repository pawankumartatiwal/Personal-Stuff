<?php
$downloadDir = __DIR__.'/downloads';
$file = $_GET['file'] ?? '';
$path = realpath($downloadDir.'/'.$file);

if($path && strpos($path, realpath($downloadDir))===0 && file_exists($path)){
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($path));
    readfile($path);
    exit;
}else{
    echo "File not found!";
}
?>

