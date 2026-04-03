<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'response_type',
        'option_id',
        'response_value',
        'rating_value',
    ];

    protected $casts = [
        'rating_value' => 'integer',
    ];

    /**
     * Response type constants
     */
    const TYPE_OPTION = 'option';
    const TYPE_TEXT = 'text';
    const TYPE_RATING = 'rating';

    /**
     * Get the user that owns the response
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the question this response is for
     */
    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the selected option (if applicable)
     */
    public function option()
    {
        return $this->belongsTo(QuestionOption::class, 'option_id');
    }

    /**
     * Get the response value based on type
     */
    public function getValue()
    {
        switch ($this->response_type) {
            case self::TYPE_OPTION:
                return $this->option ? $this->option->option_text : null;
            case self::TYPE_TEXT:
                return $this->response_value;
            case self::TYPE_RATING:
                return $this->rating_value;
            default:
                return null;
        }
    }
}
