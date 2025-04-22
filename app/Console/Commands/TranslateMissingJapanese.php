<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\JlptWord;

class TranslateMissingJapanese extends Command
{
    protected $signature = 'translate:missing-jp';
    protected $description = 'meaning_ko가 NULL인 항목을 일본어 기준으로 한국어로 번역해 저장';

    public function handle()
    {
        $words = JlptWord::whereNull('meaning_ko')->get();

        foreach ($words as $i => $word) {
            $text = $word->word;
            $meaning_ko = $this->translateFromJapanese($text);

            if ($meaning_ko !== null) {
                $meaning_ko = str_replace(';', ',', $meaning_ko);
                $word->meaning_ko = $meaning_ko;
                $word->save();
                $this->line("[$i] 저장됨: {$word->word} => {$meaning_ko}");
            } else {
                $this->line("[$i] 번역 실패 또는 무의미: {$word->word}");
            }

            usleep(300000);
        }

        $this->info('✅ 누락된 한국어 뜻 번역 완료');
    }

    private function translateFromJapanese($text)
    {
        $response = Http::asForm()->withHeaders([
            'Authorization' => 'DeepL-Auth-Key ' . env('DEEPL_API_KEY'),
        ])->post('https://api-free.deepl.com/v2/translate', [
            'text' => $text,
            'source_lang' => 'JA',
            'target_lang' => 'KO',
        ]);

        $translated = $response['translations'][0]['text'] ?? null;

        if (strcasecmp(trim($translated), trim($text)) === 0) {
            return null;
        }

        return $translated;
    }
}
