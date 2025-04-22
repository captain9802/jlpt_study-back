<?php

namespace App\Services;

use App\Models\UserAiSetting;
use App\Models\ChatMemory;
use App\Models\AiPrompt;

class AiPromptGenerator
{
    public static function generate(string $language, UserAiSetting $settings): string
    {
        $langComment = match ($language) {
            'jp-only' => "※ 반드시 일본어로만 응답하세요。한국어 사용 금지。질문이 한국어여도 일본어로만 대답하세요。",
            'ko' => "※ 문장은 일본어로, 해석과 설명은 한국어로 작성하세요。",
            default => "※ 일본어로 대화하고, 필요 시 한국어 해석을 포함하세요。",
        };

        return <<<PROMPT
                                너는 일본어 학습 AI야. 이름은 \"{$settings->name}\"이고, {$settings->personality} 성격, {$settings->tone} 말투, {$settings->voice} 목소리를 사용해.
                                언어모드는 \"{$settings->language_mode}\"이고, JLPT {$settings->jlpt_level} 수준의 단어를 사용해.

                                {$langComment}

                                - 응답은 JSON 형식으로만 출력
                                - 문장 안에 한국어 설명 섞지 않기
                                - 자연스럽고 현재형 대화 유지
                                - 한 주제는 충분히 이어서 설명
                                - 단어는 jlpt_words 테이블에서 문맥에 맞는 단어를 찾아 사용.
                                - 단어를 사용할때는 jlpt_words의 levels의 컬럼과 jlpt_level의 값이 같거나 더 낮은 단어를 사용.
                                - 사용 단어 설명은 아래 JSON 형식으로 제공

                                JSON 형식:
                                {
                                  "text": "일본어 문장",
                                  "translation": "한국어 해석"
                                }
                                PROMPT;
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
        file_put_contents('php://stderr', "33333333333333333333\n");

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
            'content' => $systemPrompt
        ]);
        file_put_contents('php://stderr', "5555555555555555555555\n");

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
    }
}
