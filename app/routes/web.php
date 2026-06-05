<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\KakaoAuthController;
use App\Http\Controllers\KakaoController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportCommentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingController;
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

// 카카오 OAuth (로그인·연결 공용 콜백 — 미들웨어 없음, 컨트롤러에서 분기)
Route::get('/auth/kakao',          [KakaoAuthController::class, 'redirect'])->name('auth.kakao.redirect');
Route::get('/auth/kakao/callback', [KakaoAuthController::class, 'callback'])->name('auth.kakao.callback');

Route::middleware('auth')->group(function () {
    // 보고서 CRUD
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::post('/reports/draft', [ReportController::class, 'storeDraft'])->name('reports.store-draft');
    Route::patch('/reports/{report}/draft', [ReportController::class, 'updateDraft'])->name('reports.update-draft');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('/reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::get('/reports/{report}/load', [ReportController::class, 'loadReport'])->name('reports.load');

    // 보고서 상태 변경
    Route::post('/reports/{report}/submit', [ReportController::class, 'submit'])->name('reports.submit');
    Route::post('/reports/{report}/reject', [ReportController::class, 'reject'])->name('reports.reject');

    // 일정 (팀 일정판)
    Route::get('/schedules',         [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/monthly', [ScheduleController::class, 'monthly'])->name('schedules.monthly');
    Route::post('/schedules/upsert', [ScheduleController::class, 'upsert'])->name('schedules.upsert');

    // 개인설정
    Route::get('/profile',                        [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile',                        [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/sites',                 [ProfileController::class, 'storeSite'])->name('profile.sites.store');
    Route::delete('/profile/sites/{site}',        [ProfileController::class, 'destroySite'])->name('profile.sites.destroy');
    Route::post('/profile/kakao/disconnect',      [ProfileController::class, 'kakaoDisconnect'])->name('profile.kakao.disconnect');

    // 인앱 알림
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // 요구/이슈
    Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
    Route::post('/issues', [IssueController::class, 'store'])->name('issues.store');
    Route::delete('/issues/{issue}', [IssueController::class, 'destroy'])->name('issues.destroy');

    // 보고서 코멘트
    Route::get('/reports/{report}/comments',    [ReportCommentController::class, 'index'])->name('reports.comments.index');
    Route::post('/reports/{report}/comments',   [ReportCommentController::class, 'store'])->name('reports.comments.store');
    Route::put('/report-comments/{comment}',    [ReportCommentController::class, 'update'])->name('report-comments.update');
    Route::delete('/report-comments/{comment}', [ReportCommentController::class, 'destroy'])->name('report-comments.destroy');

});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/issues/{issue}/status', [IssueController::class, 'updateStatus'])->name('issues.status');
    Route::get('/issues/export/md',       [IssueController::class, 'exportMd'])->name('issues.export.md');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/users',                              [UserController::class, 'index'])->name('admin.users.index');
    Route::post('/users',                             [UserController::class, 'store'])->name('admin.users.store');
    Route::post('/users/reorder',                     [UserController::class, 'reorder'])->name('admin.users.reorder');

    // 설정 — Webhook
    Route::get('/settings/webhook',               [SettingController::class, 'webhook'])->name('admin.settings.webhook');
    Route::post('/settings/webhook',              [SettingController::class, 'updateWebhook'])->name('admin.settings.webhook.update');
    Route::post('/settings/webhook/test',         [SettingController::class, 'testWebhook'])->name('admin.settings.webhook.test');
    Route::post('/settings/webhook/send-daily',   [SettingController::class, 'sendDailyNow'])->name('admin.settings.webhook.send-daily');
    Route::post('/settings/notify-not-submitted', [SettingController::class, 'sendNotSubmittedAlert'])->name('admin.settings.notify-not-submitted');

    // 설정 — 카카오 연동
    Route::get('/settings/kakao',             [KakaoController::class, 'show'])->name('admin.settings.kakao');
    Route::post('/settings/kakao',            [KakaoController::class, 'update'])->name('admin.settings.kakao.update');
    Route::post('/settings/kakao/send',          [KakaoController::class, 'sendAlert'])->name('admin.settings.kakao.send');
    Route::post('/settings/kakao/send-daily',    [KakaoController::class, 'sendDailyNow'])->name('admin.settings.kakao.send-daily');
    Route::post('/settings/kakao/daily-settings',[KakaoController::class, 'updateDailySettings'])->name('admin.settings.kakao.daily-settings');

    // 설정 — SMTP 메일
    Route::get('/settings/smtp',              [SettingController::class, 'smtp'])->name('admin.settings.smtp');
    Route::post('/settings/smtp',             [SettingController::class, 'updateSmtp'])->name('admin.settings.smtp.update');
    Route::post('/settings/smtp/test',        [SettingController::class, 'testSmtp'])->name('admin.settings.smtp.test');
    Route::get('/settings/mail-defaults',     [SettingController::class, 'mailDefaults'])->name('admin.settings.mail-defaults');
    Route::post('/settings/send-weekly-mail', [SettingController::class, 'sendWeeklyMail'])->name('admin.settings.send-weekly-mail');
    // 회원가입 승인 관리 (/{user} 와이드카드보다 먼저 선언)
    Route::get('/users/pending',                      [UserController::class, 'pendingIndex'])->name('admin.users.pending');
    Route::post('/users/{user}/approve',              [UserController::class, 'approve'])->name('admin.users.approve');
    Route::post('/users/{user}/reject-registration',  [UserController::class, 'rejectRegistration'])->name('admin.users.reject-registration');
    Route::put('/users/{user}',                       [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}',                    [UserController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/users/{user}/toggle-active',        [UserController::class, 'toggleActive'])->name('admin.users.toggle-active');
    Route::post('/users/{user}/toggle-hidden',        [UserController::class, 'toggleHidden'])->name('admin.users.toggle-hidden');
});

Route::middleware(['auth', 'admin'])->get('/export/weekly', [ExportController::class, 'weeklyExcel'])->name('export.weekly');
