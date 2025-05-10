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

            $existingFavorite = Favorite::when(isset($data['list_id']), function ($query) use ($data) {
                return $query->where('list_id', $data['list_id']);
            })
                ->where('text', $data['text'])
                ->first();

            if ($existingFavorite) {
                $existingFavorite->delete();
                return response()->json(['message' => '단어가 즐겨찾기에서 삭제되었습니다.'], 200);
            } else {
                $favorite = Favorite::create($data);
                return response()->json(['message' => '단어가 즐겨찾기에 추가되었습니다.', 'data' => $favorite], 201);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => '서버 오류', 'error' => $e->getMessage()], 500);
        }
    }

    public function generateWordQuiz(Request $request)
    {
        $validated = $request->validate([
            'list_id' => 'required|integer',
            'order' => 'required|in:default,shuffle',
            'direction' => 'required|in:jp-ko,ko-jp,random',
        ]);

        $words = Favorite::where('list_id', $validated['list_id'])->get();

        if ($validated['order'] === 'shuffle') {
            $words = $words->shuffle()->values();
        }

        $allWords = Favorite::where('list_id', $validated['list_id'])->get();

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

            $options = array_merge([
                ['text' => $correctText, 'translation' => $correctTranslation]
            ], $wrongOptions);

            shuffle($options);
            $answerIndex = array_search($correctText, array_column($options, 'text'));

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

        $grammarList = FavoriteGrammarList::with([
            'grammars.examples',
            'grammars.quizzes.choices'
        ])
            ->where('id', $listId)
            ->where('user_id', $user->id)
            ->firstOrFail();

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

        $data = $request->validate([
            'list_id' => 'nullable|integer',
            'grammar' => 'required|string',
            'meaning' => 'required|string',
        ]);
        $grammarText = is_array($data['grammar']) ? ($data['grammar']['text'] ?? '') : $data['grammar'];

        $exists = FavoriteGrammar::query()
            ->when(isset($data['list_id']), fn($q) => $q->where('list_id', $data['list_id']))
            ->where('grammar', $grammarText)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json(['message' => '즐겨찾기에서 삭제되었습니다.']);
        }

        $newGrammar = FavoriteGrammar::create([
            'list_id' => $data['list_id'],
            'grammar' => $data['grammar'],
            'meaning' => $data['meaning'],
        ]);

        $gptResult = $this->generateGrammarData($data['grammar'], $data['meaning']);

        if (!$gptResult) {
            return response()->json(['message' => 'GPT 생성 실패'], 500);
        }

        foreach ($gptResult['examples'] as $example) {
            GrammarExample::create([
                'grammar_id' => $newGrammar->id,
                'ja' => $example['ja'],
                'ko' => $example['ko'],
            ]);
        }

        foreach ($gptResult['quizzes'] as $quizData) {
            $quiz = GrammarQuiz::create([
                'grammar_id' => $newGrammar->id,
                'question' => $quizData['question'],
                'translation' => $quizData['translation'],
                'answer' => $quizData['answer'],
            ]);

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
                'model' => 'gpt-4-turbo',
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
            return null;
        }
    }

    public function getFavoriteSentences($list_id, Request $request)
    {
        $user = $request->user();

            $sentences = FavoriteSentence::with(['words', 'grammar'])
                ->where('list_id', $list_id)
                ->whereHas('sentenceList', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->get();

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
            'list_id' => 'nullable|integer',
            'text' => 'required|string',
            'translation' => 'nullable|string',
        ]);

        $normalizedText = trim($data['text']);

        $existing = FavoriteSentence::where('text', $normalizedText)->first();


        if ($existing) {
            $existing->delete();
            return response()->json(['message' => '즐겨찾기에서 삭제되었습니다.']);
        }

        $generated = $this->sendGptSentencePrompt($data['text']);
        if (!$generated) {
            return response()->json(['message' => 'GPT 응답 오류'], 500);
        }

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
너는 일본어 학습 데이터를 생성하는 AI야.

다음 일본어 문장을 기반으로, JLPT 스타일의 학습용 JSON 데이터를 아래 형식에 따라 만들어줘.
※ 반드시 **JSON 형식만 출력**하고, 설명, 주석, 마크다운, 기타 텍스트는 포함하지 마.

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
      "question": "...",        // JLPT 스타일 문제 (예: 빈칸 넣기, 올바른 표현 고르기 등)
      "question_ko": "...",     // 문제의 한국어 해석
      "choices": [
        { "text": "...", "meaning": "...", "isCorrect": true },
        { "text": "...", "meaning": "...", "isCorrect": false },
        { "text": "...", "meaning": "...", "isCorrect": false },
        { "text": "...", "meaning": "...", "isCorrect": false }
      ],
      "explanation": "정답인 이유 설명"
    }
  ]
}

조건:
- 단어는 문장에서 중요한 것만 2~4개 추출
- 문법은 핵심 표현 1~2개만 포함
- 퀴즈는 총 2개 이상 생성하고, **JLPT 시험 스타일로 구성**
  (예: 올바른 단어/문법 선택, 빈칸 채우기 등)
- 보기(choice)는 4개, 정답은 1개만
- 모든 응답은 **JSON 내부에만 포함**되어야 하며, 출력 전후로 어떤 문장도 포함하지 마
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo',
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

    public function generateGrammarQuiz(Request $request)
    {
        $validated = $request->validate([
            'list_id' => 'required|integer',
            'order' => 'required|in:default,shuffle',
        ]);
        $favorites = FavoriteGrammar::with(['quizzes.choices'])
            ->where('list_id', $validated['list_id'])
            ->get();
        $quizzes = collect();
        foreach ($favorites as $grammar) {
            if ($grammar->quizzes->isEmpty()) continue;

            $quiz = $grammar->quizzes->random();
            $choices = $quiz->choices->shuffle()->values();

            $answerIndex = $choices->search(fn($c) => $c->is_correct);

            $quizzes->push([
                'jp' => $quiz->question,
                'translation' => $quiz->translation,
                'options' => $choices->map(fn($c) => [
                    'text' => $c->text,
                    'translation' => $c->meaning,
                    'explanation' => $c->explanation
                ]),
                'answer' => $answerIndex
            ]);
        }
        if ($validated['order'] === 'shuffle') {
            $quizzes = $quizzes->shuffle()->values();
        }
        return response()->json($quizzes);
    }

    public function generateSentenceQuiz(Request $request)
    {
        $validated = $request->validate([
            'list_id' => 'required|integer',
            'order' => 'required|in:default,shuffle'
        ]);

        $sentences = FavoriteSentence::with('quizzes.choices')
            ->where('list_id', $validated['list_id'])
            ->get();

        $quizItems = collect();

        foreach ($sentences as $sentence) {
            if ($sentence->quizzes->isEmpty()) continue;

            $quiz = $sentence->quizzes->random();
            $choices = $quiz->choices->shuffle()->values();
            $answerIndex = $choices->search(fn($c) => $c->is_correct);
            $answerIndex = is_int($answerIndex) ? $answerIndex : 0;

            $quizItems->push([
                'jp' => $quiz->question,
                'translation' => $sentence->translation,
                'explanation' => $quiz->explanation ?? '',
                'question_ko' => $quiz->question_ko ?? '',
                'options' => $choices->map(fn($c) => [
                    'text' => $c->text,
                    'meaning' => $c->meaning
                ]),
                'answer' => $answerIndex
            ]);
        }

        if ($validated['order'] === 'shuffle') {
            $quizItems = $quizItems->shuffle()->values();
        }

        return response()->json($quizItems);
    }

    public function postWordDetail(Request $request)
    {
        $text = $request->input('text');

        if (!$text) {
            return response()->json(['error' => '단어가 필요합니다.'], 422);
        }

        $messages = [
            [
                'role' => 'system',
                'content' => <<<SYS

                            {$text}를 json으로 분석해줘. 전부 일본어로 작성해줘.
                            {
                            "onyomi": "이 단어의 음독",
                              "kunyomi": "이 단어의 훈독",
                              "examples": ["예문 – 해석"],  // 예문 1개 ~ 3개까지만
                              "breakdown": [
                                {
                                  "kanji": "한자",
                                  "onyomi": "한자의 음독이 있는 경우",
                                  "kunyomi": "한자의 훈독이 있는 경우"
                                }
                              ]
                            }
                            SYS
            ],
            [
                'role' => 'user',
                'content' => "단어: {$text}"
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4-turbo',
                'messages' => $messages,
                'temperature' => 0.2,
                'max_tokens' => 800
            ]);

            $gptRaw = $response->json('choices.0.message.content');
            preg_match('/\{[\s\S]*\}/', $gptRaw, $matches);
            $json = json_decode($matches[0] ?? '{}', true);

            return response()->json($json);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'GPT 요청 실패',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
