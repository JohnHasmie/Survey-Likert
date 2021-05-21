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

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        self::deleting(function ($response) {
            if ($response->file_path && file_exists($response->file_path)){
                @unlink($response->file_path);
            }
        });
    }

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
