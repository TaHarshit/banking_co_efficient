<?php

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

// Expose the MCP server over HTTP/SSE for API clients.
// Modify middleware as necessary for your specific authentication needs.
Route::middleware(['api', 'auth:api'])->group(function () {
    Mcp::web('/api/mcp', \App\Mcp\Servers\ApiServer::class);
});
