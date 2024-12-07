<?php

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// ホームページのルートを定義します
Route::get('/', function () {
    // fakerオブジェクトを生成します
    $faker = fake();
    // 4から10のランダムな文を生成します
    $chatMessages = $faker->sentences($faker->numberBetween(4, 10));

    // 最近登録されたユーザーを取得
    $users = User::orderBy('created_at', 'desc')->take(10)->get();
    // $usersからusernameを配列で取得
    $usernames = $users->pluck('username');

    // 'welcome'ビューを表示し、生成したチャットメッセージをビューに渡します
    return view('welcome', ['chatMessages'=>$chatMessages, 'usernames'=>$usernames]);
});

Route::get('/dashboard', function () {
    $faker = fake();
    return view('dashboard', [
        'welcomeMessages' => $faker->paragraphs(5),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/about-us', function () {
    return view('about-us');
});


Route::get('/users/profile/{user}', function (User $user) {
    return view('user-profile', [
        'userInfo' => [
            'username'=>$user->username,
            'profileImageLink'=> Storage::url($user->profile_path),
            'description'=>$user->description,
        ],
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';