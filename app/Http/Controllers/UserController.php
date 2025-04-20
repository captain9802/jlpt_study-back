<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        // 사용자 정보 요청
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

        // 유저 등록 or 조회
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
}
