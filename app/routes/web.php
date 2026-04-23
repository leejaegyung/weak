<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => auth()->check() ? redirect('/reports') : redirect('/login'));

Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    // 보고서 CRUD
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('/reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::get('/reports/{report}/load', [ReportController::class, 'loadReport'])->name('reports.load');
    Route::get('/reports/{report}/print', [ReportController::class, 'printView'])->name('reports.print');

    // 보고서 상태 변경
    Route::post('/reports/{report}/submit', [ReportController::class, 'submit'])->name('reports.submit');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');

    // 일정 (팀 일정판)
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::post('/schedules/upsert', [ScheduleController::class, 'upsert'])->name('schedules.upsert');

});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users',                              [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users',                             [UserController::class, 'store'])->name('admin.users.store');
    Route::post('/users/reorder',                     [UserController::class, 'reorder'])->name('admin.users.reorder');
    // 회원가입 승인 관리 (/{user} 와이드카드보다 먼저 선언)
    Route::get('/users/pending',                      [UserController::class, 'pendingIndex'])->name('admin.users.pending');
    Route::post('/users/{user}/approve',              [UserController::class, 'approve'])->name('admin.users.approve');
    Route::post('/users/{user}/reject-registration',  [UserController::class, 'rejectRegistration'])->name('admin.users.reject-registration');
    Route::put('/users/{user}',                       [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}',                    [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{user}/toggle-active',        [UserController::class, 'toggleActive'])->name('admin.users.toggle-active');
});

Route::middleware(['auth', 'admin'])->get('/export/weekly', [ExportController::class, 'weeklyExcel'])->name('export.weekly');
