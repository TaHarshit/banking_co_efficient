<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Mail\Message;

use App\Http\Controllers\Business\BusinessAuthController;

// Root URL: Business Login
Route::controller(BusinessAuthController::class)->group(function () {
    Route::GET('/', 'ShowLoginForm')->name('business.login');
    Route::POST('/login', 'Login')->name('business.login.submit');
    Route::POST('/', 'Login');
});

// Redirect /business to dashboard if authenticated, or login
Route::get('/business', function () {
    if (auth()->guard('business')->check()) {
        return redirect()->route('business.dashboard');
    }
    return redirect()->route('business.login');
});


Route::Get('/test-mail', function (\Illuminate\Http\Request $request) {
    $email = $request->query('email', 'ta.lhc5922@gmail.com');
    try {
        Mail::raw('This is a test email from Banking Co-efficient app to verify the mail configuration.', function (Message $message) use ($email) {
            $message->to($email)->subject('Test Email');
        });
        return response()->json([
            'status' => 'success',
            'message' => 'Test email sent successfully to ' . $email
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send email: ' . $e->getMessage()
        ], 500);
    }
});


// Include business routes with prefix
Route::prefix('business')->group(base_path('routes/business.php'));
