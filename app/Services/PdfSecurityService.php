<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PdfSecurityService
{
    /**
     * Generate a unique tracking token for a given user session
     * Format: BK{LangPrefix}{BookDocPrefix}-U{UserId}-{Random}
     */
    public function generateTrackingToken(User $user, Book $book): string
    {
        $cleanDocId = preg_replace('/[^A-Z0-9]/', '', $book->document_id ?? 'DOC');
        $docPrefix = substr($cleanDocId, 0, 7);
        $random = strtoupper(Str::random(6));

        return sprintf('%s-U%d-%s', $docPrefix, $user->id, $random);
    }

    /**
     * Build the structured watermark JSON payload for client application rendering
     */
    public function buildWatermarkPayload(
        User $user,
        Book $book,
        string $trackingToken,
        ?string $appVersion = null,
        ?string $timestamp = null
    ): array {
        $timestamp = $timestamp ?? now()->format('Y-m-d H:i:s');
        $appVersion = $appVersion ?: '1.0.0';
        $userDisplayId = 'U' . $user->id;
        $lang = strtoupper($book->lang ?? 'EN');

        $displayText = sprintf(
            '%s • %s • %s • [%s] • v%s • %s',
            $user->name ?: 'Authorized User',
            $userDisplayId,
            $trackingToken,
            $lang,
            $appVersion,
            $timestamp
        );

        return [
            'user_name' => $user->name ?: 'Authorized User',
            'user_display_id' => $userDisplayId,
            'tracking_token' => $trackingToken,
            'document_id' => $book->document_id,
            'lang' => $book->lang ?? 'en',
            'app_version' => $appVersion,
            'timestamp' => $timestamp,
            'display_text' => $displayText,
            'style' => [
                'type' => 'diagonal_repeat',
                'opacity' => 0.15,
                'color' => '#888888',
                'font_size' => 14,
                'angle_degrees' => -30,
                'repeat_grid' => [
                    'rows' => 4,
                    'cols' => 2,
                ],
            ],
        ];
    }

    /**
     * Build the digital metadata dictionary to be embedded into the PDF binary
     */
    public function buildEmbeddedMetadata(
        User $user,
        Book $book,
        string $trackingToken,
        ?string $appVersion = null,
        ?string $timestamp = null
    ): array {
        $timestamp = $timestamp ?? now()->toIso8601String();
        $appVersion = $appVersion ?: '1.0.0';
        $lang = strtoupper($book->lang ?? 'EN');

        return [
            'document_id' => $book->document_id,
            'tracking_token' => $trackingToken,
            'lang' => $book->lang ?? 'en',
            'user_id' => (string) $user->id,
            'user_name' => (string) ($user->name ?: 'Authorized User'),
            'app_version' => $appVersion,
            'timestamp' => $timestamp,
            'license_statement' => sprintf(
                'Licensed to %s (UID: %d) under Token %s [%s edition] via App v%s on %s',
                $user->name ?: 'User',
                $user->id,
                $trackingToken,
                $lang,
                $appVersion,
                $timestamp
            ),
        ];
    }

    /**
     * Injects tracking metadata directly into the PDF binary's internal dictionary.
     * Operates in milliseconds using lightweight incremental / dictionary injection.
     */
    public function injectMetadata(string $pdfContent, array $metadata): string
    {
        if (empty($pdfContent)) {
            return $pdfContent;
        }

        $title = $this->escapePdfString($metadata['title'] ?? 'NegoMaster Guide');
        $author = $this->escapePdfString($metadata['author'] ?? config('app.name', 'NegoMaster'));
        $subject = $this->escapePdfString(
            $metadata['license_statement'] ??
            sprintf('Licensed to User %s (Token: %s)', $metadata['user_id'] ?? 'N/A', $metadata['tracking_token'] ?? 'N/A')
        );

        $keywords = $this->escapePdfString(sprintf(
            'DocID:%s; Token:%s; Lang:%s; UID:%s; AppVer:%s; Timestamp:%s',
            $metadata['document_id'] ?? 'N/A',
            $metadata['tracking_token'] ?? 'N/A',
            $metadata['lang'] ?? 'en',
            $metadata['user_id'] ?? 'N/A',
            $metadata['app_version'] ?? 'N/A',
            $metadata['timestamp'] ?? now()->toIso8601String()
        ));

        $creator = $this->escapePdfString(config('app.name', 'NegoMaster Security Engine'));
        $producer = $this->escapePdfString(sprintf('SecurityEngine v1.0 (Token: %s)', $metadata['tracking_token'] ?? 'N/A'));
        $creationDate = 'D:' . date('YmdHis');

        // Check if an /Info dictionary already exists
        if (preg_match('/\/Info\s+(\d+)\s+(\d+)\s+R/', $pdfContent, $matches)) {
            $objNum = $matches[1];
            $genNum = $matches[2];

            // Build new Info object
            $infoObject = sprintf(
                "%d %d obj\n<<\n/Title (%s)\n/Author (%s)\n/Subject (%s)\n/Keywords (%s)\n/Creator (%s)\n/Producer (%s)\n/CreationDate (%s)\n/ModDate (%s)\n>>\nendobj\n",
                $objNum,
                $genNum,
                $title,
                $author,
                $subject,
                $keywords,
                $creator,
                $producer,
                $creationDate,
                $creationDate
            );

            // Replace existing Info object if found, or append updated object before EOF
            $pattern = sprintf('/%d\s+%d\s+obj\s*<<.*?>>\s*endobj/s', $objNum, $genNum);
            if (preg_match($pattern, $pdfContent)) {
                return preg_replace($pattern, trim($infoObject), $pdfContent, 1);
            }
        }

        // If no existing /Info object reference was cleanly replaced, append updated Info block
        $newObjNum = 99999;
        $infoBlock = sprintf(
            "\n%d 0 obj\n<<\n/Title (%s)\n/Author (%s)\n/Subject (%s)\n/Keywords (%s)\n/Creator (%s)\n/Producer (%s)\n/CreationDate (%s)\n/ModDate (%s)\n>>\nendobj\n",
            $newObjNum,
            $title,
            $author,
            $subject,
            $keywords,
            $creator,
            $producer,
            $creationDate,
            $creationDate
        );

        if (preg_match('/trailer\s*<<([^>]*)>>/s', $pdfContent, $trailerMatches)) {
            $existingTrailer = $trailerMatches[1];
            if (!str_contains($existingTrailer, '/Info')) {
                $newTrailer = 'trailer << ' . trim($existingTrailer) . ' /Info ' . $newObjNum . " 0 R >>\n";
                $pdfContent = preg_replace('/trailer\s*<<.*?>>/s', $newTrailer, $pdfContent, 1);
            }
        }

        return $pdfContent . $infoBlock;
    }

    /**
     * Escape special PDF string characters
     */
    protected function escapePdfString(string $value): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        return strtr($value, [
            '\\' => '\\\\',
            '(' => '\\(',
            ')' => '\\)',
            "\r" => '',
            "\n" => ' ',
        ]);
    }

    /**
     * Get or create a book for a specific language (e.g. 'en' or 'fr')
     */
    public function getOrCreateBookForLanguage(string $lang = 'en'): Book
    {
        $normalizedLang = str_starts_with(strtolower(trim($lang)), 'fr') ? 'fr' : 'en';

        // Auto-prime database with all existing physical books first
        $this->syncPhysicalBooks();

        $book = Book::where('is_active', true)
            ->where('lang', $normalizedLang)
            ->latest()
            ->first();

        if ($book && File::exists($book->file_path)) {
            return $book;
        }

        // Check language-specific file in private storage
        $privateDir = storage_path('app/private/books');
        if (!File::exists($privateDir)) {
            File::makeDirectory($privateDir, 0755, true);
        }

        $specificPath = $privateDir . DIRECTORY_SEPARATOR . "book_{$normalizedLang}.pdf";
        $fallbackPath = $privateDir . DIRECTORY_SEPARATOR . 'book_en.pdf';
        $inputPath = $privateDir . DIRECTORY_SEPARATOR . 'input_pdf.pdf';

        if (File::exists($specificPath)) {
            $targetPath = $specificPath;
        } elseif (File::exists($fallbackPath)) {
            $targetPath = $fallbackPath;
        } elseif (File::exists($inputPath)) {
            $targetPath = $inputPath;
        } else {
            $targetPath = $specificPath;
            File::put($targetPath, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
        }

        $fileSize = File::exists($targetPath) ? File::size($targetPath) : 0;
        $title = $normalizedLang === 'fr' 
            ? 'Manuel Officiel NegoMaster (Français)' 
            : 'NegoMaster Official Guide (English)';

        if (!$book) {
            $book = Book::create([
                'title' => $title,
                'lang' => $normalizedLang,
                'document_id' => Book::generateDocumentId($normalizedLang),
                'file_path' => $targetPath,
                'file_size' => $fileSize,
                'is_active' => true,
            ]);
        } else {
            $book->file_path = $targetPath;
            $book->file_size = $fileSize;
            $book->save();
        }

        return $book;
    }

    /**
     * Backward-compatible default book retrieval
     */
    public function getOrCreateDefaultBook(?string $lang = 'en'): Book
    {
        return $this->getOrCreateBookForLanguage($lang ?: 'en');
    }

    /**
     * Scan storage/app/private/books/ and register all physical book files in the database
     */
    public function syncPhysicalBooks(): void
    {
        $privateDir = storage_path('app/private/books');
        if (!File::exists($privateDir)) {
            return;
        }

        $languages = ['en' => 'English', 'fr' => 'Français'];

        foreach ($languages as $code => $label) {
            $filePath = $privateDir . DIRECTORY_SEPARATOR . "book_{$code}.pdf";
            if (File::exists($filePath)) {
                $book = Book::where('lang', $code)->first();
                $title = $code === 'fr' 
                    ? 'Manuel Officiel NegoMaster (Français)' 
                    : 'NegoMaster Official Guide (English)';

                if (!$book) {
                    Book::create([
                        'title' => $title,
                        'lang' => $code,
                        'document_id' => Book::generateDocumentId($code),
                        'file_path' => $filePath,
                        'file_size' => File::size($filePath),
                        'is_active' => true,
                    ]);
                } else {
                    $book->file_path = $filePath;
                    $book->file_size = File::size($filePath);
                    $book->is_active = true;
                    $book->save();
                }
            }
        }
    }

    /**
     * Get all available book languages and their details
     */
    public function getAvailableBooksList(): array
    {
        $this->syncPhysicalBooks();

        $books = Book::where('is_active', true)->get();
        $result = [];

        foreach ($books as $book) {
            $exists = File::exists($book->file_path);
            $result[] = [
                'id' => $book->id,
                'title' => $book->title,
                'lang' => $book->lang ?? 'en',
                'document_id' => $book->document_id,
                'file_size' => $book->file_size,
                'file_size_formatted' => round(($book->file_size ?: 0) / 1024 / 1024, 2) . ' MB',
                'is_available' => $exists,
            ];
        }

        return $result;
    }
}
