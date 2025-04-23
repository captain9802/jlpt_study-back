<?php

namespace App\Http\Controllers;

use App\Models\AiPrompt;
use App\Models\User;
use App\Models\UserAiSetting;
use App\Services\AiPromptGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function kakaoLogin(Request $request)
    {
        $accessToken = $request->input('accessToken');
        $profileImg = $request->input('profileImg');

        $response = Http::withToken($accessToken)->get('https://kapi.kakao.com/v2/user/me');

        if (!$response->successful()) {
            return response()->json([
                'error' => '카카오 사용자 정보 요청 실패',
                'details' => $response->json()
            ], 400);
        }

        $userData = $response->json();
        $email = $userData['kakao_account']['email'];
        $nickname = $userData['properties']['nickname'];

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'NickName' => $nickname,
                'profileImg' => $profileImg,
                'password' => Hash::make(Str::random(16)),
            ]
        );

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string',
            'personality' => 'nullable|string',
            'tone' => 'nullable|string',
            'voice' => 'nullable|string',
            'jlpt_level' => 'nullable|in:N1,N2,N3,N4,N5',
            'language_mode' => 'nullable|in:jp-only,ko,mix',
        ]);

        $setting = UserAiSetting::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json(['message' => '저장됨', 'data' => $setting]);
    }

    public function updateLanguageMode(Request $request)
    {
        $request->validate([
            'language_mode' => 'required|in:jp-only,ko,mix'
        ]);

        $user = $request->user();
        $setting = UserAiSetting::where('user_id', $user->id)->first();

        if (!$setting) {
            return response()->json(['error' => 'AI 설정이 존재하지 않습니다.'], 404);
        }

        $setting->language_mode = $request->input('language_mode');
        $setting->save();

        $existingPrompt = AiPrompt::where('user_id', $user->id)
            ->where('language', $setting->language_mode)
            ->first();

        if (!$existingPrompt) {
            AiPromptGenerator::generate($setting->language_mode, $setting);
        }

        return response()->json(['message' => '언어 모드가 저장되었습니다.', 'data' => $setting]);
    }

    public function getSettings(Request $request) {
        $setting = UserAiSetting::where('user_id', $request->user()->id)->first();
        return response()->json(['data' => $setting]);
    }
}
