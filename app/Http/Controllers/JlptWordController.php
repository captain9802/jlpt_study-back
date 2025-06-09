<?php

namespace App\Http\Controllers;


use App\Models\JlptWord;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class JlptWordController extends Controller
{
    public function getByLevel(Request $request)
    {
        $level = strtoupper($request->query('level', 'N3'));
        $perPage = (int) $request->query('per_page', 50);
        $page = (int) $request->query('page', 1);
        if (!in_array($level, ['N1', 'N2', 'N3', 'N4', 'N5'])) {
            return response()->json(['error' => '잘못된 레벨입니다.'], 400);
        }
        $words = JlptWord::whereJsonContains('levels', [$level])
            ->select('word', 'kana', 'meaning_ko')
            ->orderBy('id')
            ->paginate($perPage, ['word', 'kana', 'meaning_ko'], 'page', $page);
        return response()->json($words->items());
    }

    public function getByJlptQuiz(Request $request)
    {
        $validated = $request->validate([
            'list_id' => 'required|string',
            'order' => 'required|in:default,shuffle',
            'direction' => 'required|in:jp-ko,ko-jp,random',
            'count' => 'required|string|min:1|max:3000',
        ]);
        $levelTag = strtoupper($request->input('list_id'));
        $wordsQuery = JlptWord::whereJsonContains('levels', [$levelTag]);
        if ($validated['order'] === 'shuffle') {
            $wordsQuery = $wordsQuery->inRandomOrder();
        }
        $words = $wordsQuery->limit($validated['count'])->get();
        $allWords = JlptWord::whereJsonContains('levels', [$levelTag])->get();
        $quiz = $words->map(function ($word) use ($validated, $allWords) {
            $jp = $word->word;
            $ko = $word->meaning_ko;

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
                ->unique($mode === 'jp-ko' ? 'meaning_ko' : 'word')
                ->shuffle()
                ->take(3)
                ->values();
            $wrongOptions = $wrongOptionsRaw->map(function ($item) use ($mode) {
                return [
                    'text' => $mode === 'jp-ko' ? $item->meaning_ko : $item->word,
                    'translation' => $mode === 'jp-ko' ? $item->word : $item->meaning_ko,
                ];
            })->toArray();
            $options = array_merge([
                ['text' => $correctText, 'translation' => $correctTranslation]
            ], $wrongOptions);
            shuffle($options);
            $options = collect($options)->map(function ($opt) use ($allWords, $mode) {
                $matched = $allWords->first(function ($w) use ($opt, $mode) {
                    return $mode === 'jp-ko'
                        ? $w->meaning_ko === $opt['text']
                        : $w->word === $opt['text'];
                });
                return [
                    ...$opt,
                    'kana' => $matched?->kana ?? null
                ];
            })->toArray();
            $answerIndex = array_search($correctText, array_column($options, 'text'));
            return [
                'jp' => $question,
                'options' => $options,
                'answer' => $answerIndex,
                'kana' => $word->kana
            ];
        });

        return response()->json($quiz->values());
    }

    public function getChoicePool(Request $request)
    {
        $validated = $request->validate([
            'list_id' => 'required|string',
            'count' => 'required|integer|min:1|max:3000',
        ]);

        $level = strtoupper($validated['list_id']);
        $total = $validated['count'] * 3;

        $words = JlptWord::whereJsonContains('levels', [$level])
            ->inRandomOrder()
            ->limit($total)
            ->get(['word as text', 'meaning_ko as meaning', 'kana']);

        return response()->json($words);
    }

    public function getTodayWord(Request $request)
    {
        $user = $request->user();

        $level = $user->aiSetting->jlpt_level ?? 'N5';
        $todayKey = 'today_word_' . $user->id . '_' . $level . '_' . Carbon::now()->toDateString();

        $word = Cache::remember($todayKey, 60 * 60 * 24, function () use ($level) {
            return JlptWord::whereJsonContains('levels', $level)
                ->inRandomOrder()
                ->first(['word', 'kana', 'meaning_ko', 'levels']);
        });

        return response()->json([
            'date' => Carbon::now()->toDateString(),
            'word' => $word
        ]);
    }
}
