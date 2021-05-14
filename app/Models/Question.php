<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'survey_id',
        'content',
        'type',
        'options',
        'required',
        'note'
    ];

    public function options() {
        return $this->hasMany('App\Models\Option');
    }

    public function responses() {
        return $this->hasMany('App\Models\Response');
    }

    public function survey() {
        return $this->belongsTo('App\Models\Survey');
    }
}
