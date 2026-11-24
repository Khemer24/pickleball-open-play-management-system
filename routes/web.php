<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OpenPlayController;
use App\Http\Controllers\OrganizerEventController;
use App\Http\Controllers\PlayersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));
Route::get('/live/{token}', [OpenPlayController::class, 'live'])->name('open-plays.live');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [AuthController::class, 'verificationNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/open-plays', [OpenPlayController::class, 'index'])->name('open-plays.index');
    Route::get('/open-plays/{session}', [OpenPlayController::class, 'show'])->name('open-plays.show');
    Route::get('/players', [PlayersController::class, 'index'])->name('players.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('manage-open-plays')->group(function () {
        Route::get('/open-plays/create', [OpenPlayController::class, 'create'])->name('open-plays.create');
        Route::post('/open-plays', [OpenPlayController::class, 'store'])->name('open-plays.store');
        Route::get('/open-plays/{session}/manage', [OpenPlayController::class, 'manage'])->name('open-plays.manage');
        Route::put('/open-plays/{session}', [OpenPlayController::class, 'update'])->name('open-plays.update');
        Route::post('/open-plays/{session}/players', [OpenPlayController::class, 'addPlayer'])->name('open-plays.players.store');
        Route::post('/open-plays/{session}/players/{player}/check-in', [OpenPlayController::class, 'checkIn'])->name('open-plays.check-in');
        Route::post('/open-plays/{session}/matches/{match}/score', [OpenPlayController::class, 'score'])->name('open-plays.score');
        Route::post('/open-plays/{session}/finalize', [OpenPlayController::class, 'finalize'])->name('open-plays.finalize');
        Route::post('/player-groups', [AdminController::class, 'storeGroup'])->name('player-groups.store');
    });

    Route::get('/my-events', [OrganizerEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [OrganizerEventController::class, 'create'])->name('events.create');
    Route::post('/events', [OrganizerEventController::class, 'store'])->name('events.store');
    Route::get('/events/{session}', [OrganizerEventController::class, 'workspace'])->name('events.show');
    Route::put('/events/{session}', [OrganizerEventController::class, 'update'])->name('events.update');
    Route::post('/events/{session}/managers', [OrganizerEventController::class, 'assignManagers'])->name('events.managers.update');
    Route::post('/events/{session}/players', [OrganizerEventController::class, 'addPlayer'])->name('events.players.store');
    Route::patch('/events/{session}/players/{player}/check-in', [OrganizerEventController::class, 'setCheckIn'])->name('events.players.check-in');
    Route::patch('/events/{session}/players/{player}/fee', [OrganizerEventController::class, 'setFeeStatus'])->name('events.players.fee');
    Route::post('/events/{session}/courts', [OrganizerEventController::class, 'addCourt'])->name('events.courts.store');
    Route::post('/events/{session}/scheduling', [OrganizerEventController::class, 'toggleScheduling'])->name('events.scheduling.toggle');
    Route::post('/events/{session}/matches/{match}/score', [OrganizerEventController::class, 'score'])->name('events.matches.score');
    Route::put('/events/{session}/matches/{match}/score', [OrganizerEventController::class, 'correctScore'])->name('events.matches.correct');
    Route::post('/events/{session}/finalize', [OrganizerEventController::class, 'finalize'])->name('events.finalize');

    Route::middleware('manage-users')->group(function () {
        Route::get('/admin/managers', [AdminController::class, 'managers'])->name('admin.managers');
        Route::post('/admin/managers/{user}/approve', [AdminController::class, 'approve'])->name('admin.managers.approve');
    });
});
