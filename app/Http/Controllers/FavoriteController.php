<?php

namespace App\Http\Controllers;
use App\Models\Favorite;
use Illuminate\Http\Request;

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

}
