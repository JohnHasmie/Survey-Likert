<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'single_survey',
        'total_in_right',
        'total_in_bottom',
        'average_in_right',
        'average_in_bottom'
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

    public function headers() {
        return $this->hasMany('App\Models\Header');
    }
}
