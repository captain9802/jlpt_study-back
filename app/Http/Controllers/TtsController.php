<?php

namespace App\Http\Controllers;

use App\Models\UserAiSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class TtsController extends Controller
{
    public function speak(Request $request)
    {
        $text = $request->input('text');
        file_put_contents('php://stderr', "[TTS 요청 텍스트] " . $text . "\n");

        if (!$text) {
            return response()->json(['error' => '텍스트가 비어 있습니다.'], 400);
        }

        $user = Auth::user();

        // ✅ 설정 존재 여부 확인
        $aiSettingExists = UserAiSetting::where('user_id', $user->id)->exists();

        // ✅ 기본값
        $voice = '여성';
        $personality = '기본';
        $tone = '보통 말투';

        if ($aiSettingExists) {
            $ai = UserAiSetting::where('user_id', $user->id)->first();
            $voice = $ai->voice ?? $voice;
            $personality = $ai->personality ?? $personality;
            $tone = $ai->tone ?? $tone;
        }

        // ✅ voice 파일 선택
        $voiceFile = match ($voice) {
            '여성' => match ($personality) {
                '밝은' => 'mei_happy.htsvoice',
                '슬픈' => 'mei_sad.htsvoice',
                '단호한' => 'mei_angry.htsvoice',
                default => 'mei_normal.htsvoice',
            },
            '남성' => 'nitech_jp_atr503_m001.htsvoice',
            default => 'mei_normal.htsvoice',
        };

        $voicePath = ($voice === '남성')
            ? "/usr/share/hts-voice/nitech-jp-atr503-m001/{$voiceFile}"
            : "/usr/share/hts-voice/mei/{$voiceFile}";

        // ✅ tone → 속도/피치
        $rate = match ($tone) {
            '빠른 말투' => 1.2,
            '느린 말투' => 0.8,
            default => 1.0,
        };

        $pitch = match ($tone) {
            '높은 톤' => 2.0,
            '낮은 톤' => -2.0,
            default => 0.0,
        };

        // ✅ 실행
        $uuid = Str::uuid();
        $outputPath = storage_path("app/tts_output_{$uuid}.wav");
        $escapedText = escapeshellarg($text);

        $command = "echo {$escapedText} | open_jtalk " .
            "-x \"/var/lib/mecab/dic/open-jtalk/naist-jdic\" " .
            "-m \"{$voicePath}\" " .
            "-r {$rate} -fm {$pitch} " .
            "-ow \"{$outputPath}\"";

        exec($command, $output, $code);

        if ($code !== 0 || !file_exists($outputPath)) {
            file_put_contents('php://stderr', "[TTS 실패] code={$code}\n");
            return response()->json(['error' => 'TTS 생성 실패'], 500);
        }

        return response()->file($outputPath, [
            'Content-Type' => 'audio/wav',
            'Content-Disposition' => 'inline; filename=\"tts.wav\"',
        ]);
    }
}
