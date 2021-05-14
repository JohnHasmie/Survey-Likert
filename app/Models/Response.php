<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'content',
        'note',
    ];

    public function question() {
        return $this->belongsTo('App\Models\Question');
    }

    public function user() {
        return $this->belongsTo('App\Models\User');
    }

    public function survey() {
        return $this->belongsTo('App\Models\Survey');
    }
}
