<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Book extends Model
{
    use HasFactory;

    protected $table = 'books';

    protected $fillable = [
        'title',
        'lang',
        'document_id',
        'file_path',
        'file_size',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relationship: Access logs for this book
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(BookAccessLog::class, 'book_id');
    }

    /**
     * Scope or helper for language
     */
    public static function forLanguage(string $lang = 'en'): ?self
    {
        $normalizedLang = str_starts_with(strtolower(trim($lang)), 'fr') ? 'fr' : 'en';

        return self::where('is_active', true)
            ->where('lang', $normalizedLang)
            ->latest()
            ->first();
    }

    /**
     * Generate a unique, recognizable document ID format (e.g., BK-EN-7F82A91C)
     */
    public static function generateDocumentId(string $lang = 'en'): string
    {
        $prefix = 'BK-' . strtoupper($lang) . '-';
        do {
            $code = $prefix . strtoupper(Str::random(6));
        } while (self::where('document_id', $code)->exists());

        return $code;
    }
}
