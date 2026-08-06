<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Front & posts
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;

// Cetak & laporan
use App\Http\Controllers\PenerjemahanPdfController;
use App\Http\Controllers\EptSubmissionPdfController;
use App\Http\Controllers\CertificateController;

// Verifikasi
use App\Http\Controllers\VerificationController;

// Basic Listening (Unified Controller)
use App\Http\Controllers\BasicListeningController;
use App\Http\Controllers\BasicListeningConnectController;
use App\Http\Controllers\BasicListeningQuizController;
use App\Http\Controllers\BasicListeningHistoryController;
use App\Http\Controllers\BasicListeningProfileController;
use App\Http\Controllers\BasicListeningScheduleController;
use App\Http\Controllers\BlSurveyController;
use App\Http\Controllers\ManualCertificateController;
use App\Http\Controllers\EptOnlineController;
use App\Http\Controllers\EptOnlineAttemptController;

// Middleware
use App\Http\Middleware\CountPostView;

// Dashboard per role
use App\Http\Controllers\RoleDashboardRedirectController;
use App\Http\Controllers\PendaftarDashboardController;
use App\Http\Controllers\TutorDashboardController;
use App\Http\Controllers\DashboardNotificationController;

// Dashboard fitur lain
use App\Http\Controllers\DashboardPasswordController;
use App\Http\Controllers\Dashboard\SubmitEptScoreController;
use App\Http\Controllers\Dashboard\TranslationController;
use App\Http\Controllers\TutorMahasiswaBulkExportController;
use App\Http\Controllers\Api\WhatsAppController;

/*
|--------------------------------------------------------------------------
| Dashboard per Role (Blade, bukan Filament)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Gerbang utama setelah login / register
    Route::get('/dashboard', [RoleDashboardRedirectController::class, 'index'])
        ->name('dashboard');

    // Dashboard Pendaftar
    Route::get('/dashboard/pendaftar', [PendaftarDashboardController::class, 'index'])
        ->middleware('role:pendaftar')
        ->name('dashboard.pendaftar');

    Route::get('/dashboard/notifications/{notification}/open', [DashboardNotificationController::class, 'open'])
        ->name('dashboard.notifications.open');

    Route::post('/dashboard/notifications/read-all', [DashboardNotificationController::class, 'readAll'])
        ->name('dashboard.notifications.read-all');

    Route::post('/dashboard/notifications/read-delete', [DashboardNotificationController::class, 'destroyRead'])
        ->name('dashboard.notifications.destroy-read');

    Route::post('/dashboard/notifications/{notification}/delete', [DashboardNotificationController::class, 'destroy'])
        ->name('dashboard.notifications.destroy');

    // Dashboard Penerjemah
    Route::get('/dashboard/penerjemah', [\App\Http\Controllers\PenerjemahDashboardController::class, 'index'])
        ->middleware('role:Penerjemah')
        ->name('dashboard.penerjemah');
    
    Route::get('/dashboard/penerjemah/tugas', [\App\Http\Controllers\PenerjemahDashboardController::class, 'tugas'])
        ->middleware('role:Penerjemah')
        ->name('dashboard.penerjemah.tugas');

    Route::get('/dashboard/penerjemah/edit/{penerjemahan}', [\App\Http\Controllers\PenerjemahDashboardController::class, 'edit'])
        ->middleware('role:Penerjemah')
        ->name('dashboard.penerjemah.edit');

    Route::put('/dashboard/penerjemah/update/{penerjemahan}', [\App\Http\Controllers\PenerjemahDashboardController::class, 'update'])
        ->middleware('role:Penerjemah')
        ->name('dashboard.penerjemah.update');

    Route::post('/dashboard/penerjemah/selesai/{penerjemahan}', [\App\Http\Controllers\PenerjemahDashboardController::class, 'selesai'])
        ->middleware('role:Penerjemah')
        ->name('dashboard.penerjemah.selesai');
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('front.home');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Front pages
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])
    ->name('front.home');

Route::get('/berita', [PostController::class, 'index'])
    ->defaults('type', 'news')
    ->name('front.news');

Route::redirect('/carier', '/career', 301);

Route::get('/career', [PostController::class, 'index'])
    ->defaults('type', 'career')
    ->name('front.career');

Route::get('/berita/kategori/{newsCategory}', [PostController::class, 'newsCategory'])
    ->where('newsCategory', '[a-z0-9\-]+')
    ->name('front.news.category');

Route::get('/jadwal-ujian', [PostController::class, 'index'])
    ->defaults('type', 'schedule')
    ->name('front.schedule');

Route::get('/nilai-ujian', [PostController::class, 'index'])
    ->defaults('type', 'scores')
    ->name('front.scores');

Route::get('/career/{post:slug}', [PostController::class, 'showCareer'])
    ->middleware(CountPostView::class)
    ->name('front.career.show');

Route::get('/post/{post:slug}', [PostController::class, 'show'])
    ->middleware(CountPostView::class)
    ->name('front.post.show');

Route::view('/privacy', 'legal.privacy')
    ->name('legal.privacy');

Route::view('/terms', 'legal.terms')
    ->name('legal.terms');

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

Route::get('/login', [LoginController::class, 'showLoginForm'])
    ->middleware('guest')
    ->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('guest');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
    ->middleware('guest')
    ->name('register');
Route::post('/register', [RegisterController::class, 'register'])
    ->middleware('guest');

/*
|--------------------------------------------------------------------------
| Layanan (Service Info Pages)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\ServiceController;

Route::get('/layanan', [ServiceController::class, 'index'])
    ->name('layanan.index');

Route::get('/layanan/{service:slug}', [ServiceController::class, 'show'])
    ->name('layanan.show');

/*
|--------------------------------------------------------------------------
| Penerjemahan PDF
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/penerjemahan/{record}/pdf', [PenerjemahanPdfController::class, 'show'])
        ->whereNumber('record')
        ->name('penerjemahan.pdf');
});

Route::get('/verification/{code}/penerjemahan.pdf', [PenerjemahanPdfController::class, 'byCode'])
    ->where('code', '[A-Za-z0-9\-_]+')
    ->middleware('throttle:30,1')
    ->name('verification.penerjemahan.pdf');

/*
|--------------------------------------------------------------------------
| EPT Submission PDF (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/ept-submissions/{submission}/pdf', [EptSubmissionPdfController::class, 'show'])
        ->name('ept-submissions.pdf');
});

/*
|--------------------------------------------------------------------------
| Verification (Public + Rate Limit)
|--------------------------------------------------------------------------
*/

Route::get('/verification/{code}/ept.pdf', [EptSubmissionPdfController::class, 'byCode'])
    ->where('code', '[A-Za-z0-9\-_]+')
    ->middleware('throttle:30,1')
    ->name('verification.ept.pdf');

Route::get('/verification', [VerificationController::class, 'index'])
    ->name('verification.index');

Route::get('/verification/lookup', [VerificationController::class, 'lookup'])
    ->middleware('throttle:60,1')
    ->name('verification.lookup');

Route::get('/verification/basic-listening/manual-search', [VerificationController::class, 'searchLegacyScores'])
    ->middleware('throttle:60,1')
    ->name('verification.basic-listening.manual.search');

Route::get('/verification/{code}', [VerificationController::class, 'show'])
    ->where('code', '[A-Za-z0-9\-_]+')
    ->middleware('throttle:60,1')
    ->name('verification.show');

/*
|--------------------------------------------------------------------------
| Basic Listening – Index, Sesi, Jadwal
|--------------------------------------------------------------------------
*/

Route::get('/basic-listening', [BasicListeningController::class, 'index'])
    ->name('bl.index');

Route::get('/basic-listening/sessions/{session}', [BasicListeningController::class, 'show'])
    ->whereNumber('session')
    ->name('bl.session.show');

Route::get('/basic-listening/schedule', [BasicListeningScheduleController::class, 'index'])
    ->name('bl.schedule');

/*
|--------------------------------------------------------------------------
| Basic Listening – Connect Code (Protected + biodata lengkap)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'bl.profile'])->group(function () {
    Route::get('/basic-listening/sessions/{session}/code', [BasicListeningConnectController::class, 'showForm'])
        ->whereNumber('session')
        ->name('bl.code.form');

    Route::post('/basic-listening/sessions/{session}/code', [BasicListeningConnectController::class, 'verify'])
        ->whereNumber('session')
        ->name('bl.code.verify');
});

/*
|--------------------------------------------------------------------------
| Basic Listening – Quiz (All Types: MC, TF, FIB)
| Protected + biodata lengkap
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'bl.profile'])->group(function () {
    // Tampilkan soal (handle dynamic view FIB/MC)
    Route::get('/basic-listening/quiz/{attempt}', [BasicListeningQuizController::class, 'show'])
        ->whereNumber('attempt')
        ->name('bl.quiz.show');

    // Simpan jawaban (handle single answer & array answers)
    Route::post('/basic-listening/quiz/{attempt}/answer', [BasicListeningQuizController::class, 'answer'])
        ->whereNumber('attempt')
        ->name('bl.quiz.answer');

    // Submit akhir
    Route::post('/basic-listening/quiz/{attempt}/submit', [BasicListeningQuizController::class, 'submit'])
        ->whereNumber('attempt')
        ->name('bl.quiz.submit');

    // Force submit (timeout)
    Route::post('/bl-quiz/{attempt}/force-submit', [BasicListeningQuizController::class, 'forceSubmit'])
        ->name('bl.quiz.force-submit');
        
    // Continue attempt (redirector)
    Route::get('/basic-listening/quiz/{attempt}/continue', [BasicListeningController::class, 'continue'])
        ->whereNumber('attempt')
        ->name('bl.quiz.continue');
});

/*
|--------------------------------------------------------------------------
| Basic Listening – History (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/basic-listening/history', [BasicListeningHistoryController::class, 'index'])
        ->name('bl.history');

    Route::get('/basic-listening/history/{attempt}', [BasicListeningHistoryController::class, 'show'])
        ->whereNumber('attempt')
        ->name('bl.history.show');

    Route::get('/bl/tutor-mahasiswa/export', TutorMahasiswaBulkExportController::class)
        ->middleware('signed')
        ->name('bl.tutor-mahasiswa.export');
});

/*
|--------------------------------------------------------------------------
| EPT Online – Akses Peserta
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/ept-online', [EptOnlineController::class, 'index'])
        ->name('ept-online.index');

    Route::post('/ept-online/access', [EptOnlineController::class, 'verify'])
        ->name('ept-online.access');

    Route::get('/ept-online/attempt/{attempt:public_id}', [EptOnlineAttemptController::class, 'show'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.show');

    Route::get('/ept-online/attempt/{attempt:public_id}/audio', [EptOnlineAttemptController::class, 'audio'])
        ->middleware('signed')
        ->whereUlid('attempt')
        ->name('ept-online.attempt.audio');

    Route::post('/ept-online/attempt/{attempt:public_id}/start-section', [EptOnlineAttemptController::class, 'startSection'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.start-section');

    Route::post('/ept-online/attempt/{attempt:public_id}/part-intro', [EptOnlineAttemptController::class, 'revealListeningPart'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.part-intro');

    Route::post('/ept-online/attempt/{attempt:public_id}/section-intro', [EptOnlineAttemptController::class, 'acknowledgeSectionIntro'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.section-intro');

    Route::post('/ept-online/attempt/{attempt:public_id}/answer', [EptOnlineAttemptController::class, 'answer'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.answer');

    Route::post('/ept-online/attempt/{attempt:public_id}/progress', [EptOnlineAttemptController::class, 'progress'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.progress');

    Route::post('/ept-online/attempt/{attempt:public_id}/ping', [EptOnlineAttemptController::class, 'ping'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.ping');

    Route::post('/ept-online/attempt/{attempt:public_id}/integrity', [EptOnlineAttemptController::class, 'integrity'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.integrity');

    Route::get('/ept-online/attempt/{attempt:public_id}/finished', [EptOnlineAttemptController::class, 'finished'])
        ->whereUlid('attempt')
        ->name('ept-online.attempt.finished');
});

/*
|--------------------------------------------------------------------------
| Basic Listening – Sertifikat, Verifikasi Sertifikat, Group Number
|--------------------------------------------------------------------------
*/

// Download sertifikat (login)
Route::middleware('auth')->group(function () {
    Route::get('/bl/certificate', [CertificateController::class, 'basicListening'])
        ->name('bl.certificate.download');

    Route::post('/bl/group-number', [BasicListeningProfileController::class, 'updateGroupNumber'])
        ->name('bl.groupNumber.update');
});

// Public (tanpa login): download/preview berdasar kode verifikasi
Route::get('/verification/{code}/basic-listening.pdf', [CertificateController::class, 'basicListeningByCode'])
    ->where('code', '[A-Za-z0-9\-_]+')
    ->middleware('throttle:30,1')
    ->name('bl.certificate.bycode');

/*
|--------------------------------------------------------------------------
| Manual Certificate Download (Admin Only)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/manual-certificate/{certificate}/download', [ManualCertificateController::class, 'download'])
        ->name('manual-certificate.download');
});

// Public download by verification code (tanpa login)
Route::get('/verification/{code}/manual-certificate.pdf', [ManualCertificateController::class, 'downloadByCode'])
    ->where('code', '[A-Za-z0-9\-_]+')
    ->middleware('throttle:30,1')
    ->name('manual-certificate.public-download');

// Public download by certificate ID (untuk individual semester)
Route::get('/manual-certificate/{id}/pdf', [ManualCertificateController::class, 'downloadById'])
    ->whereNumber('id')
    ->middleware('throttle:30,1')
    ->name('manual-certificate.download-by-id');

/*
|--------------------------------------------------------------------------
| Basic Listening – Survey (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // start wizard: pilih tutor/supervisor dulu
    Route::get('/bl/survey/start', [BlSurveyController::class, 'start'])
        ->name('bl.survey.start');
    Route::post('/bl/survey/start', [BlSurveyController::class, 'startSubmit'])
        ->name('bl.survey.start.submit');

    // existing
    Route::get('/bl/survey/required', [BlSurveyController::class, 'redirectToRequired'])
        ->name('bl.survey.required');

    Route::get('/bl/survey/{survey}', [BlSurveyController::class, 'show'])
        ->whereNumber('survey')
        ->name('bl.survey.show');

    Route::post('/bl/survey/{survey}', [BlSurveyController::class, 'submit'])
        ->whereNumber('survey')
        ->name('bl.survey.submit');

    Route::get('/bl/survey/success', [BlSurveyController::class, 'success'])
        ->name('bl.survey.success');

    Route::get('/bl/survey/edit-choice', [BlSurveyController::class, 'editChoice'])
        ->name('bl.survey.edit-choice');

    Route::post('/bl/survey/edit-choice', [BlSurveyController::class, 'updateChoice'])
        ->name('bl.survey.update-choice');

    Route::get('/bl/survey/reset-choice', [BlSurveyController::class, 'resetChoice'])
        ->name('bl.survey.reset-choice');

    // Export hasil survey (PDF)
    Route::get('/bl/survey-results/export', [\App\Http\Controllers\BlSurveyResultsExportController::class, '__invoke'])
        ->name('bl.survey-results.export');
});

/*
|--------------------------------------------------------------------------
| Basic Listening – Profil, Biodata Dashboard, Password (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/bl/complete-profile', [BasicListeningProfileController::class, 'showCompleteForm'])
        ->name('bl.profile.complete');

    Route::post('/bl/complete-profile', [BasicListeningProfileController::class, 'submitCompleteForm'])
        ->name('bl.profile.complete.submit');

    Route::get('/dashboard/biodata', [BasicListeningProfileController::class, 'showDashboardBiodata'])
        ->name('dashboard.biodata');
    Route::get('/dashboard/biodata/manual-basic-listening-score', [BasicListeningProfileController::class, 'lookupLegacyScore'])
        ->middleware('throttle:30,1')
        ->name('dashboard.biodata.manual-basic-listening-score');
    Route::delete('/dashboard/biodata/photo', [BasicListeningProfileController::class, 'deletePhoto'])
        ->name('dashboard.biodata.photo.delete');

    Route::post('/dashboard/password', [DashboardPasswordController::class, 'update'])
        ->name('dashboard.password.update');

    // EPT Registration (S2 only)
    Route::get('/dashboard/ept-registration', [\App\Http\Controllers\Dashboard\EptRegistrationController::class, 'index'])
        ->name('dashboard.ept-registration.index');
    Route::post('/dashboard/ept-registration', [\App\Http\Controllers\Dashboard\EptRegistrationController::class, 'store'])
        ->name('dashboard.ept-registration.store');
    Route::get('/dashboard/ept-registration/kartu', [\App\Http\Controllers\Dashboard\EptRegistrationController::class, 'kartuPeserta'])
        ->name('dashboard.ept-registration.kartu');

    // API WhatsApp & biodata
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/api/whatsapp/update', [WhatsAppController::class, 'update'])
            ->name('api.whatsapp.update');

        Route::post('/api/whatsapp/send-otp', [WhatsAppController::class, 'sendOtp'])
            ->name('api.whatsapp.send-otp');

        Route::post('/api/whatsapp/verify-otp', [WhatsAppController::class, 'verifyOtp'])
            ->name('api.whatsapp.verify-otp');
    });

    Route::post('/api/whatsapp/save-only', [WhatsAppController::class, 'saveOnly'])
        ->middleware('throttle:10,1')
        ->name('api.whatsapp.save-only');

    Route::post('/delete-whatsapp', [WhatsAppController::class, 'delete'])
        ->name('api.whatsapp.delete');

    Route::post('/reset-biodata', [WhatsAppController::class, 'resetBiodata'])
        ->name('api.biodata.reset');

    Route::post('/dismiss-welcome', [WhatsAppController::class, 'dismissWelcome'])
        ->name('api.dismiss-welcome');
});

/*
|--------------------------------------------------------------------------
| Dashboard Pendaftar – Pengajuan Surat Rekomendasi (Protected, role:pendaftar)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pendaftar'])->group(function () {
    Route::get('/dashboard/surat-rekomendasi', [SubmitEptScoreController::class, 'index'])
        ->name('dashboard.ept');

    Route::post('/dashboard/surat-rekomendasi', [SubmitEptScoreController::class, 'store'])
        ->name('dashboard.ept.store');
});

/*
|--------------------------------------------------------------------------
| Dashboard Pendaftar – Penerjemahan Dokumen Abstrak (Protected, role:pendaftar)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pendaftar'])
    ->prefix('dashboard')
    ->group(function () {
        // daftar & riwayat
        Route::get('/translation', [TranslationController::class, 'index'])
            ->name('dashboard.translation');

        // form permintaan baru
        Route::get('/translation/create', [TranslationController::class, 'create'])
            ->name('dashboard.translation.create');

        Route::post('/translation', [TranslationController::class, 'store'])
            ->name('dashboard.translation.store');

        // perbaikan permohonan
        Route::get('/translation/{penerjemahan}/edit', [TranslationController::class, 'edit'])
            ->name('dashboard.translation.edit');

        Route::put('/translation/{penerjemahan}', [TranslationController::class, 'update'])
            ->name('dashboard.translation.update');
    });

Route::post('/bl/quiz/ping/{attempt}', [BasicListeningQuizController::class, 'ping'])
    ->middleware('auth')
    ->name('bl.quiz.ping');

/*
|--------------------------------------------------------------------------
| Admin: EPT Group - Kirim WA Individual
|--------------------------------------------------------------------------
*/
Route::post('/admin/ept-group/{group}/send-wa/{registration}', function (
    \App\Models\EptGroup $group,
    \App\Models\EptRegistration $registration
) {
    $user = $registration->user;

    if (! $user) {
        return response()->json(['success' => false, 'message' => 'User tidak ditemukan.'], 404);
    }
    
    if (!$group->jadwal) {
        return response()->json(['success' => false, 'message' => 'Grup belum memiliki jadwal.'], 400);
    }
    
    $tesNum = $registration->testNumberForGroupId((int) $group->id);

    if ($tesNum === null) {
        return response()->json(['success' => false, 'message' => 'Peserta tidak terdaftar pada grup ini.'], 400);
    }
    
    try {
        $user->notify(new \App\Notifications\EptScheduleAssignedNotification(
            testNumber: $tesNum,
            groupName: $group->name,
            scheduledAt: $group->jadwal,
            location: (string) $group->lokasi,
            dashboardUrl: route('dashboard.ept-registration.index'),
        ));

        $message = $user->whatsapp && $user->whatsapp_verified_at
            ? "Email diproses dan WA masuk antrean untuk {$user->name}"
            : "Email diproses untuk {$user->name}. WA dilewati karena nomor belum terverifikasi";

        return response()->json(['success' => true, 'message' => $message]);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
})->middleware(['auth', 'can:view_any_ept::group'])->name('admin.ept-group.send-wa-single');

/*
|--------------------------------------------------------------------------
| Admin: Crop Bukti Pembayaran Penerjemahan
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/crop-bukti/{penerjemahan}', [\App\Http\Controllers\Admin\CropBuktiController::class, 'show'])
        ->name('admin.crop-bukti.show');
    
    Route::post('/crop-bukti/{penerjemahan}', [\App\Http\Controllers\Admin\CropBuktiController::class, 'save'])
        ->name('admin.crop-bukti.save');
    
    Route::post('/crop-bukti/{penerjemahan}/restore', [\App\Http\Controllers\Admin\CropBuktiController::class, 'restore'])
        ->name('admin.crop-bukti.restore');
    
    // Export Bukti Preview & Generate
    Route::get('/export-bukti-preview', [\App\Http\Controllers\Admin\ExportBuktiController::class, 'preview'])
        ->name('admin.export-bukti.preview');
    
    Route::post('/export-bukti-generate', [\App\Http\Controllers\Admin\ExportBuktiController::class, 'generate'])
        ->name('admin.export-bukti.generate');
    
    Route::post('/export-bukti-crop-save', [\App\Http\Controllers\Admin\ExportBuktiController::class, 'cropSave'])
        ->name('admin.export-bukti.crop-save');

    // Export Bukti EPT Group Preview & Generate (ZIP batch)
    Route::get('/ept-groups/{group}/export-bukti-preview', [\App\Http\Controllers\Admin\EptGroupExportBuktiController::class, 'preview'])
        ->name('admin.ept-group-export-bukti.preview');

    Route::post('/ept-groups/export-bukti-generate', [\App\Http\Controllers\Admin\EptGroupExportBuktiController::class, 'generate'])
        ->name('admin.ept-group-export-bukti.generate');

    Route::post('/ept-groups/export-bukti-crop-save', [\App\Http\Controllers\Admin\EptGroupExportBuktiController::class, 'cropSave'])
        ->name('admin.ept-group-export-bukti.crop-save');

    Route::get('/ept-registration-export-bukti/preview', [\App\Http\Controllers\Admin\EptRegistrationExportBuktiController::class, 'preview'])
        ->name('admin.ept-registration-export-bukti.preview');

    Route::post('/ept-registration-export-bukti/generate', [\App\Http\Controllers\Admin\EptRegistrationExportBuktiController::class, 'generate'])
        ->name('admin.ept-registration-export-bukti.generate');

    Route::post('/ept-registration-export-bukti/crop-save', [\App\Http\Controllers\Admin\EptRegistrationExportBuktiController::class, 'cropSave'])
        ->name('admin.ept-registration-export-bukti.crop-save');
});
