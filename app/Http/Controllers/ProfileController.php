<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        
        // 検証済みデータを取得
        $validatedArray = $request->validated();
        // アップロードされた新しい画像を取得
        $file = $request->file('profile_picture');
        // 古い画像のパスを取得
        $previousProfileFilePath = $request->user()->profile_path;

        if($file) {
            // md5でユニークなファイル名を生成
            $md5Filename = md5($file->getClientOriginalName() . $request->user()->username . Carbon::now()->toDateString()) . '.' . $file->getClientOriginalExtension();

            // 新しい画像を保存
            $profilePicturePath = $file->storeAs('/users/profiles/profile_pictures', $md5Filename, 'public');

            // プロフィールデータを更新
            $request->user()->fill([
                'username' => $validatedArray['username'],
                'email' => $validatedArray['email'],
                'description' => $validatedArray['description'],
                'profile_path' => $profilePicturePath,
            ]);

            // 古い画像の削除（新しい画像が保存された場合のみ実行）
            if ($previousProfileFilePath && $previousProfileFilePath !== $profilePicturePath) {
                Storage::disk('public')->delete($previousProfileFilePath);
            } else {
                // 新しい画像がアップロードされていない場合
                $request->user()->fill([
                    'username' => $validatedArray['username'],
                    'email' => $validatedArray['email'],
                    'description' => $validatedArray['description'],
                ]);
            }
        }

        // メールアドレスが変更されている場合，認証状態をリセット
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // データベースに保存
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
