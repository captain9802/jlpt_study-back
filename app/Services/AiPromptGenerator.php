<?php

namespace App\Services;

use App\Models\JlptWord;
use App\Models\UserAiSetting;
use App\Models\ChatMemory;
use App\Models\AiPrompt;

class AiPromptGenerator
{
    public static function generate(string $language, UserAiSetting $settings): string
    {
        file_put_contents('php://stderr', "11111111111111111111됨\n");
        $userId = $settings->user_id;
        file_put_contents('php://stderr', "👤 userId: {$userId}\n");

        file_put_contents('php://stderr', "11111111111111111111됨\n");

        $existingPrompt = AiPrompt::where('user_id', $userId)
            ->where('language', $language)
            ->first();

        file_put_contents('php://stderr', "22222222222222222222222222222\n");

            $langComment = match ($language) {
                'jp-only' => "※ 반드시 일본어로만 응답하세요。한국어 사용 금지。질문이 한국어여도 일본어로만 대답하세요。",
                'ko' => "※ 문장은 일본어로, 해석과 설명은 한국어로 작성하세요。",
                default => "※ 일본어로 대화하고, 필요 시 한국어 해석을 포함하세요。",
            };
            file_put_contents('php://stderr', "아이우에오\n");

            $systemPrompt = <<<PROMPT
                                너는 일본어 학습 AI야. 이름은 \"{$settings->name}\"이고, {$settings->personality} 성격, {$settings->tone} 말투, {$settings->voice} 목소리를 사용해.

                                {$langComment}

                                - 문장 안에 한국어 설명 섞지 않기
                                - 자연스럽고 현재형 대화 유지
                                - 한 주제는 충분히 이어서 설명
                                - 사용 단어 설명은 아래 JSON 형식으로 제공
                                - 응답은 JSON 형식으로만 출력

                                JSON 형식:
                                {
                                  "text": "일본어 문장",
                                  "translation": "한국어 해석"
                                }

                                      여러 문장을 응답할 경우, 반드시 다음과 같은 배열로 감싸서 응답하세요:
                                    [
                                      { "text": "...", "translation": "..." },
                                      { "text": "...", "translation": "..." },
                                      ...
                                    ]

                                PROMPT;
        file_put_contents('php://stderr', "아우우우우우우우우\n");

            AiPrompt::updateOrCreate(
                ['user_id' => $userId, 'language' => $language],
                ['prompt' => $systemPrompt]
            );
        file_put_contents('php://stderr', "암ㄴ련ㄷ로ㅕ모쟈로먖ㄹ\n");

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
        file_put_contents('php://stderr', "111111111111111111\n");

        if (!$existingPrompt) {
            throw new \Exception('AI 프롬프트가 존재하지 않습니다. 먼저 설정을 저장해주세요.');
        }

        file_put_contents('php://stderr', "2222222222222222222\n");

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

        file_put_contents('php://stderr', "44444444444444444444\n");

        array_unshift($recentMemories, [
            'role' => 'system',
            'content' => $wordGuide
        ]);
        array_unshift($recentMemories, [
            'role' => 'system',
            'content' => $systemPrompt
        ]);

        file_put_contents('php://stderr', "5555555555555555555555\n");
        file_put_contents('php://stderr', "🧪 userId: {$userId}, message: {$userMessage}\n");

        ChatMemory::create([
            'user_id' => $userId,
            'summary' => $userMessage
        ]);

        file_put_contents('php://stderr', "66666666666666666666\n");

        $recentMemories[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        file_put_contents('php://stderr', "777777777777777777777\n");

        return $recentMemories;
    }



    public static function saveAssistantResponse(int $userId, string $message): void
    {
        ChatMemory::create([
            'user_id' => $userId,
            'summary' => $message
        ]);

        $count = ChatMemory::where('user_id', $userId)->count();

        if ($count > 10) {
            ChatMemory::where('user_id', $userId)
                ->orderBy('id', 'asc')
                ->limit($count - 10)
                ->delete();
        }
    }
}
