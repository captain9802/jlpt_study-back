<?php
$text = $_GET['text'] ?? 'こんにちは~';

if (!$text) {
    http_response_code(400);
    echo '텍스트가 비어있음.';
    exit;
}

$tmpText = tempnam(sys_get_temp_dir(), 'jtalk_') . '.txt';
$tmpWav = tempnam(sys_get_temp_dir(), 'jtalk_') . '.wav';

file_put_contents($tmpText, $text);

$cmd = sprintf(
    'wsl open_jtalk -x /var/lib/mecab/dic/open-jtalk/naist-jdic ' .
    '-m /usr/share/hts-voice/nitech-jp-atr503-m001/nitech_jp_atr503_m001.htsvoice ' .
    '-ow %s %s',
    escapeshellarg($tmpWav),
    escapeshellarg($tmpText)
);

exec($cmd, $output, $returnCode);

if (!file_exists($tmpWav)) {
    http_response_code(500);
    echo 'TTS 변환 실패';
    exit;
}

header('Content-Type: audio/wav');
header('Content-Disposition: inline; filename="voice.wav"');
readfile($tmpWav);

// cleanup
unlink($tmpText);
unlink($tmpWav);
