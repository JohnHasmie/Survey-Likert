<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'value',
        'text'
    ];

    public function questions() {
        return $this->hasMany('App\Models\Question');
    }
}
