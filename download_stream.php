<?php
// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Prevent buffering

$downloadDir = __DIR__ . '/downloads';
if (!file_exists($downloadDir)) mkdir($downloadDir, 0777, true);

$ytDlpPath = __DIR__ . '/yt-dlp.exe';
$ffmpegPath = __DIR__ . '/ffmpeg.exe';

$url = $_GET['url'] ?? '';
$downloadType = $_GET['format'] ?? 'video';
$selectedFormat = $_GET['resolution'] ?? 'bestvideo+bestaudio/best';
$outformat = $_GET['outformat'] ?? 'mp4';

if (!$url) {
    echo "data: URL missing\n\n";
    exit;
}

$outputTemplate = $downloadDir . '/%(title)s.%(ext)s';

if ($downloadType === 'audio') {
    // Audio only
    $cmd = "\"$ytDlpPath\" -f bestaudio --extract-audio --audio-format $outformat --restrict-filenames --ffmpeg-location \"$ffmpegPath\" -o \"$outputTemplate\" " . escapeshellarg($url);
} else {
    // Video + audio merge
    // If user picked video-only format, add +bestaudio
    if (stripos($selectedFormat,'+') === false) {
        $selectedFormat .= '+bestaudio';
    }
    $cmd = "\"$ytDlpPath\" -f $selectedFormat --merge-output-format ".escapeshellarg($outformat)." --restrict-filenames --ffmpeg-location \"$ffmpegPath\" -o \"$outputTemplate\" " . escapeshellarg($url);
}

// Open process
$descriptorspec = [
    0 => ["pipe","r"],
    1 => ["pipe","w"],
    2 => ["pipe","w"]
];

$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    while (true) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        // Clean stderr: skip ffprobe warnings
        if ($stderr) {
            $lines = explode("\n", $stderr);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || stripos($line, 'ffprobe') !== false) continue;
                echo "data: [ERR] $line\n\n";
                @ob_flush(); flush();
            }
        }

        if ($stdout) {
            $lines = explode("\n", $stdout);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;
                echo "data: $line\n\n";
                @ob_flush(); flush();
            }
        }

        $status = proc_get_status($process);
        if (!$status['running']) break;
        usleep(50000);
    }

    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    // Find latest downloaded file
    $files = glob($downloadDir.'/*.'.$outformat);
    $latestFile = '';
    $latestTime = 0;
    foreach ($files as $f) {
        if (filemtime($f) > $latestTime) {
            $latestTime = filemtime($f);
            $latestFile = basename($f);
        }
    }

    if ($latestFile) {
        echo "data: DOWNLOAD_LINK:".$latestFile."\n\n";
        @ob_flush(); flush();
    }
}
