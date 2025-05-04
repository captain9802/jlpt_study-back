<?php

namespace App\Http\Controllers;
use App\Models\FavoriteList;
use App\Models\FavoriteGrammarList;
use App\Models\FavoriteSentenceList;
use Illuminate\Http\Request;

class FavoriteListController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $lists = FavoriteList::where('user_id', $userId)->get();
        $mapped = $lists->map(function ($list) {
            $firstWord = $list->words()->orderBy('created_at')->first();
            $preview = is_string($firstWord?->text) ? $firstWord->text : '미리보기 없음';
            return array_merge(
                $list->toArray(),
                ['preview' => $preview]
            );
        });

        return response()->json($mapped);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);

        $data['user_id'] = $request->user()->id;
        return FavoriteList::create($data);
    }

    public function update(Request $request, $id)
    {
        $list = FavoriteList::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $list->update($request->only('title', 'color'));
        return $list;
    }

    public function destroy(Request $request, $id)
    {
        $list = FavoriteList::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $list->delete();
        return response()->json(['message' => '삭제됨']);
    }

    public function getGrammarLists(Request $request)
    {
        $user = $request->user();

        $lists = FavoriteGrammarList::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $mapped = $lists->map(function ($list) {
            $first = $list->grammars()->orderBy('created_at')->first();

            return array_merge(
                $list->toArray(),
                ['preview' => $first?->grammar ?? '미리보기 없음']
            );
        });

        return response()->json($mapped);
    }


    public function storeGrammarList(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $list = FavoriteGrammarList::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'color' => $data['color'] ?? '#ffffff',
        ]);

        return response()->json($list);
    }

    public function updateGrammarList(Request $request, $id)
    {
        $list = FavoriteGrammarList::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        $list->update($data);

        return response()->json($list);
    }

    public function deleteGrammarList(Request $request, $id)
    {
        $list = FavoriteGrammarList::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $list->delete();

        return response()->json(['success' => true]);
    }

    public function getSentenceLists(Request $request)
    {
        $user = $request->user();

        $lists = FavoriteSentenceList::where('user_id', $user->id)
            ->withCount('sentences')
            ->orderByDesc('created_at')
            ->get();

        $mapped = $lists->map(function ($list) {
            $first = $list->sentences()->orderBy('created_at')->first();

            return array_merge(
                $list->toArray(),
                ['preview' => $first?->text ?? '미리보기 없음']
            );
        });

        return response()->json($mapped);
    }


    public function storeSentenceList(Request $request)
    {
        $user = $request->user();

        $list = FavoriteSentenceList::create([
            'user_id' => $user->id,
            'title' => $request->input('title', '새 문장장'),
            'color' => $request->input('color', '#eee')
        ]);

        return response()->json($list, 201);
    }

    public function updateSentenceList(Request $request, $id)
    {
        $user = $request->user();

        $list = FavoriteSentenceList::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $list->update([
            'title' => $request->input('title', $list->title),
            'color' => $request->input('color', $list->color)
        ]);

        return response()->json($list);
    }

    public function deleteSentenceList(Request $request, $id)
    {
        $user = $request->user();

        $list = FavoriteSentenceList::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $list->delete();

        return response()->json(['message' => '삭제되었습니다']);
    }
}
