<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\Survey;
use App\Models\Response;
use App\Models\User;
use App\Models\SurveySession;

use Illuminate\Support\Facades\Auth;

class ShowSurveys extends Component
{
    use WithPagination; 

    protected $surveys;
    public $currentSurvey;
    public $questions = [];
    public $responses = [];
    public $isOpen;
    public $user;

    public $indexSession = 0;
    public $titleSingleSurvey = '';

    public function mount(?User $user) 
    {
        $this->user = $user->id ? $user : Auth::user();
    }
    
    public function render()
    {
        if ($this->user) {
            // Auth
            $this->surveys = Survey::with(['responses' => function($q) {
                // $q->whereUserId($this->user->id);
            }, 'questions.options', 'questions.responses' => function($q) {
                $q->whereUserId($this->user->id);
            }, 'sessions'])->orderBy('created_at', 'desc')->paginate(8);
        } else {
            // Guest
            $this->surveys = Survey::orderBy('created_at', 'desc')->paginate(8);
        }

        return view('livewire.show-surveys',  [
            'surveys' => $this->surveys,
        ]);
    }

    public function startSurvey($survey, $index = 0) {
        $this->currentSurvey = $survey;
        $this->questions = $survey['questions'];
        $this->responses = [];
        $this->titleSingleSurvey = '';

        // Edit if single session
        if ($survey['single_survey']) $this->generateResponse($index);
        $this->openModal();
    }

    public function generateResponse($index) {
        $this->indexSession = $index+1;
        $this->titleSingleSurvey = $this->currentSurvey['responses'][$index]['content'];
        $this->questions = array_slice($this->currentSurvey['questions'],1);

        $sessionId = $this->currentSurvey['sessions'][$index]['id'];
        $_responses = Response::whereSurveySessionId($sessionId)->get()->toArray();

        foreach ($this->questions as $iQuestion => $question) {
            $responses = array_values(array_filter($_responses, function ($response) use ($question) { 
                return $response['question_id'] === $question['id']; 
            }));

            if ($question['type'] === 'checkbox') {
                $this->responses[$question['id']] =
                    $responses ? array_column($responses, 'content') : [];
            } else {
                $this->responses[$question['id']] = 
                    $responses ? $responses[0]['content'] : '';
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
            $survey = $this->currentSurvey;
            $isNotFinish = $this->indexSession < count($survey['sessions']);
            $userId = $this->user->id;

            // Edit response if single session
            if ($survey['single_survey']) {
                // delete responses by survey session id
                $sessionId = $survey['sessions'][$this->indexSession-1]['id'];
                SurveySession::find($sessionId)->update(['user_id' => $userId]);

                Response::
                    whereHas('question', function($q) use ($survey){
                        $q->whereSurveyId($survey['id']);
                    })
                    ->whereSurveySessionId($sessionId)
                    ->update(['user_id' => $userId]);

                Response::
                    whereHas('question', function($q) use ($survey){
                        $q->whereSurveyId($survey['id']);
                    })
                    ->whereSurveySessionId($sessionId)
                    ->whereNull('note')
                    ->delete();
            } else {
                $newSession = new SurveySession;
                $newSession->survey_id = $survey['id'];
                $newSession->user_id = $userId;
                $newSession->save();
    
                $sessionId = $newSession->id;
            }

            foreach ($this->responses as $questionId => $content) {
                // content cant be null
                if ($content) {
                    if (is_array($content)) {
                        // Array checkbox
                        foreach ($content as $value) {
                            $response = new Response;
                            $response->user_id = $userId;
                            $response->question_id = $questionId;
                            $response->survey_id = $survey['id'];
                            $response->survey_session_id = $sessionId;
                            $response->content = $value;
        
                            $response->save();
                        }
                    } else {
                        $response = new Response;
                        $response->user_id = $userId;
                        $response->question_id = $questionId;
                        $response->survey_id = $survey['id'];
                        $response->survey_session_id = $sessionId;
                        $response->content = $content;
    
                        $response->save();
                    }
                }
            }

            \DB::commit();

            if ($survey['single_survey'] && $isNotFinish) {
                $this->closeModal();
                $this->startSurvey($survey, $this->indexSession);
            } else {
                session()->flash('message', 'Response updated successfully');
                $this->closeModal();
                $this->mount($this->user);
            }
        } catch (\Throwable $th) {
            \DB::rollback();
            $this->closeModal();
            session()->flash('message', $th . 'Survey created failed.');
        }
    }
}
