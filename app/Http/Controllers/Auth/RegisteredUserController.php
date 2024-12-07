<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    public function store(Request $request): RedirectResponse
{
    // 入力データの検証
    // ユーザーが入力したデータ（ユーザー名、メールアドレス、説明、プロフィール画像、パスワード）をチェックします。
    // 正しい形式や値であるかを確認します。
    $request->validate([
        'username' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'description' => ['nullable', 'string', 'max:2000'],
        'profile_picture'=> ['nullable', 'image', 'mimes:jpeg,png,jpg','max:10240','extensions:jpg,png'],
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    // プロフィール画像の保存
    // ユーザーがアップロードした画像ファイルを取り出し、安全なファイル名に変換して保存します。
    $file = $request->file('profile_picture');
    if($file) {
        // ファイル名をmd5文字列に変換します。ファイル名、ユーザー名、および現在の日付文字列を使用します。
        $md5Filename = md5($file->getClientOriginalName() . $request->username . Carbon::now()->toDateString()) . '.' . $file->getClientOriginalExtension();
        $profilePicturePath = $request->file('profile_picture')->store(sprintf('/users/profiles/profile_pictures/%s',$md5Filename), 'public');
    } else {
        // 画像の登録がなければパスをnullで保存
        $profilePicturePath = null;
    }

    // ユーザーの作成
    // 検証済みのデータを使って、新しいユーザーをデータベースに登録します。パスワードは暗号化して保存します。
    $user = User::create([
        'username' => $request->username,
        'email' => $request->email,
        'description' => $request->description,
        'profile_path' => $profilePicturePath,
        'password' => Hash::make($request->password),
    ]);

    // ユーザーのログイン
    // 新しいユーザーを自動的にログインさせます。
    event(new Registered($user));
    Auth::login($user);

    // ダッシュボードへのリダイレクト
    // 登録が完了した後、ユーザーをダッシュボードページに移動させます。
    return redirect(route('dashboard', absolute: false));
}

}
