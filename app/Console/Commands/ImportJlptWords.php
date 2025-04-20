<?php
// 📄 Laravel Artisan 캡명: JLPT 단어 + DeepL 번역 생성 (레벨 범위 그룹)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\JlptWord;

class ImportJlptWords extends Command
{
    protected $signature = 'import:jlpt {--start=0}';
    protected $description = 'JLPT 단어를 GitHub CSV에서 가져와서 한국어로 번역해 저장합니다';

    public function handle()
    {
        $startAt = (int) $this->option('start');

        $url = 'https://raw.githubusercontent.com/elzup/jlpt-word-list/master/out/all.csv';
        $csv = file_get_contents($url);
        $rows = array_map('str_getcsv', explode("\n", $csv));
        $header = array_shift($rows); // [expression, reading, meaning, tags]

        foreach ($rows as $i => $row) {
            if ($i < $startAt) continue;
            if (count($row) < 4) continue;

            $word = $row[0];          // 일본어 단어
            $kana = $row[1];          // 읽기 (히라가나)
            $meaning_en = $row[2];    // 영어 뜻 (DeepL 번역에 사용)
            $levels = array_slice($row, 3); // JLPT 태그들

            $levelList = $this->extractJlptLevels($levels);
            if (empty($levelList)) continue;

            // 🔁 DeepL 번역 - 영어에서 한국어로
            $meaning_ko = $this->translateToKorean($meaning_en);

            // 세미콜론을 쉼표로 교체
            if ($meaning_ko !== null) {
                $meaning_ko = str_replace(';', ',', $meaning_ko);
            }

            // 📅 중복 확인 후 저장 또는 업데이트
            $existing = JlptWord::where('word', $word)->first();

            if ($existing) {
                $mergedLevels = array_unique(array_merge($existing->levels ?? [], $levelList));
                $existing->update([
                    'kana' => $kana,
                    'meaning_ko' => $meaning_ko,
                    'levels' => $mergedLevels,
                ]);
                $this->line("[$i] 업데이트됨: $word (" . implode(',', $mergedLevels) . ") => $meaning_ko");
            } else {
                JlptWord::create([
                    'word' => $word,
                    'kana' => $kana,
                    'meaning_ko' => $meaning_ko,
                    'levels' => $levelList,
                ]);
                $this->line("[$i] 저장됨: $word (" . implode(',', $levelList) . ") => $meaning_ko");
            }

            usleep(300000); // 0.3초 디레이 (DeepL 요금 보호)
        }

        $this->info('🎉 모든 단어 저장 완료');
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

        // 번역 결과가 영어 원문과 같은 경우 null 처리
        if (strcasecmp(trim($translated), trim($text)) === 0) {
            return null;
        }

        return $translated;
    }

    private function extractJlptLevels(array $tags)
    {
        $levels = [];
        foreach ($tags as $tag) {
            if (preg_match_all('/JLPT_(\d)/', $tag, $matches)) {
                foreach ($matches[1] as $levelDigit) {
                    $levels[] = 'N' . $levelDigit;
                }
            }
        }
        return array_unique($levels);
    }
}

/*
○ 예시:
JlptWord {
  word: "冷蔵庫",
  kana: "れいぞうこ",
  meaning_ko: "냉장고",
  levels: ["N5", "N4"]
}
*/
