<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Survey;
use App\Models\Response;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ShowSurveys extends Component
{
    use WithPagination; 

    protected $surveys;
    public $currentSurvey;
    public $responses = [];
    public $isOpen;
    public $user;

    public function mount(?User $user) 
    {
        $this->user = $user->id ? $user : Auth::user();
    }
    
    public function render()
    {
        if ($this->user) {
            $this->surveys = Survey::with(['responses' => function($q) {
                $q->whereUserId($this->user->id);
            }, 'questions.options', 'questions.responses' => function($q) {
                $q->whereUserId($this->user->id);
            }])->orderBy('created_at', 'desc')->paginate(8);
        } else {
            $this->surveys = Survey::orderBy('created_at', 'desc')->paginate(8);
        }

        return view('livewire.show-surveys',  [
            'surveys' => $this->surveys,
        ]);
    }

    public function startSurvey($survey) {
        $questions = $survey['questions'];
        $this->currentSurvey = $survey;
        $this->responses = [];

        $this->generateResponse($questions);
        $this->openModal();
    }

    public function generateResponse($questions) {
        foreach ($questions as $question) {
            if ($question['type'] === 'checkbox') {
                $this->responses[$question['id']] =
                    $question['responses'] ? array_column($question['responses'], 'content') : [];
            } else {
                $this->responses[$question['id']] = 
                    $question['responses'] ? $question['responses'][0]['content'] : '';
            }
        }
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function store()
    {
        \DB::beginTransaction();
        try {
            $surveyId = $this->currentSurvey['id'];
            $userId = $this->user->id;

            // delete all reponse by questionId and userId
            Response::
                whereHas('question', function($q) use ($surveyId){
                    $q->whereSurveyId($surveyId);
                })
                ->whereUserId($userId)
                ->delete();

            foreach ($this->responses as $questionId => $content) {
                // content cant be null
                if ($content) {
                    if (is_array($content)) {
                        // Array checkbox
                        foreach ($content as $value) {
                            $response = new Response;
                            $response->user_id = $userId;
                            $response->question_id = $questionId;
                            $response->survey_id = $surveyId;
                            $response->content = $value;
        
                            $response->save();
                        }
                    } else {
                        $response = new Response;
                        $response->user_id = $userId;
                        $response->question_id = $questionId;
                        $response->survey_id = $surveyId;
                        $response->content = $content;
    
                        $response->save();
                    }
                }
            }

            session()->flash('message', 'Response updated successfully');
            $this->closeModal();
            $this->mount($this->user);

            \DB::commit();
        } catch (\Throwable $th) {
            $this->closeModal();
            session()->flash('message', $th . 'Survey created failed.');
            \DB::rollback();
        }
    }
}
