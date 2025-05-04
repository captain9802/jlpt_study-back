<?php
namespace App\Http\Controllers;

use App\Models\AiPrompt;
use App\Models\ChatMemory;
use App\Models\UserAiSetting;
use App\Services\AiPromptGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\JlptWord;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $user = $request->user();
        $userMessage = $request->input('message');
        $language = $request->input('language');

        if (!isset($language)) {
            return response()->json([
                'message' => '언어 모드가 설정되지 않았습니다. 설정 후 다시 시도해주세요.',
                'require_language_mode' => true
            ], 200);
        }

        $messages = AiPromptGenerator::withRecentMessages(
            $user->id,
            $userMessage
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);
        $content = $response->json()['choices'][0]['message']['content'] ?? '없음';
        $aiMessage = $response->json('choices.0.message.content');

        if ($content) {
            try {
                $parsed = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['error' => 'GPT 응답 JSON 파싱 실패'], 500);
                }

                $aiText = $parsed['text'] ?? '';
            } catch (\Exception $e) {
                return response()->json(['error' => 'GPT 응답 처리 중 예외 발생'], 500);
            }
        }

        if ($aiMessage) {
            AiPromptGenerator::saveAssistantResponse($user->id, $aiMessage);
        }

        return response()->json($response->json());
    }

    public function saveSummary(Request $request) {

        $user = $request->user();
        $request->validate(['summary' => 'required|string|max:255']);

        $count = ChatMemory::where('user_id', $user->id)->count();
        if ($count >= 30) {
            ChatMemory::where('user_id', $user->id)->oldest()->first()->delete();
        }

        $memory = ChatMemory::create([
            'user_id' => $user->id,
            'summary' => $request->summary
        ]);

        return response()->json(['data' => $memory]);
    }

    public function getMemories(Request $request)
    {

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => '사용자 인증이 필요합니다.'], 401);
        }
        $memories = ChatMemory::where('user_id', $user->id)->latest()->get();

        $aiSettingExists = \App\Models\UserAiSetting::where('user_id', $user->id)->exists();

        $hasLanguageMode = \App\Models\UserAiSetting::where('user_id', $user->id)
            ->whereNotNull('language_mode')
            ->where('language_mode', '!=', '')
            ->exists();

        return response()->json([
            'data' => $memories,
            'Aisetting' => $aiSettingExists,
            'hasLanguageMode' => $hasLanguageMode,
        ]);
    }

    public function tooltip(Request $request)
    {
        $sentence = $request->input('text');
        if (!$sentence) {
            return response()->json(['error' => '문장이 필요합니다.'], 422);
        }

        $cacheKey = 'tooltip:' . md5($sentence);

        if (cache()->has($cacheKey)) {
            return response()->json(cache()->get($cacheKey));
        }

        $messages = [
            [
                'role' => 'system',
                'content' => <<<SYS
                너는 일본어 학습을 도와주는 AI야.

                다음 조건을 반드시 지켜서 응답해:
                - 반드시 **JSON 형식만** 출력할 것 (설명 X, 주석 X, 마크다운 X)
                - 구조는 아래와 같아야 함:

                {
                  "translation": "한국어 번역",
                  "grammar": [
                    { "text": "문법 표현", "meaning": "의미" }
                  ],
                  "words": [
                    {
                      "text": "단어",
                      "reading": "읽는 법",
                      "meaning": "뜻",
                      "onyomi": "음독",
                      "kunyomi": "훈독",
                      "examples": ["예문 – 해석"],  // 예문 1개만
                      "breakdown": [
                        {
                          "kanji": "한자",
                          "onyomi": "음독이 있는 경우",
                          "kunyomi": "훈독이 있는 경우"
                        }
                      ]
                    }
                  ]
                }

                - 단어 수는 3개 이내로 추출
                - 예문은 1개만 제공 (충분한 의미 전달이 가능해야 함)
                - breakdown은 가능한 주요 한자에 대해서만 작성
                - "훈독 없음" 또는 "음독 없음"은 "なし"으로 표시
                SYS
            ],
            [
                'role' => 'user',
                'content' => <<<PROMPT
                다음 문장을 분석해줘:

                "{$sentence}"
                PROMPT
            ]
        ];

        try {
            $response = Http::timeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo',
                'messages' => $messages,
                'max_tokens' => 1024,
            ]);

            $json = $response->json();
            $gptRaw = $json['choices'][0]['message']['content'] ?? '';

            preg_match('/\{[\s\S]*\}/', $gptRaw, $matches);
            $contentText = $matches[0] ?? '{}';
            $content = json_decode($contentText, true);

            if (!$content || !is_array($content)) {
                return response()->json([
                    'error' => 'GPT 응답을 JSON으로 파싱하지 못했습니다.',
                    'raw' => $gptRaw,
                ], 500);
            }

            $result = [
                'explanation' => [
                    'grammar' => $content['grammar'] ?? [],
                ],
                'words' => $content['words'] ?? [],
            ];
            cache()->put($cacheKey, $result, now()->addMinutes(1));
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'GPT 요청 실패',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

}
