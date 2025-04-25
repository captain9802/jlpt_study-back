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

    public function store(Request $request)
    {
        try {
            $data = $request->all();

            // 디버깅 로그 추가
            file_put_contents('php://stderr', "[🔍 입력된 데이터]\n" . print_r($data, true));

            $favorite = Favorite::create($data);

            return response()->json(['message' => '단어 저장 완료', 'data' => $favorite], 201);
        } catch (\Exception $e) {
            // 예외 처리 및 오류 로그 출력
            file_put_contents('php://stderr', "[❌ 저장 실패] " . $e->getMessage());
            return response()->json(['message' => '서버 오류', 'error' => $e->getMessage()], 500);
        }
    }




    public function destroy($id)
    {
        $word = Favorite::findOrFail($id);
        $word->delete();
        return response()->json(['message' => '단어 삭제됨']);
    }
}
