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

        file_put_contents('php://stderr', "111111111\n");

        if (!isset($language)) {
            return response()->json([
                'message' => '언어 모드가 설정되지 않았습니다. 설정 후 다시 시도해주세요.',
                'require_language_mode' => true
            ], 200);
        }
        file_put_contents('php://stderr', "????????????????\n");

        file_put_contents('php://stderr', '🧪 userId: ' . $user->id . PHP_EOL);

        $messages = AiPromptGenerator::withRecentMessages(
            $user->id,
            $userMessage
        );
        file_put_contents('php://stderr', "222222222222222222\n");

        file_put_contents('php://stderr', "📤 GPT 전송 메시지:\n" . json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);
        file_put_contents('php://stderr', "3333333333333333333333\n");
        $content = $response->json()['choices'][0]['message']['content'] ?? '없음';
        file_put_contents('php://stderr', "GPT 응답 내용 (일본어):\n$content\n");
        $aiMessage = $response->json('choices.0.message.content');

        if ($content) {
            // 파싱 시도
            try {
                $parsed = json_decode($content, true);
                file_put_contents('php://stderr', "🧪 파싱된 응답:\n" . print_r($parsed, true));

                if (json_last_error() !== JSON_ERROR_NONE) {
                    file_put_contents('php://stderr', "❌ JSON 파싱 실패: " . json_last_error_msg() . "\n");
                    return response()->json(['error' => 'GPT 응답 JSON 파싱 실패'], 500);
                }

                $aiText = $parsed['text'] ?? '';
                file_put_contents('php://stderr', "✅ 응답 저장 완료\n");
            } catch (\Exception $e) {
                file_put_contents('php://stderr', "❗ 예외 발생: " . $e->getMessage() . "\n");
                return response()->json(['error' => 'GPT 응답 처리 중 예외 발생'], 500);
            }
        } else {
            file_put_contents('php://stderr', "❌ GPT content가 비어 있음\n");
        }

        if ($aiMessage) {
            AiPromptGenerator::saveAssistantResponse($user->id, $aiMessage);
        }
        file_put_contents('php://stderr', "44444444444444444444\n");

        return response()->json($response->json());
    }

    public function saveSummary(Request $request) {
        file_put_contents('php://stderr', "여기는 세이브 서머리\n");

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
        file_put_contents('php://stderr', "여기는 겟메모리\n");

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
        $messages = [
            [
                'role' => 'system',
                'content' => '너는 일본어 학습을 도와주는 AI입니다.',
            ],
            [
                                    'role' => 'user',
                                    'content' => <<<PROMPT
                                 {$sentence}를 다음 형식의 JSON 구조로 분석해주세요. 문장의 의미, 문법, 단어 정보를 포함해야 하며, 출력은 반드시 아래 구조에 따라 JSON 형태로만 해주세요.

                                예시 문장: 明日は友だちと映画を見に行く予定です。

                                반환 포맷 예시:

                                {
                                  "translation": "내일은 친구와 영화를 보러 갈 예정입니다.",
                                  "grammar": [
                                    { "text": "〜に行く", "meaning": "~하러 가다" },
                                    { "text": "予定です", "meaning": "~할 예정이다" }
                                  ],
                                  "words": [
                                    {
                                      "text": "予定",
                                      "reading": "よてい",
                                      "meaning": "예정",
                                      "onyomi": "よてい",
                                      "kunyomi": "なし",
                                      "examples": ["予定通り – 예정대로", "予定日 – 예정일"],
                                      "showDetail": false
                                    },
                                    {
                                      "text": "明日",
                                      "reading": "あした",
                                      "meaning": "내일",
                                      "onyomi": "メイニチ",
                                      "kunyomi": "あした / あす",
                                      "examples": ["明日会いましょう – 내일 만나자"],
                                      "showDetail": false,
                                      "breakdown": [
                                        {
                                          "kanji": "明",
                                          "onyomi": "メイ",
                                          "kunyomi": "あか・あき・あけ"
                                        },
                                        {
                                          "kanji": "日",
                                          "onyomi": "ニチ",
                                          "kunyomi": "ひ・か"
                                        }
                                      ]
                                    }
                                  ]
                                }

                                조건:
                                - 반드시 JSON 형식만 출력 (설명문 금지)
                PROMPT
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
            ]);

            $json = $response->json();
            $gptRaw = $json['choices'][0]['message']['content'] ?? '';

            file_put_contents('php://stderr', "📩 GPT 원문 응답:\n" . $gptRaw);

            preg_match('/\{[\s\S]*\}/', $gptRaw, $matches);
            $contentText = $matches[0] ?? '{}';
            file_put_contents('php://stderr', "🔍 추출된 JSON 텍스트:\n" . $contentText);

            $content = json_decode($contentText, true);

            if (!$content || !is_array($content)) {
                file_put_contents('php://stderr', "🚨 JSON 파싱 실패!");
                return response()->json([
                    'error' => 'GPT 응답을 JSON으로 파싱하지 못했습니다.',
                    'raw' => $gptRaw,
                ], 500);
            }

            return response()->json([
                'explanation' => [
                    'grammar' => $content['grammar'] ?? [],
                ],
                'words' => $content['words'] ?? [],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'GPT 요청 실패',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
