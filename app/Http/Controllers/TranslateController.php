<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TranslateController extends Controller
{
    public function translate(Request $request)
    {
        $text = $request->input('text');
        $type = $request->input('type', 'sentence');

        if (!$text) {
            return response()->json(['error' => '문장이 필요합니다.'], 422);
        }

        $cacheKey = "translate:{$type}:" . md5($text);
        if (cache()->has($cacheKey)) {
            return response()->json(cache()->get($cacheKey));
        }

        $systemPrompt = match ($type) {
            'word' => <<<SYS
            다음 단어를 번역해줘.
            - 설명이나 예시 없이 JSON만 출력
        SYS,
            'sentence' => <<<SYS
            다음 문장을 분석해줘.
            - 번역, 단어, 문법 정보를 모두 포함
            - 반드시 JSON 형식만 출력 (마크다운, 설명 X)
            - 구조는 다음과 같아야 함:
            {
              "translation": "번역된 문장",
              "grammar": [
                { "text": "문법 표현", "meaning": "간결한 뜻(한국어)" }
              ],
              "words": [
                { "text": "단어", "reading": "히라가나", "meaning": "뜻(한국어)" }
              ]
            }
        SYS
        };

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $text]
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
                    'error' => 'GPT 응답을 파싱할 수 없습니다.',
                    'raw' => $gptRaw
                ], 500);
            }

            $result = match ($type) {
                'word' => [
                    'translation' => $content['translation'] ?? '',
                ],
                'sentence' => [
                    'translation' => $content['translation'] ?? '',
                    'words' => $content['words'] ?? [],
                    'explanation' => [
                        'grammar' => $content['grammar'] ?? [],
                    ]
                ]
            };

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
