<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_android_version',
        'android_build_number',
        'user_ios_version',
        'ios_build_number',
        'privacy_policy',
        'privacy_policy_fr',
        'terms_and_conditions',
        'terms_and_conditions_fr',
        'feedback_form_link',
        'feedback_form_link_fr',
        'business_policies_message',
        'policy_sections',
    ];

    protected $casts = [
        'policy_sections' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    /**
     * Get structured 3 bilingual sections for global app policies
     *
     * @return array
     */
    public function getFormattedPolicySectionsAttribute(): array
    {
        if (!empty($this->policy_sections) && is_array($this->policy_sections)) {
            $sections = $this->policy_sections;
            $normalized = [];
            for ($idx = 0; $idx < 3; $idx++) {
                $sec = $sections[$idx] ?? ['id' => $idx + 1, 'title_en' => '', 'title_fr' => '', 'points' => []];
                $titleEn = $sec['title_en'] ?? ($sec['title'] ?? '');
                $titleFr = $sec['title_fr'] ?? ($sec['title'] ?? '');
                $points = [];
                foreach ($sec['points'] ?? [] as $p) {
                    if (is_array($p)) {
                        $points[] = [
                            'en' => (string)($p['en'] ?? ($p['fr'] ?? '')),
                            'fr' => (string)($p['fr'] ?? ($p['en'] ?? '')),
                        ];
                    } else {
                        $pStr = (string)$p;
                        $points[] = [
                            'en' => $pStr,
                            'fr' => $pStr,
                        ];
                    }
                }
                $normalized[] = [
                    'id' => $sec['id'] ?? ($idx + 1),
                    'title_en' => $titleEn,
                    'title_fr' => $titleFr,
                    'points' => $points,
                ];
            }
            return $normalized;
        }

        if (!empty($this->business_policies_message)) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$this->business_policies_message)), fn($l) => $l !== ''));
            $pts = array_map(fn($line) => ['en' => $line, 'fr' => $line], $lines);
            return [
                [
                    'id' => 1,
                    'title_en' => 'Policy Guidelines',
                    'title_fr' => 'Lignes directrices sur les politiques',
                    'points' => $pts,
                ],
                [
                    'id' => 2,
                    'title_en' => '',
                    'title_fr' => '',
                    'points' => [],
                ],
                [
                    'id' => 3,
                    'title_en' => '',
                    'title_fr' => '',
                    'points' => [],
                ],
            ];
        }

        return [
            ['id' => 1, 'title_en' => '', 'title_fr' => '', 'points' => []],
            ['id' => 2, 'title_en' => '', 'title_fr' => '', 'points' => []],
            ['id' => 3, 'title_en' => '', 'title_fr' => '', 'points' => []],
        ];
    }
}
