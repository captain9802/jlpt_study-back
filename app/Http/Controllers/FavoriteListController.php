<?php

namespace App\Http\Controllers;
use App\Models\FavoriteList;
use Illuminate\Http\Request;

class FavoriteListController extends Controller
{
    public function index(Request $request)
    {
        return FavoriteList::where('user_id', $request->user()->id)->get();
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
}
