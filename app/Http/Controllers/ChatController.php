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
        $messages = AiPromptGenerator::withRecentMessages($user->id, $userMessage);
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);
        $aiText = $response->json('choices.0.message.content');
        if (!$aiText) {
            return response()->json(['error' => 'GPT 응답 없음'], 500);
        }
        $result = [
            'text' => trim($aiText),
            'translation' => AiPromptGenerator::gptTranslate($aiText),
        ];
        AiPromptGenerator::saveAssistantResponse($user->id, json_encode($result, JSON_UNESCAPED_UNICODE));
        return response()->json($result);
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
                                    다음 조건을 반드시 지켜서 응답해:
                                    - 반드시 **JSON 형식만** 출력할 것 (설명 X, 주석 X, 마크다운 X)
                                    - grammar는 문법 / words는 단어
                                    - 구조는 아래와 같아야 함:
                                    {
                                      "grammar": [
                                        { "text": "문법 표현", "meaning": "간결한 뜻(한국어)" }
                                      ],
                                      "words": [
                                        { "text": "단어", "reading": "히라가나", "meaning": "뜻(한국어)" }
                                      ]
                                    }
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
                'max_tokens' => 512,
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
