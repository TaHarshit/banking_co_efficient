<?php

namespace Tests\Unit;

use App\General\General;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordSpecialCharactersTest extends TestCase
{
    /**
     * The special characters that must be accepted in password fields.
     */
    protected array $specialCharacters = [
        '?', '.', ',', '_', '/', '(', ')', '&', '%', '!', '+', '"', '^', '='
    ];

    /**
     * Test that backend Password validation rule accepts all requested special characters.
     */
    public function test_backend_password_rule_accepts_all_required_special_characters(): void
    {
        foreach ($this->specialCharacters as $char) {
            $password = 'Secret1' . $char;
            $validator = Validator::make(
                ['password' => $password],
                ['password' => [Password::min(8)->mixedCase()->letters()->numbers()->symbols()]]
            );

            $this->assertTrue(
                $validator->passes(),
                "Password validation failed for special character: {$char}. Errors: " . implode(', ', $validator->errors()->all())
            );
        }
    }

    /**
     * Test that frontend regular expression accepts all required special characters.
     */
    public function test_frontend_regex_matches_all_required_special_characters(): void
    {
        $pattern = '/[#?!@$%^&*.,_\/()+="`~:;<>[\]{}|\\\\\'-]/';

        foreach ($this->specialCharacters as $char) {
            $this->assertSame(
                1,
                preg_match($pattern, $char),
                "Regex failed to match special character: {$char}"
            );
        }
    }

    /**
     * Test that General::stripRequest preserves password fields without altering special characters.
     */
    public function test_strip_request_preserves_password_fields(): void
    {
        $passwords = [
            'password' => 'Pass.word_123/?',
            'password_confirmation' => 'Pass.word_123/?',
            'new_password' => 'P@ss&(+"=)^!',
            'old_password' => 'Old%Pass+1',
            'name' => '<b>John Doe</b>',
        ];

        $stripped = General::stripRequest($passwords);

        $this->assertEquals('Pass.word_123/?', $stripped['password']);
        $this->assertEquals('Pass.word_123/?', $stripped['password_confirmation']);
        $this->assertEquals('P@ss&(+"=)^!', $stripped['new_password']);
        $this->assertEquals('Old%Pass+1', $stripped['old_password']);
        $this->assertEquals('John Doe', $stripped['name']);
    }
}
