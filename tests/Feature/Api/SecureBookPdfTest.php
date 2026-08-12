<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\BookAccessLog;
use App\Models\User;
use App\Services\PdfSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SecureBookPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $headers;
    protected Book $bookEn;
    protected Book $bookFr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Harshit Test User',
            'email' => 'harshit.test@example.com',
        ]);

        $this->headers = [
            'api-key' => 'BANKING-CO-EFFICIENT',
            'platform' => 'WEB',
            'Accept' => 'application/json',
            'app-version' => '2.4.1',
        ];

        // Create sample test PDFs in private storage
        $testDir = storage_path('app/private/books');
        if (!File::exists($testDir)) {
            File::makeDirectory($testDir, 0755, true);
        }

        $dummyPdfEn = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
        $dummyPdfFr = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        $enPath = $testDir . DIRECTORY_SEPARATOR . 'book_en.pdf';
        $frPath = $testDir . DIRECTORY_SEPARATOR . 'book_fr.pdf';

        if (!File::exists($enPath)) {
            File::put($enPath, $dummyPdfEn);
        }
        if (!File::exists($frPath)) {
            File::put($frPath, $dummyPdfFr);
        }

        $this->bookEn = Book::create([
            'title' => 'NegoMaster Official Guide (English)',
            'lang' => 'en',
            'document_id' => 'BK-EN-7F82A9',
            'file_path' => $enPath,
            'file_size' => File::size($enPath),
            'is_active' => true,
        ]);

        $this->bookFr = Book::create([
            'title' => 'Manuel Officiel NegoMaster (Français)',
            'lang' => 'fr',
            'document_id' => 'BK-FR-9B31C2',
            'file_path' => $frPath,
            'file_size' => File::size($frPath),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function guests_cannot_access_secure_book_api(): void
    {
        $response = $this->postJson('/api/book/secure-access', [], $this->headers);
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_request_english_book_by_default(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/book/secure-access', [
                'lang' => 'en',
            ], $this->headers);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertTrue(isset($json['data']['stream_url']));
        $this->assertEquals('en', $json['data']['lang']);
        $this->assertEquals($this->bookEn->document_id, $json['data']['document_id']);

        // Check watermark payload
        $watermark = $json['data']['watermark'];
        $this->assertEquals('Harshit Test User', $watermark['user_name']);
        $this->assertEquals('en', $watermark['lang']);
        $this->assertEquals('2.4.1', $watermark['app_version']);
        $this->assertNotEmpty($watermark['tracking_token']);
    }

    /** @test */
    public function authenticated_user_can_request_french_book(): void
    {
        $frenchHeaders = array_merge($this->headers, ['Accept-Language' => 'fr']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/book/secure-access', [
                'lang' => 'fr',
            ], $frenchHeaders);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals('fr', $json['data']['lang']);
        $this->assertEquals($this->bookFr->document_id, $json['data']['document_id']);
        $this->assertEquals($this->bookFr->title, $json['data']['title']);

        // Verify database audit log was created with French language tag
        $this->assertDatabaseHas('book_access_logs', [
            'user_id' => $this->user->id,
            'book_id' => $this->bookFr->id,
            'document_id' => $this->bookFr->document_id,
            'lang' => 'fr',
            'app_version' => '2.4.1',
        ]);
    }

    /** @test */
    public function signed_stream_url_serves_pdf_with_security_headers_and_injected_metadata(): void
    {
        // 1. Get secure access
        $accessResponse = $this->actingAs($this->user, 'api')
            ->postJson('/api/book/secure-access', ['lang' => 'fr'], $this->headers);

        $streamUrl = $accessResponse->json('data.stream_url');
        $token = $accessResponse->json('data.watermark.tracking_token');

        // 2. Fetch the signed stream URL
        $response = $this->get($streamUrl);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Pdf-Document-Id', $this->bookFr->document_id);
        $response->assertHeader('X-Pdf-Lang', 'fr');
        $response->assertHeader('X-Pdf-Tracking-Token', $token);
        $response->assertHeader('X-Pdf-App-Version', '2.4.1');

        // Verify PDF binary contains the injected metadata
        $pdfContent = $response->getContent();
        $this->assertStringContainsString('/Keywords', $pdfContent);
        $this->assertStringContainsString($token, $pdfContent);
        $this->assertStringContainsString('UID:' . $this->user->id, $pdfContent);
        $this->assertStringContainsString('DocID:' . $this->bookFr->document_id, $pdfContent);
    }

    /** @test */
    public function tampered_or_unsigned_url_is_rejected(): void
    {
        $response = $this->get('/api/book/stream/FAKE-TOKEN-12345');
        $response->assertStatus(403);
    }

    /** @test */
    public function expired_session_token_is_rejected(): void
    {
        $token = 'BK-TEST-U' . $this->user->id . '-EXPIRED';

        BookAccessLog::create([
            'user_id' => $this->user->id,
            'book_id' => $this->bookEn->id,
            'document_id' => $this->bookEn->document_id,
            'access_token' => $token,
            'lang' => 'en',
            'app_version' => '2.4.1',
            'platform' => 'WEB',
            'expires_at' => now()->subMinute(), // Already expired
        ]);

        $signedUrl = URL::temporarySignedRoute('api.book.stream', now()->addMinutes(10), ['token' => $token]);

        $response = $this->get($signedUrl);
        $response->assertStatus(403);
        $this->assertStringContainsString('expired', $response->json('message'));
    }

    /** @test */
    public function book_details_endpoint_returns_available_languages(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/book/details?lang=fr', $this->headers);

        $response->assertStatus(200);
        $response->assertJsonPath('data.current_book.lang', 'fr');
        $this->assertIsArray($response->json('data.available_books'));
    }
}
