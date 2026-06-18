<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'title'
    ];

    /**
     * Get the user that owns the chat session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages (chat history) for the session.
     */
    public function messages()
    {
        return $this->hasMany(ChatHistory::class, 'chat_session_id');
    }
}
