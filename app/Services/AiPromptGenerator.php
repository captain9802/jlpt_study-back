<?php

namespace App\Services;

use App\Models\JlptWord;
use App\Models\UserAiSetting;
use App\Models\ChatMemory;
use App\Models\AiPrompt;
use Illuminate\Support\Facades\Http;

class AiPromptGenerator
{
    public static function generate(string $language, UserAiSetting $settings): string
    {
        $userId = $settings->user_id;

        $existingPrompt = AiPrompt::where('user_id', $userId)
            ->where('language', $language)
            ->first();

        $langComment = match ($language) {
            'jp-only' => "※ 반드시 일본어로만 응답하세요. 질문이 한국어여도 일본어로만 대답하세요.",
            'ko' => "※ 일본어 문장을 중심으로, 해석이나 설명은 한국어로 혼합해서 제공하세요.",
        };

        $systemPrompt = <<<PROMPT
        너는 일본어 학습 AI야. 이름은 "{$settings->name}"이고, {$settings->personality} 성격, {$settings->tone} 말투를 사용해.

        {$langComment}
        - 같은 주제는 충분히 이어서 설명
    PROMPT;

        AiPrompt::updateOrCreate(
            ['user_id' => $userId, 'language' => $language],
            ['prompt' => $systemPrompt]
        );

        return $systemPrompt;
    }


    public static function getBalancedWords(string $userLevel, int $maxWords = 60): string
    {
        $levelWeights = [
            'N1' => ['N1' => 60, 'N2' => 13, 'N3' => 9, 'N4' => 9, 'N5' => 9],
            'N2' => ['N2' => 60, 'N3' => 20, 'N4' => 10, 'N5' => 10],
            'N3' => ['N3' => 60, 'N4' => 25, 'N5' => 15],
            'N4' => ['N4' => 65, 'N5' => 35],
            'N5' => ['N5' => 100],
        ];

        $levelRatio = $levelWeights[$userLevel];
        $entries = [];

        foreach ($levelRatio as $level => $percent) {
            $count = intval($maxWords * $percent / 100);
            $words = \App\Models\JlptWord::whereJsonContains('levels', $level)
                ->inRandomOrder()
                ->limit($count)
                ->get(['word', 'kana', 'levels']);

            foreach ($words as $word) {
                $entries[] = "{$word->word}（{$word->kana}）: {$level}";
            }
        }

        return implode("\n", $entries);
    }


    public static function withRecentMessages(int $userId, string $userMessage): array
    {
        $existingPrompt = AiPrompt::where('user_id', $userId)->first();

        if (!$existingPrompt) {
            throw new \Exception('AI 프롬프트가 존재하지 않습니다. 먼저 설정을 저장해주세요.');
        }

        $systemPrompt = $existingPrompt->prompt;

        $jlptLevel = UserAiSetting::where('user_id', $userId)->value('jlpt_level');

        $wordList = self::getBalancedWords($jlptLevel);
        $wordGuide = <<<TXT
                                📘 이번 대화에서 사용할 수 있는 단어들 (JLPT {$jlptLevel} 이하):

                                {$wordList}

                                이 단어들 중 상황에 맞는 표현을 자연스럽게 사용하세요. 그 외 단어는 사용하지 마세요.
                                ~ 가 붙어있는 단어는 ~를 붙여서 사용하세요.
                                TXT;

        $recentMemories = ChatMemory::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->reverse()
            ->map(fn($m) => [
                'role' => 'user',
                'content' => $m->summary
            ])
            ->values()
            ->all();

        array_unshift($recentMemories, [
            'role' => 'system',
            'content' => $wordGuide
        ]);

        ChatMemory::create([
            'user_id' => $userId,
            'summary' => $userMessage
        ]);
        $recentMemories[] = [
            'role' => 'user',
            'content' => $userMessage
        ];
        $recentMemories[] = [
            'role' => 'system',
            'content' => $systemPrompt
        ];
        return $recentMemories;
    }



    public static function saveAssistantResponse(int $userId, string $message): void
    {
        ChatMemory::create([
            'user_id' => $userId,
            'summary' => $message
        ]);

        $count = ChatMemory::where('user_id', $userId)->count();

        if ($count > 20) {
            ChatMemory::where('user_id', $userId)
                ->orderBy('id', 'asc')
                ->limit($count - 20)
                ->delete();
        }
    }

    public static function gptTranslate(string $text): string
    {
        $res = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '다음 일본어 문장을 한국어로 자연스럽게 번역해줘.'
                ],
                [
                    'role' => 'user',
                    'content' => $text
                ]
            ],
            'temperature' => 0.5,
        ]);

        return $res['choices'][0]['message']['content'] ?? '';
    }
}
