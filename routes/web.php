<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Profile\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/cadastro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.dashboard.index');
    })->name('dashboard');

    Route::get('/perfil', [ProfileController::class, 'show'])->name('profile');
    Route::patch('/perfil/dados', [ProfileController::class, 'updateDetails'])
        ->middleware('throttle:12,1')
        ->name('profile.details.update');
    Route::patch('/perfil/aparencia', [ProfileController::class, 'updateAppearance'])
        ->middleware('throttle:12,1')
        ->name('profile.appearance.update');
    Route::post('/perfil/imagem', [ProfileController::class, 'uploadImage'])
        ->middleware('throttle:8,1')
        ->name('profile.image.upload');

    Route::redirect('/documentacao', '/documentacao/assinaturas')->name('documentation');

    Route::get('/documentacao/{article}', function (string $article) {
        $articles = [
            'assinaturas' => 'Como funcionam as assinaturas',
            'cobranca-consumo' => 'Como funciona a cobranca por consumo',
            'processamento-pagamentos' => 'Como os pagamentos sao processados',
        ];

        abort_unless(array_key_exists($article, $articles), 404);

        return view('pages.docs.index', [
            'article' => $article,
            'articleTitle' => $articles[$article],
        ]);
    })->name('documentation.article');

    Route::get('/projetos/criar', function () {
        return view('pages.projects.create');
    })->name('projects.create');

    Route::get('/api', function () {
        return view('pages.api.index');
    })->name('api.docs');

    Route::get('/sistema/especificacoes', function () {
        return view('pages.system.specs');
    })->name('system.specs');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
