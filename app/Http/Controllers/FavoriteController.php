<?php

namespace App\Http\Controllers;
use App\Models\Favorite;
use App\Models\FavoriteGrammar;
use App\Models\FavoriteGrammarList;
use App\Models\FavoriteSentence;
use App\Models\FavoriteSentenceGrammar;
use App\Models\FavoriteSentenceList;
use App\Models\FavoriteSentenceQuiz;
use App\Models\FavoriteSentenceQuizChoice;
use App\Models\FavoriteSentenceWord;
use App\Models\GrammarChoice;
use App\Models\GrammarExample;
use App\Models\GrammarQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FavoriteController extends Controller
{
    public function index($listId)
    {
        return Favorite::where('list_id', $listId)->get();
    }

    public function getFavoriteWords(Request $request)
    {
        $user = $request->user();

        $words = Favorite::whereHas('wordList', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->get(['text', 'breakdown'])
            ->filter(fn($fav) => $fav->text)
            ->map(fn($fav) => [
                'text' => $fav->text,
                'breakdown' => $fav->breakdown,
            ])
            ->values();

        return response()->json($words);
    }

    public function toggleFavorite(Request $request)
    {
        try {
            $data = $request->only(['list_id', 'text', 'reading', 'meaning', 'onyomi', 'kunyomi', 'examples', 'breakdown']);
            file_put_contents('php://stderr', "1111111111111111111\n");

            $existingFavorite = Favorite::when(isset($data['list_id']), function ($query) use ($data) {
                return $query->where('list_id', $data['list_id']);
            })
                ->where('text', $data['text'])
                ->first();
            file_put_contents('php://stderr', "112222222222222\n");

            if ($existingFavorite) {
                file_put_contents('php://stderr', "33333333333리\n");
                $existingFavorite->delete();
                return response()->json(['message' => '단어가 즐겨찾기에서 삭제되었습니다.'], 200);
            } else {
                $favorite = Favorite::create($data);
                return response()->json(['message' => '단어가 즐겨찾기에 추가되었습니다.', 'data' => $favorite], 201);
            }
        } catch (\Exception $e) {
            file_put_contents('php://stderr', "[❌ 즐겨찾기 처리 실패] " . $e->getMessage());
            return response()->json(['message' => '서버 오류', 'error' => $e->getMessage()], 500);
        }
    }

    public function generateWordQuiz(Request $request)
    {
        file_put_contents('php://stderr', "11111111111111111111\n");

        $validated = $request->validate([
            'list_id' => 'required|integer',
            'order' => 'required|in:default,shuffle',
            'direction' => 'required|in:jp-ko,ko-jp,random',
        ]);
        file_put_contents('php://stderr', "11111111111111111111\n");

        $words = Favorite::where('list_id', $validated['list_id'])->get();
        file_put_contents('php://stderr', "22222222222222222222222\n");

        if ($validated['order'] === 'shuffle') {
            $words = $words->shuffle()->values();
        }
        file_put_contents('php://stderr', "3333333333333333333333333\n");

        $allWords = Favorite::where('list_id', $validated['list_id'])->get();
        file_put_contents('php://stderr', "4444444444444444444444444\n");

        $quiz = $words->map(function ($word) use ($validated, $allWords) {
            $jp = $word->text;
            $ko = $word->meaning;

            $mode = match ($validated['direction']) {
                'jp-ko' => 'jp-ko',
                'ko-jp' => 'ko-jp',
                'random' => rand(0, 1) === 1 ? 'jp-ko' : 'ko-jp',
            };

            $correctText = $mode === 'jp-ko' ? $ko : $jp;
            $correctTranslation = $mode === 'jp-ko' ? $jp : $ko;
            $question = $mode === 'jp-ko' ? $jp : $ko;

            $wrongOptionsRaw = $allWords
                ->where('id', '!=', $word->id)
                ->unique($mode === 'jp-ko' ? 'meaning' : 'text')
                ->shuffle()
                ->take(3)
                ->values();

            $wrongOptions = $wrongOptionsRaw->map(function ($item) use ($mode) {
                return [
                    'text' => $mode === 'jp-ko' ? $item->meaning : $item->text,
                    'translation' => $mode === 'jp-ko' ? $item->text : $item->meaning,
                ];
            })->toArray();
            file_put_contents('php://stderr', "555555555555555555555555\n");

            $options = array_merge([
                ['text' => $correctText, 'translation' => $correctTranslation]
            ], $wrongOptions);
            file_put_contents('php://stderr', "6666666666666666666666\n");

            shuffle($options);
            $answerIndex = array_search($correctText, array_column($options, 'text'));
            file_put_contents('php://stderr', "777777777777777777777777777777\n");

            return [
                'jp' => $question,
                'options' => $options,
                'answer' => $answerIndex
            ];
        });

        return response()->json($quiz->values());
    }

    public function getFavoriteGrammars(Request $request, $listId)
    {
        $user = $request->user();
        file_put_contents('php://stderr', "111111111111as11\n");

        $grammarList = FavoriteGrammarList::with([
            'grammars.examples',
            'grammars.quizzes.choices'
        ])
            ->where('id', $listId)
            ->where('user_id', $user->id)
            ->firstOrFail();
        file_put_contents('php://stderr', "22222222222222222\n");

        return response()->json($grammarList->grammars);
    }

    public function getAllGrammarTexts(Request $request)
    {
        $user = $request->user();

        $grammars = FavoriteGrammar::whereHas('grammarList', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->pluck('grammar');

        return response()->json($grammars);
    }

    public function toggleGrammarFavorite(Request $request)
    {
        $user = $request->user();
        file_put_contents('php://stderr', "11111111111111111111\n");
        file_put_contents('php://stderr', "0000000000000000\n");

        $data = $request->validate([
            'list_id' => 'nullable|integer',
            'grammar' => 'required|string',
            'meaning' => 'required|string',
        ]);

        $grammarText = is_array($data['grammar']) ? ($data['grammar']['text'] ?? '') : $data['grammar'];
        $normalizedGrammar = preg_replace('/^〜/u', '', $grammarText);

        file_put_contents('php://stderr', "grammar raw: " . print_r($data['grammar'], true) . "\n");
        file_put_contents('php://stderr', "normalized: " . print_r($normalizedGrammar, true) . "\n");
        file_put_contents('php://stderr', "??????????????????????????\n");


        file_put_contents('php://stderr', "222222222222222222222\n");
        $exists = FavoriteGrammar::when(isset($data['list_id']), function ($query) use ($data) {
            return $query->where('list_id', $data['list_id']);
        })
            ->where('grammar', $normalizedGrammar)
            ->first();

        file_put_contents('php://stderr', "3333333333333333333\n");

        if ($exists) {
            $exists->delete();
            return response()->json(['message' => '즐겨찾기에서 삭제되었습니다.']);
        }
        file_put_contents('php://stderr', "444444444444444444444\n");

        // 새로 추가
        $newGrammar = FavoriteGrammar::create([
            'list_id' => $data['list_id'],
            'grammar' => $data['grammar'],
            'meaning' => $data['meaning'],
        ]);
        file_put_contents('php://stderr', "555555555555555555555555\n");

        // GPT 호출해서 예시 + 퀴즈 생성
        $gptResult = $this->generateGrammarData($data['grammar'], $data['meaning']);
        file_put_contents('php://stderr', "6666666666666666666666\n");

        if (!$gptResult) {
            return response()->json(['message' => 'GPT 생성 실패'], 500);
        }
        file_put_contents('php://stderr', "777777777777777777777777\n");

        // 1. 예시 저장
        foreach ($gptResult['examples'] as $example) {
            GrammarExample::create([
                'grammar_id' => $newGrammar->id,
                'ja' => $example['ja'],
                'ko' => $example['ko'],
            ]);
        }
        file_put_contents('php://stderr', "888888888888\n");

        // 2. 퀴즈 저장
        foreach ($gptResult['quizzes'] as $quizData) {
            $quiz = GrammarQuiz::create([
                'grammar_id' => $newGrammar->id,
                'question' => $quizData['question'],
                'translation' => $quizData['translation'],
                'answer' => $quizData['answer'],
            ]);
            file_put_contents('php://stderr', "9999999999999999999999999\n");

            foreach ($quizData['choices'] as $choice) {
                GrammarChoice::create([
                    'quiz_id' => $quiz->id,
                    'text' => $choice['text'],
                    'meaning' => $choice['meaning'],
                    'is_correct' => $choice['is_correct'],
                    'explanation' => $choice['explanation'],
                ]);
            }
        }
        return response()->json(['message' => '즐겨찾기에 추가되었습니다.']);
    }

    private function generateGrammarData($grammar, $meaning)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "너는 일본어 학습 AI야. 사용자에게 다음 형식으로 JSON만 응답해야 해. 설명 추가 금지.

                    {
                      \"examples\": [
                        { \"ja\": \"...\", \"ko\": \"...\" },
                        { \"ja\": \"...\", \"ko\": \"...\" },
                        { \"ja\": \"...\", \"ko\": \"...\" }
                      ],
                      \"quizzes\": [
                        {
                          \"question\": \"...\",
                          \"translation\": \"...\",
                          \"answer\": \"...\",
                          \"choices\": [
                            { \"text\": \"...\", \"meaning\": \"...\", \"is_correct\": true, \"explanation\": \"...\" },
                            { \"text\": \"...\", \"meaning\": \"...\", \"is_correct\": false, \"explanation\": \"...\" },
                            { \"text\": \"...\", \"meaning\": \"...\", \"is_correct\": false, \"explanation\": \"...\" },
                            { \"text\": \"...\", \"meaning\": \"...\", \"is_correct\": false, \"explanation\": \"...\" }
                          ]
                        },
                        (3문제 생성)
                      ]
                    }"
                    ],
                    [
                        'role' => 'user',
                        'content' => "문법 표현: {$grammar} ({$meaning})"
                    ]
                ],
                'temperature' => 0.3,
            ]);

            $content = $response->json('choices.0.message.content');

            return json_decode($content, true);

        } catch (\Exception $e) {
            file_put_contents('php://stderr', "GPT 호출 실패: " . $e->getMessage() . "\n");
            return null;
        }
    }

    public function getFavoriteSentences($list_id, Request $request)
    {
        $user = $request->user();
        file_put_contents('php://stderr', "11111111111111111111111111\n");

            $sentences = FavoriteSentence::with(['words', 'grammar'])
                ->where('list_id', $list_id)
                ->whereHas('sentenceList', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->get();

        file_put_contents('php://stderr', "222222222222222222222222\n");

        $formatted = $sentences->map(function ($s) {
            return [
                'id' => $s->id,
                'text' => $s->text,
                'translation' => $s->translation,
                'words' => $s->words->map(fn($w) => [
                    'text' => $w->text,
                    'meaning' => $w->meaning,
                    'reading' => $w->reading,
                ]),
                'grammar' => $s->grammar->map(fn($g) => [
                    'text' => $g->text,
                    'meaning' => $g->meaning
                ])
            ];
        });
        file_put_contents('php://stderr', "33333333333333333333333333333333\n");

        return response()->json($formatted);
    }

    public function getAllSentenceTexts(Request $request)
    {
        $user = $request->user();

        $texts = FavoriteSentence::whereHas('sentenceList', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->pluck('text');

        return response()->json($texts);
    }

    public function toggleSentenceFavorite(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'list_id' => 'required|integer',
            'text' => 'required|string',
            'translation' => 'nullable|string',
        ]);

        // 기존에 있으면 삭제
        $existing = FavoriteSentence::where('list_id', $data['list_id'])
            ->where('text', $data['text'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => '즐겨찾기에서 삭제되었습니다.']);
        }

        // GPT로 문장 구성 요청
        $generated = $this->sendGptSentencePrompt($data['text']);
        if (!$generated) {
            return response()->json(['message' => 'GPT 응답 오류'], 500);
        }

        // 문장 저장
        $sentence = FavoriteSentence::create([
            'list_id' => $data['list_id'],
            'text' => $generated['text'] ?? $data['text'],
            'translation' => $generated['translation'] ?? ($data['translation'] ?? ''),
        ]);

        foreach ($generated['words'] ?? [] as $word) {
            FavoriteSentenceWord::create([
                'sentence_id' => $sentence->id,
                'text' => $word['text'],
                'reading' => $word['reading'] ?? '',
                'meaning' => $word['meaning'] ?? '',
            ]);
        }

        foreach ($generated['grammar'] ?? [] as $g) {
            FavoriteSentenceGrammar::create([
                'sentence_id' => $sentence->id,
                'text' => $g['text'],
                'meaning' => $g['meaning'] ?? '',
                'reading' => $g['reading'] ?? '',
            ]);
        }

        foreach ($generated['quizzes'] ?? [] as $quizData) {
            $quiz = FavoriteSentenceQuiz::create([
                'sentence_id' => $sentence->id,
                'question' => $quizData['question'],
                'question_ko' => $quizData['question_ko'] ?? '',
                'explanation' => $quizData['explanation'] ?? '',
            ]);

            foreach ($quizData['choices'] ?? [] as $choice) {
                FavoriteSentenceQuizChoice::create([
                    'quiz_id' => $quiz->id,
                    'text' => $choice['text'],
                    'meaning' => $choice['meaning'] ?? '',
                    'is_correct' => $choice['isCorrect'] ?? false,
                ]);
            }
        }

        return response()->json(['message' => '즐겨찾기에 추가되었습니다.']);
    }

    function sendGptSentencePrompt(string $sentence): ?array
    {
        $prompt = <<<PROMPT
다음 일본어 문장을 기반으로 학습용 데이터를 JSON 형식으로 만들어줘.

문장:
{$sentence}

요청 형식:
{
  "text": "문장 (일본어)",
  "translation": "문장 해석 (한국어)",
  "words": [
    { "text": "...", "reading": "...", "meaning": "..." }
  ],
  "grammar": [
    { "text": "...", "meaning": "..." }
  ],
  "quizzes": [
    {
      "question": "...",
      "question_ko": "...",
      "choices": [
        { "text": "...", "meaning": "...", "isCorrect": true },
        ...
      ],
      "explanation": "..."
    }
  ]
}

조건:
- 단어는 중요한 것만 2~4개만 뽑아줘
- 문법은 핵심 표현 1~2개로 간단히
- 퀴즈는 2개 이상, 보기 4개 (1개만 정답)
- 전체 응답은 JSON 형식으로, 불필요한 텍스트 없이 출력
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ["role" => "system", "content" => "너는 일본어 학습 데이터를 만드는 AI야. 응답은 반드시 JSON으로만 출력해."],
                    ["role" => "user", "content" => $prompt],
                ],
            ]);

            $content = $response['choices'][0]['message']['content'] ?? '';
            return json_decode($content, true);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
