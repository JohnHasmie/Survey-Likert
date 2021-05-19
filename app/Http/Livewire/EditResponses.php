<?php

namespace App\Http\Livewire;

use Livewire\Component;

use App\Models\Survey;
use App\Models\SurveySession;
use App\Models\User;
use App\Models\Question;
use App\Models\Response;

class EditResponses extends Component
{
    public $user;
    public $survey;
    public $sessions;
    public $questions;

    public $responses = [];
    public $sessionId;
    public $isOpen;

    public function mount(User $user, Survey $survey) 
    {
        $this->user = $user;
        $this->survey = $survey;
        $this->sessions = SurveySession::whereUserId($user->id)
            ->whereSurveyId($survey->id)
            ->with('responses')
            ->get();
        $this->questions = Question::whereSurveyId($survey->id)->where('type', '!=', 'hidden')
            ->get();
    }

    public function render()
    {
        return view('livewire.edit-responses');
    }

    public function editResponse($sessionId, $responses) {
        $this->responses = [];
        $this->sessionId = $sessionId;

        $this->generateResponse($responses);
        $this->openModal();
    }

    public function generateResponse($_responses) {
        foreach ($this->questions as $iQuestion => $question) {
            $responses = array_values(array_filter($_responses, function ($response) use ($question) {
                return isset($response['question_id']) && $response['question_id'] === $question->id; 
            }));

            if ($question['type'] === 'checkbox') {
                $this->responses[$question->id] =
                    $responses ? array_column($responses, 'content') : [];
            } else {
                $this->responses[$question->id] = 
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
            Response::whereSurveySessionId($this->sessionId)
                ->whereNull('note')
                ->delete();

            foreach ($this->responses as $questionId => $content) {
                if (is_array($content)) {
                    // Array checkbox can multiple value/content
                    foreach ($content as $value) {
                        $response = new Response;
                        $response->user_id = $this->user->id;
                        $response->question_id = $questionId;
                        $response->survey_id = $this->survey->id;
                        $response->survey_session_id = $this->sessionId;
                        $response->content = $value;
                        
                        $response->save();
                    }
                } else {
                    $response = new Response;
                    $response->user_id = $this->user->id;
                    $response->question_id = $questionId;
                    $response->survey_id = $this->survey->id;
                    $response->survey_session_id = $this->sessionId;
                    $response->content = $content;
                    
                    $response->save();
                }
            }

            session()->flash('message', 'Response updated successfully');
            $this->closeModal();
            $this->mount($this->user, $this->survey);
            \DB::commit();
        } catch (\Throwable $th) {
            \DB::rollback();
            $this->closeModal();
            session()->flash('message', $th . 'Survey created failed.');
        }
    }

    public function deleteResponse($iSession)
    {
        SurveySession::find($iSession)->delete();
        $this->mount($this->user, $this->survey);
    }

}
