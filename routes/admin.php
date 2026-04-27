<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ContactUsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DistributorsController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ReceptionController;
use App\Http\Controllers\Admin\UserSubController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\PdfController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::controller(UserController::class)->group(function () {
    Route::GET('/', 'index')->name('login');
    Route::POST('/user/sign-in', 'Signin')->name('signin');
    Route::GET('/error/404', 'Error404')->name('error404');
});

// Language Switching Route
Route::get('/locale/{locale}', function ($locale) {
    $supportedLocales = array_keys(config('localization.available_locales', ['en' => []]));
    if (in_array($locale, $supportedLocales)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.switch');

Route::group(['middleware' => ['auth', 'admin.auth']], function () {

    Route::controller(UserController::class)->group(function () {
        Route::GET('/dashboard/index', 'Dashboard')->name('dashboard');
        Route::GET('/user/logout', 'Logout')->name('logout');
        Route::GET('/user/setting', [UserController::class, 'EditProfile'])->name('setting');
        Route::POST('/update-profile', [UserController::class, 'updateProfile'])->name('updateprofile');

        Route::GET('/user/index', 'ManageUsers')->name('manageusers');
        Route::GET('/user/export', 'ExportUsers')->name('exportusers');
        Route::GET('/user/create', 'CreateUser')->name('createuser');
        Route::GET('/user/update/{id}', 'UpdateUser')->name('updateuser');
        Route::GET('/user/delete/{id}', 'DeleteUser')->name('deleteuser');
        Route::POST('/user/store', 'StoreUser')->name('storeuser');
        Route::POST('user/change-status', 'ChangeStatus')->name('changeuserstatus');
    });

    Route::controller(SettingsController::class)->group(function () {
        Route::GET('/settings/{id}', 'Index')->name('addeditsetting');
        Route::POST('/settings/store', 'StoreSetting')->name('storesettings');
    });

    // Personalized Experience - Sections
    Route::controller(SectionController::class)->group(function () {
        Route::GET('/sections/index', 'ManageSections')->name('managesections');
        Route::GET('/sections/create', 'CreateSection')->name('createsection');
        Route::GET('/sections/update/{id}', 'UpdateSection')->name('updatesection');
        Route::POST('/sections/store', 'StoreSection')->name('storesection');
        Route::GET('/sections/delete/{id}', 'DeleteSection')->name('deletesection');
        Route::POST('/sections/change-status', 'ChangeStatus')->name('changesectionstatus');
        Route::POST('/sections/update-order', 'UpdateOrder')->name('updatesectionorder');
    });

    // Personalized Experience - Questions
    Route::controller(QuestionController::class)->group(function () {
        Route::GET('/questions/index/{section_id}', 'ManageQuestions')->name('managequestions');
        Route::GET('/questions/create/{section_id}', 'CreateQuestion')->name('createquestion');
        Route::GET('/questions/update/{id}', 'UpdateQuestion')->name('updatequestion');
        Route::POST('/questions/store', 'StoreQuestion')->name('storequestion');
        Route::GET('/questions/delete/{id}', 'DeleteQuestion')->name('deletequestion');
        Route::POST('/questions/change-status', 'ChangeStatus')->name('changequestionstatus');
        Route::GET('/questions/export/{section_id}', 'ExportQuestions')->name('exportquestions');
        Route::POST('/questions/import', 'ImportQuestions')->name('importquestions');
        Route::GET('/questions/import', 'ImportQuestions')->name('importquestions');
        Route::GET('/questions/download-example', 'DownloadExample')->name('downloadexample');
        Route::GET('/questions/delete-all/{section_id}', 'DeleteAllQuestions')->name('deleteallquestions');
    });

    // Business Management
    Route::controller(BusinessController::class)->group(function () {
        Route::GET('/business/index', 'ManageBusinesses')->name('managebusinesses');
        Route::GET('/business/create', 'CreateBusiness')->name('createbusiness');
        Route::GET('/business/update/{id}', 'UpdateBusiness')->name('updatebusiness');
        Route::POST('/business/store', 'StoreBusiness')->name('storebusiness');
        Route::GET('/business/delete/{id}', 'DeleteBusiness')->name('deletebusiness');
        Route::POST('/business/change-status', 'ChangeStatus')->name('changebusinessstatus');
        Route::GET('/business/resend-invitation/{id}', 'ResendInvitation')->name('resendbusinessinvitation');
    });

    // Skill Assessment - Exam Templates
    Route::controller(\App\Http\Controllers\Admin\SkillAssessmentExamTemplateController::class)->group(function () {
        Route::GET('/skill-assessment/exam-templates/index', 'ManageExamTemplates')->name('manageskillassessmentexamtemplates');
        Route::GET('/skill-assessment/exam-templates/create', 'CreateExamTemplate')->name('createskillassessmentexamtemplate');
        Route::GET('/skill-assessment/exam-templates/update/{id}', 'UpdateExamTemplate')->name('updateskillassessmentexamtemplate');
        Route::POST('/skill-assessment/exam-templates/store', 'StoreExamTemplate')->name('storeskillassessmentexamtemplate');
        Route::GET('/skill-assessment/exam-templates/delete/{id}', 'DeleteExamTemplate')->name('deleteskillassessmentexamtemplate');
        Route::POST('/skill-assessment/exam-templates/change-status', 'ChangeStatus')->name('changeskillassessmentexamtemplatestatus');
    });

    // Skill Assessment - Sections
    Route::controller(\App\Http\Controllers\Admin\SkillAssessmentSectionController::class)->group(function () {
        Route::GET('/skill-assessment/sections/index', 'ManageSkillAssessmentSections')->name('manageskillassessmentsections');
        Route::GET('/skill-assessment/sections/create', 'CreateSkillAssessmentSection')->name('createskillassessmentsection');
        Route::GET('/skill-assessment/sections/update/{id}', 'UpdateSkillAssessmentSection')->name('updateskillassessmentsection');
        Route::POST('/skill-assessment/sections/store', 'StoreSkillAssessmentSection')->name('storeskillassessmentsection');
        Route::GET('/skill-assessment/sections/delete/{id}', 'DeleteSkillAssessmentSection')->name('deleteskillassessmentsection');
        Route::POST('/skill-assessment/sections/change-status', 'ChangeStatus')->name('changeskillassessmentsectionstatus');
        Route::POST('/skill-assessment/sections/update-order', 'UpdateOrder')->name('updateskillassessmentsectionorder');
    });

    // Skill Assessment - Questions
    Route::controller(\App\Http\Controllers\Admin\SkillAssessmentQuestionController::class)->group(function () {
        Route::GET('/skill-assessment/questions/index', 'ManageSkillAssessmentQuestions')->name('manageskillassessmentquestions');
        Route::GET('/skill-assessment/questions/create', 'CreateSkillAssessmentQuestion')->name('createskillassessmentquestion');
        Route::GET('/skill-assessment/questions/update/{id}', 'UpdateSkillAssessmentQuestion')->name('updateskillassessmentquestion');
        Route::POST('/skill-assessment/questions/store', 'StoreSkillAssessmentQuestion')->name('storeskillassessmentquestion');
        Route::GET('/skill-assessment/questions/delete/{id}', 'DeleteSkillAssessmentQuestion')->name('deleteskillassessmentquestion');
        Route::POST('/skill-assessment/questions/change-status', 'ChangeStatus')->name('changeskillassessmentquestionstatus');
        Route::GET('/skill-assessment/questions/export/{section_id}', 'ExportQuestions')->name('exportskillassessmentquestions');
        Route::POST('/skill-assessment/questions/import', 'ImportQuestions')->name('importskillassessmentquestions');
        Route::GET('/skill-assessment/questions/download-example', 'DownloadExample')->name('downloadskillassessmentquestionsexample');
        Route::GET('/skill-assessment/questions/delete-all/{section_id}', 'DeleteAllQuestions')->name('deleteallskillassessmentquestions');
    });

    // Skill Assessment - Exam Results (Admin View)
    Route::controller(\App\Http\Controllers\Admin\SkillAssessmentExamController::class)->group(function () {
        Route::GET('/skill-assessment/exam-results/index', 'ManageExams')->name('manageskillassessmentexams');
        Route::GET('/skill-assessment/exam-results/view/{id}', 'ViewExam')->name('viewskillassessmentexam');
        Route::POST('/skill-assessment/exam-results/score-answer', 'ScoreAnswer')->name('scoreskillassessmentanswer');
    });

    // Contact Us Management
    Route::controller(\App\Http\Controllers\Admin\ContactUsController::class)->group(function () {
        Route::GET('/contacts/index', 'ManageContacts')->name('managecontacts');
        Route::GET('/contacts/view/{id}', 'ViewContact')->name('viewcontact');
        Route::POST('/contacts/reply', 'ReplyContact')->name('replycontact');
        Route::GET('/contacts/delete/{id}', 'DeleteContact')->name('deletecontact');
    });

    // PDF Management
    Route::controller(PdfController::class)->group(function () {
        Route::GET('/pdf/manage', 'index')->name('admin.pdf.manage');
        Route::POST('/pdf/upload', 'upload')->name('admin.pdf.upload');
    });
    // Case Study Questions
    Route::controller(\App\Http\Controllers\Admin\CaseStudyQuestionController::class)->group(function () {
        Route::get('/case-study-questions', 'index')->name('admin.case_study_questions.index');
        Route::get('/case-study-questions/create', 'create')->name('admin.case_study_questions.create');
        Route::post('/case-study-questions', 'store')->name('admin.case_study_questions.store');
        Route::post('/case-study-questions/import', 'import')->name('admin.case_study_questions.import');
        Route::get('/case-study-questions/{question}/edit', 'edit')->name('admin.case_study_questions.edit');
        Route::post('/case-study-questions/{question}', 'update')->name('admin.case_study_questions.update');
        Route::get('/case-study-questions/{question}/delete', 'destroy')->name('admin.case_study_questions.destroy');
        // Admin Activity Logs
    Route::controller(\App\Http\Controllers\Admin\AdminActivityLogController::class)->group(function () {
        Route::GET('/activity-logs', 'index')->name('admin.activity_logs.index');
    });
});

});
