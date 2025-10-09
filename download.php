<?php
// =======================================================
// Live YouTube Downloader Console Output
// =======================================================

// Disable buffering
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
ob_implicit_flush(1);

// Paths
$downloadDir = __DIR__ . '/downloads';
if (!file_exists($downloadDir)) mkdir($downloadDir, 0777, true);

$ytDlpPath = __DIR__ . '/yt-dlp.exe';
$ffmpegPath = __DIR__ . '/ffmpeg.exe';

$url = $_POST['url'] ?? '';
$downloadType = $_POST['format'] ?? 'video';
$selectedFormat = $_POST['resolution'] ?? 'bestvideo+bestaudio/best';
$outformat = $_POST['outformat'] ?? 'mp4';

if (!$url) {
    echo "URL missing!\n";
    exit;
}

// Output template
$outputTemplate = $downloadDir . '/%(title)s.' . $outformat;

// Build command
if ($downloadType === 'audio') {
    $cmd = "\"$ytDlpPath\" -f bestaudio --extract-audio --audio-format $outformat --restrict-filenames --ffmpeg-location \"$ffmpegPath\" -o \"$outputTemplate\" " . escapeshellarg($url);
} else {
    $cmd = "\"$ytDlpPath\" -f $selectedFormat --recode-video $outformat --restrict-filenames --ffmpeg-location \"$ffmpegPath\" -o \"$outputTemplate\" " . escapeshellarg($url);
}

// Set header for plain text
header('Content-Type: text/plain');

// Open process with pipes
$descriptorspec = [
    0 => ["pipe", "r"],   // stdin
    1 => ["pipe", "w"],   // stdout
    2 => ["pipe", "w"]    // stderr
];

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    while (true) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        if ($stdout) {
            echo $stdout;
            flush();
        }
        if ($stderr) {
            echo "[ERR] ".$stderr;
            flush();
        }

        $status = proc_get_status($process);
        if (!$status['running']) break;
        usleep(50000); // 0.05 sec
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    echo "\nDownload finished!\n";

    // Provide download link
    $files = glob($downloadDir.'/*.'.$outformat);
    $latestFile = '';
    $latestTime = 0;
    foreach($files as $f){
        if(filemtime($f) > $latestTime){
            $latestTime = filemtime($f);
            $latestFile = basename($f);
        }
    }

    if ($latestFile) {
        echo "\nDOWNLOAD_LINK:" . $latestFile;
    }
}
