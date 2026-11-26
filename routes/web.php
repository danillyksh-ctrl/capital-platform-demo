<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth', ['mode' => 'login']))->name('login');
    Route::get('/register', fn () => Inertia::render('Auth', ['mode' => 'register']))->name('register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/forgot-password', fn () => Inertia::render('Auth', ['mode' => 'forgot']))->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (Request $r, string $token) => Inertia::render('Auth', ['mode' => 'reset', 'token' => $token, 'email' => $r->query('email')]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});
Route::middleware(['auth', 'auth.session', 'throttle:120,1'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verify-email', fn () => Inertia::render('Auth', ['mode' => 'verify']))->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', function (EmailVerificationRequest $r) {
        $r->fulfill();

        return redirect('/dashboard')->with('success', 'Email подтверждён.');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $r) {
        if (! $r->user()->hasVerifiedEmail()) {
            $r->user()->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Письмо отправлено.');
    })->middleware('throttle:3,1')->name('verification.send');
    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
        Route::get('/profile', fn () => Inertia::render('Profile'))->name('profile');
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::get('/profile/export', [ProfileController::class, 'export'])->middleware('throttle:5,1');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:5,1');
        Route::get('/admin', [AdminController::class, 'index'])->name('admin');
        Route::patch('/admin/users/{user}/block', [AdminController::class, 'toggle'])->middleware('throttle:20,1');
    });
});
