<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\GetApiController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationsController;
use App\Http\Controllers\Api\PdfQuestionController;
use App\Http\Controllers\Api\UserSubscriptionsController;
use App\Http\Controllers\Api\PersonalizedExperienceController;
use App\Repositories\Api\ContactUsRepository;
use App\Http\Controllers\Api\ClientCaseController;
use App\Http\Controllers\Api\CaseStudyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::GET('user/reset_password/{token}', [UserController::class, 'ResetPassword'])->name('password.reset');
Route::POST('user/update_password', [UserController::class, 'UpdatePassword'])->name('updatepassword');
Route::POST('contact_us', [\App\Http\Controllers\Api\ContactUsController::class, 'Submit'])->name('contactus');

Route::get('test-fcm-notification', function () {
    try {
        $userId = 5;
        $token = 'fxevcRa8SM-aXDy01mNxJ0:APA91bHNPDxKXtPO_CFNwVfUrTyv_Mflt8MyU3qGXjUfUrQR3GbFUlQIXws_1i_1iUeT98AQ4B2GtutmsLWg9zumQg_8iKjB4ZwBfcJlNG1xJ1CRH7BcQoo';
        
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->device_token = $token;
            $user->platform = 'WEB';
            $user->save();
        } else {
            return response()->json(['error' => 'User 138 not found'], 404);
        }

        $test = \App\General\General::sendNotificationV1(
            $userId,
            'Test Notification',
            'If you see this, notifications and token updates are working!',
            ['case_id' => 123]
        );

        return response()->json([
            'success' => true,
            'message' => 'General::sendNotificationV1() called successfully for user 138'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});



Route::middleware(['basicFilter'])->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::POST('user/signup', 'SignUp');
        Route::POST('user/login', 'Login');
        Route::GET('/setting', 'GetSetting');
        Route::POST('user/forgot_password', 'ForgotPassword');
        Route::POST('email/subscribe', 'subscribe');
        Route::POST('email/unsubscribe', 'unsubscribe');
    });

    Route::controller(ContactUsController::class)->group(function () {
        Route::POST('contact_us/submit', 'Submit');
    });

    Route::middleware(['auth:api'])->group(function () {
        Route::controller(UserController::class)->group(function () {
            Route::POST('user/get_profile', 'GetProfile');
            Route::POST('user/get_status', 'GetStatus');
            Route::POST('user/complete_profile', 'CompleteProfile');
            Route::POST('user/profile_image_update', 'UpdateProfileImages');
            Route::POST('user/change_password', 'ChangePassword');
            Route::POST('user/delete_account', 'DeleteAccount');
            Route::POST('user/logout', 'Logout');
        });

        Route::controller(NotificationsController::class)->group(function () {
            Route::GET('notifications/get', 'GetNotifications');
        });

        // Personalized Experience - All endpoints require authentication
        Route::controller(PersonalizedExperienceController::class)->group(function () {
            Route::GET('experience/sections', 'GetSections');
            Route::POST('experience/submit', 'SubmitResponses');
            Route::GET('experience/responses', 'GetResponses');
            Route::GET('experience/status', 'GetStatus');
        });

        // Skill Assessment - All endpoints require authentication
        Route::controller(\App\Http\Controllers\Api\SkillAssessmentController::class)->group(function () {
            Route::GET('skill-assessment/exams', 'getExams');
            Route::GET('skill-assessment/sections/{exam_template_id}', 'getSections');
            Route::POST('skill-assessment/start/{exam_template_id}', 'startExam');
            Route::POST('skill-assessment/submit/{exam_id}', 'submitExam');
            Route::GET('skill-assessment/result/{exam_id}', 'getResult');
            Route::GET('skill-assessment/history', 'getHistory');
        });

        Route::controller(PdfQuestionController::class)->group(function () {
            Route::POST('pdf/ask', 'ask');
            Route::GET('pdf/history', 'getHistory');
            Route::GET('pdf/status', 'status');
            
            // Chat Sessions Management
            Route::POST('pdf/sessions/list', 'getSessions');
            Route::POST('pdf/sessions/create', 'createSession');
            Route::POST('pdf/sessions/rename', 'renameSession');
            Route::POST('pdf/sessions/delete', 'deleteSession');
        });

        // Client Case Management
        Route::controller(ClientCaseController::class)->group(function () {
            Route::post('client-cases', 'store');
            Route::get('client-cases', 'index');
            Route::get('client-cases/{id}', 'show');
            Route::post('delete-client-cases/{id}', 'destroy');
            Route::get('client-cases/{id}/export-plan', 'exportPlan');
            Route::get('case-study-sections', 'caseStudySections');
        });

        // AI Case Analysis and Plan Generation (async queue-based)
        // POST endpoints return a job_id immediately; poll GET ai/job-status/{job_id} for results.
        Route::controller(ClientCaseController::class)->group(function () {
            Route::post('ai/analyze-case', 'analyzeCase');
            Route::post('ai/generate-plan', 'generatePlan');
            Route::get('ai/job-status/{job_id}', 'getAiJobStatus');
            Route::post('ai/rate-plan', 'ratePlan');
        });

        Route::controller(UserSubscriptionsController::class)->group(function () {
            Route::POST('transactions/init-payment', 'InitPayment'); 
            Route::POST('transactions/complete-payment', 'CompletePayment'); 
            Route::POST('transactions/get_user_current_plan', 'GetUserCurrentPlan'); 
        });

    });
});
