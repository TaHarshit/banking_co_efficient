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
        });

        // Client Case Management
        Route::controller(ClientCaseController::class)->group(function () {
            Route::post('client-cases', 'store');
            Route::get('client-cases', 'index');
            Route::get('client-cases/{id}', 'show');
            Route::get('case-study-sections', 'caseStudySections');
        });
    });
});
