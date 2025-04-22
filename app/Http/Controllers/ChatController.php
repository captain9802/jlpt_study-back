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

        $settings = UserAiSetting::where('user_id', $user->id)->first();
        if (!$settings) {
            return response()->json(['error' => 'AI 설정이 없습니다.'], 400);
        }
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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages,
        ]);
        file_put_contents('php://stderr', "3333333333333333333333\n");

        $aiMessage = $response->json('choices.0.message.content');
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


}
