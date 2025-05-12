<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\JlptWord;

class ImportJlptN4Words extends Command
{
    protected $signature = 'import:jlpt-n4';
    protected $description = 'JLPT N4 단어만 GitHub CSV에서 가져와서 중복 없이 저장합니다';

    public function handle()
    {
        $url = 'https://raw.githubusercontent.com/elzup/jlpt-word-list/master/out/all.csv';
        $csv = file_get_contents($url);
        $rows = array_map('str_getcsv', explode("\n", $csv));
        $header = array_shift($rows);

        foreach ($rows as $i => $row) {
            if (count($row) < 4) continue;

            $word = $row[0];
            $kana = $row[1];
            $meaning_en = $row[2];
            $rawLevels = array_slice($row, 3);

            // ✅ 정확히 "JLPT_N4"가 포함된 경우만 처리
            $containsN4 = collect($rawLevels)->contains(fn($tag) => str_contains($tag, 'JLPT_N4'));
            if (!$containsN4) continue;

            // ✅ 중복 방지
            if (JlptWord::where('word', $word)->exists()) {
                $this->line("[$i] 이미 존재: $word → 건너뜀");
                continue;
            }

            // ✅ 번역
            $meaning_ko = $this->translateToKorean($meaning_en);
            if ($meaning_ko !== null) {
                $meaning_ko = str_replace(';', ',', $meaning_ko);
            }

            // ✅ 저장
            JlptWord::create([
                'word' => $word,
                'kana' => $kana,
                'meaning_ko' => $meaning_ko,
                'levels' => ['N4'],
            ]);

            $this->line("[$i] 저장됨: $word (N4) => $meaning_ko");
            usleep(300000); // 0.3초 딜레이
        }

        $this->info('🎉 JLPT_N4 단어 저장 완료');
    }

    private function translateToKorean($text)
    {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . env('DEEPL_API_KEY'),
        ])->post('https://api-free.deepl.com/v2/translate', [
            'text' => $text,
            'source_lang' => 'EN',
            'target_lang' => 'KO',
        ]);

        $translated = $response['translations'][0]['text'] ?? null;

        if (strcasecmp(trim($translated), trim($text)) === 0) {
            return null;
        }

        return $translated;
    }
}
