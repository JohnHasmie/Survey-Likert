<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description'
    ];

    public function questions() {
        return $this->hasMany('App\Models\Question');
    }

    // public function responses() {
    //     return $this->hasManyThrough(Response::class, Question::class);
    // }
    
    public function responses() {
        return $this->hasMany('App\Models\Response');
    }

    public function sessions() {
        return $this->hasMany('App\Models\SurveySession');
    }
}
