<?php

use Illuminate\Support\Facades\DB;

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';

// We just need to resolve the DB connection, no need to run the full HTTP kernel request if not needed.
// But we can boot the app:
$app->boot();

echo "<h1>Queue and Jobs Status Check</h1>";

// 1. Check ai_jobs
echo "<h2>1. Latest AI Jobs (ai_jobs table)</h2>";
try {
    $aiJobs = DB::table('ai_jobs')->orderBy('id', 'desc')->take(10)->get();
    if ($aiJobs->isEmpty()) {
        echo "<p>No AI jobs found.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Case ID</th><th>Job Type</th><th>Status</th><th>Attempts</th><th>Error</th><th>Created At</th></tr>";
        foreach ($aiJobs as $job) {
            echo "<tr>";
            echo "<td>{$job->id}</td>";
            echo "<td>{$job->user_id}</td>";
            echo "<td>{$job->case_id}</td>";
            echo "<td>{$job->job_type}</td>";
            echo "<td><strong>{$job->status}</strong></td>";
            echo "<td>{$job->attempts}</td>";
            echo "<td style='color: red;'>".htmlspecialchars($job->error_message ?? '')."</td>";
            echo "<td>{$job->created_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>Error checking ai_jobs table: " . $e->getMessage() . "</p>";
}

// 2. Check jobs table
echo "<h2>2. Pending Queue Jobs (jobs table)</h2>";
try {
    $jobs = DB::table('jobs')->get();
    if ($jobs->isEmpty()) {
        echo "<p>No pending jobs in the queue.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Queue</th><th>Attempts</th><th>Reserved At</th><th>Available At</th></tr>";
        foreach ($jobs as $job) {
            echo "<tr>";
            echo "<td>{$job->id}</td>";
            echo "<td>{$job->queue}</td>";
            echo "<td>{$job->attempts}</td>";
            echo "<td>{$job->reserved_at}</td>";
            echo "<td>{$job->available_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>Error checking jobs table: " . $e->getMessage() . "</p>";
}

// 3. Check failed_jobs table
echo "<h2>3. Failed Queue Jobs (failed_jobs table)</h2>";
try {
    $failedJobs = DB::table('failed_jobs')->orderBy('id', 'desc')->take(10)->get();
    if ($failedJobs->isEmpty()) {
        echo "<p>No failed jobs found in failed_jobs table.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Connection</th><th>Queue</th><th>Exception (Partial)</th><th>Failed At</th></tr>";
        foreach ($failedJobs as $fj) {
            echo "<tr>";
            echo "<td>{$fj->id}</td>";
            echo "<td>{$fj->connection}</td>";
            echo "<td>{$fj->queue}</td>";
            echo "<td style='color: red;'>".htmlspecialchars(substr($fj->exception, 0, 200))."...</td>";
            echo "<td>{$fj->failed_at}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>Error checking failed_jobs table: " . $e->getMessage() . "</p>";
}

// 4. Check Google Auth token
echo "<h2>4. Google Auth Token status</h2>";
try {
    $googleToken = DB::table('in_app_auth_tokens')->where('identifier', 'google')->first();
    if (!$googleToken) {
        echo "<p>Google Auth Token record not found in database.</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Identifier</th><th>Access Token (Partial)</th><th>Refresh Token (Partial)</th><th>Expiry Time</th><th>Current Time</th></tr>";
        echo "<tr>";
        echo "<td>{$googleToken->identifier}</td>";
        echo "<td>".htmlspecialchars(substr($googleToken->access_token ?? '', 0, 15))."...</td>";
        echo "<td>".htmlspecialchars(substr($googleToken->refresh_token ?? '', 0, 15))."...</td>";
        echo "<td>{$googleToken->token_expiry_time}</td>";
        echo "<td>".date('Y-m-d H:i:s')."</td>";
        echo "</tr>";
        echo "</table>";
    }
} catch (\Exception $e) {
    echo "<p style='color: red;'>Error checking Google token: " . $e->getMessage() . "</p>";
}
