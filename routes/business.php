<?php

use App\Http\Controllers\Business\BusinessAuthController;
use App\Http\Controllers\Business\BusinessDashboardController;
use App\Http\Controllers\Business\EmployeeController;
use App\Http\Controllers\Business\UserApprovalController;
use App\Http\Controllers\Business\SectionController;
use App\Http\Controllers\Business\QuestionController;
use App\Http\Controllers\Business\SkillAssessmentSectionController;
use App\Http\Controllers\Business\SkillAssessmentQuestionController;
use App\Http\Controllers\Business\SkillAssessmentExamTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Routes
|--------------------------------------------------------------------------
|
| Here is where you can register business routes for your application.
|
*/

// Language Switching Route for Business
Route::get('/locale/{locale}', function ($locale) {
    $supportedLocales = array_keys(config('localization.available_locales', ['en' => []]));
    if (in_array($locale, $supportedLocales)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('business.locale.switch');

// Public routes (no authentication required)
Route::controller(BusinessAuthController::class)->group(function () {
    Route::GET('/login', 'ShowLoginForm')->name('business.login');
    Route::POST('/login', 'Login')->name('business.login.submit');
    Route::GET('/forgot-password', 'ShowForgotForm')->name('business.password.request');
    Route::POST('/forgot-password', 'SendResetLink')->name('business.password.email');
    Route::GET('/setup-password/{token}', 'ShowPasswordSetupForm')->name('business.password.setup');
    Route::POST('/setup-password', 'SetupPassword')->name('business.password.setup.submit');
});

// Protected routes (authentication required)
Route::middleware(['business.auth'])->group(function () {
    Route::controller(BusinessAuthController::class)->group(function () {
        Route::GET('/logout', 'Logout')->name('business.logout');
    });

    Route::controller(BusinessDashboardController::class)->group(function () {
        Route::GET('/dashboard', 'Dashboard')->name('business.dashboard');
        Route::GET('/profile', 'Profile')->name('business.profile');
        Route::POST('/profile/update', 'UpdateProfile')->name('business.profile.update');
    });

    // Employee Management
    Route::controller(EmployeeController::class)->group(function () {
        Route::GET('/employees', 'Index')->name('business.employees');
        Route::GET('/employees/create', 'Create')->name('business.employees.create');
        Route::POST('/employees/store', 'Store')->name('business.employees.store');
        Route::GET('/employees/edit/{id}', 'Edit')->name('business.employees.edit');
        Route::POST('/employees/update/{id}', 'Update')->name('business.employees.update');
        Route::GET('/employees/delete/{id}', 'Delete')->name('business.employees.delete');
        Route::GET('/employees/import', 'ImportForm')->name('business.employees.import');
        Route::POST('/employees/import', 'Import')->name('business.employees.import.process');
    });

    // User Management & Approval
    Route::controller(UserApprovalController::class)->group(function () {
        Route::GET('/users', 'AllUsers')->name('business.users');
        Route::GET('/users/pending', 'PendingUsers')->name('business.users.pending');
        Route::GET('/users/approve/{id}', 'Approve')->name('business.users.approve');
        Route::GET('/users/reject/{id}', 'Reject')->name('business.users.reject');
        Route::GET('/users/remove/{id}', 'Remove')->name('business.users.remove');
    });

    // Personalized Experience - Section Management
    Route::controller(SectionController::class)->group(function () {
        Route::GET('/sections', 'Index')->name('business.sections');
        Route::GET('/sections/create', 'Create')->name('business.sections.create');
        Route::POST('/sections/store', 'Store')->name('business.sections.store');
        Route::GET('/sections/edit/{id}', 'Edit')->name('business.sections.edit');
        Route::GET('/sections/delete/{id}', 'Delete')->name('business.sections.delete');
        Route::POST('/sections/order', 'UpdateOrder')->name('business.sections.order');
        Route::POST('/sections/status', 'ChangeStatus')->name('business.sections.status');
    });

    // Personalized Experience - Question Management
    Route::controller(QuestionController::class)->group(function () {
        Route::GET('/sections/{sectionId}/questions', 'Index')->name('business.questions');
        Route::GET('/sections/{sectionId}/questions/create', 'Create')->name('business.questions.create');
        Route::POST('/sections/{sectionId}/questions/store', 'Store')->name('business.questions.store');
        Route::GET('/sections/{sectionId}/questions/edit/{id}', 'Edit')->name('business.questions.edit');
        Route::GET('/sections/{sectionId}/questions/delete/{id}', 'Delete')->name('business.questions.delete');
        Route::POST('/questions/order', 'UpdateOrder')->name('business.questions.order');
        Route::POST('/questions/status', 'ChangeStatus')->name('business.questions.status');
        Route::GET('/sections/{sectionId}/questions/export', 'Export')->name('business.questions.export');
        Route::POST('/sections/{sectionId}/questions/import', 'Import')->name('business.questions.import');
        Route::GET('/questions/download-example', 'DownloadExample')->name('business.questions.download-example');
        Route::GET('/sections/{sectionId}/questions/delete-all', 'DeleteAll')->name('business.questions.delete-all');
    });

    // Skill Assessment - Exam Management
    Route::controller(SkillAssessmentExamTemplateController::class)->group(function () {
        Route::GET('/skill-assessment/exams', 'ManageExamTemplates')->name('business.skill-assessment.exams');
        Route::GET('/skill-assessment/exams/create', 'CreateExamTemplate')->name('business.skill-assessment.exams.create');
        Route::POST('/skill-assessment/exams/store', 'StoreExamTemplate')->name('business.skill-assessment.exams.store');
        Route::GET('/skill-assessment/exams/edit/{id}', 'UpdateExamTemplate')->name('business.skill-assessment.exams.edit');
        Route::GET('/skill-assessment/exams/delete/{id}', 'DeleteExamTemplate')->name('business.skill-assessment.exams.delete');
        Route::POST('/skill-assessment/exams/status', 'ChangeStatus')->name('business.skill-assessment.exams.status');
    });

    // Skill Assessment - Section Management
    Route::controller(SkillAssessmentSectionController::class)->group(function () {
        Route::GET('/skill-assessment/sections', 'Index')->name('business.skill-assessment.sections');
        Route::GET('/skill-assessment/sections/create', 'Create')->name('business.skill-assessment.sections.create');
        Route::POST('/skill-assessment/sections/store', 'Store')->name('business.skill-assessment.sections.store');
        Route::GET('/skill-assessment/sections/edit/{id}', 'Edit')->name('business.skill-assessment.sections.edit');
        Route::GET('/skill-assessment/sections/delete/{id}', 'Delete')->name('business.skill-assessment.sections.delete');
        Route::POST('/skill-assessment/sections/status', 'ChangeStatus')->name('business.skill-assessment.sections.status');
    });

    // Skill Assessment - Question Management
    Route::controller(SkillAssessmentQuestionController::class)->group(function () {
        Route::GET('/skill-assessment/sections/{sectionId}/questions', 'Index')->name('business.skill-assessment.questions');
        Route::GET('/skill-assessment/sections/{sectionId}/questions/create', 'Create')->name('business.skill-assessment.questions.create');
        Route::POST('/skill-assessment/sections/{sectionId}/questions/store', 'Store')->name('business.skill-assessment.questions.store');
        Route::GET('/skill-assessment/sections/{sectionId}/questions/edit/{id}', 'Edit')->name('business.skill-assessment.questions.edit');
        Route::GET('/skill-assessment/sections/{sectionId}/questions/delete/{id}', 'Delete')->name('business.skill-assessment.questions.delete');
        Route::POST('/skill-assessment/questions/status', 'ChangeStatus')->name('business.skill-assessment.questions.status');
        Route::GET('/skill-assessment/sections/{sectionId}/questions/export', 'Export')->name('business.skill-assessment.questions.export');
        Route::POST('/skill-assessment/sections/{sectionId}/questions/import', 'Import')->name('business.skill-assessment.questions.import');
        Route::GET('/skill-assessment/questions/download-example', 'DownloadExample')->name('business.skill-assessment.questions.download-example');
        Route::GET('/skill-assessment/sections/{sectionId}/questions/delete-all', 'DeleteAll')->name('business.skill-assessment.questions.delete-all');
    });
    // Contact Us Management
    Route::controller(\App\Http\Controllers\Business\ContactUsController::class)->group(function () {
        Route::GET('/contacts', 'Index')->name('business.contacts');
        Route::GET('/contacts/view/{id}', 'ViewContact')->name('business.contacts.view');
        Route::POST('/contacts/reply/{id}', 'Reply')->name('business.contacts.reply');
        Route::GET('/contacts/delete/{id}', 'Delete')->name('business.contacts.delete');
    });
});
