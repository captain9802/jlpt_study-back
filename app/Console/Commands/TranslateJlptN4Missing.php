<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\JlptWord;

class TranslateJlptN4Missing extends Command
{
    protected $signature = 'translate:n4-missing';
    protected $description = 'N4 단어 중 meaning_ko가 비어 있는 항목을 GPT로 번역해서 저장';

    public function handle()
    {
        $words = JlptWord::whereJsonContains('levels', ['N4'])
            ->whereNull('meaning_ko')
            ->take(1000)
            ->get();

        $this->info("🔍 번역할 단어 수: " . $words->count());

        foreach ($words as $i => $word) {
            $translated = $this->translateWithGpt($word->meaning_en);

            if ($translated) {
                $word->update(['meaning_ko' => $translated]);
                $this->line("[$i] 번역 완료: {$word->word} → {$translated}");
            } else {
                $this->warn("[$i] 번역 실패: {$word->word}");
            }

            usleep(300000); // 0.3초 딜레이
        }

        $this->info('✅ GPT 기반 번역 완료!');
    }

    private function translateWithGpt($english)
    {
        $prompt = "영어 단어 또는 구문을 자연스러운 한국어로 번역해줘. 단어: {$english}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => '이 단어를 한국어로 번역해줘.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response['choices'][0]['message']['content'] ?? null;
    }
}
