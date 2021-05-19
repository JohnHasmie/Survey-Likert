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

    public $sessionId;
    public $indexSession = 0;
    public $titleSession = '';
    public $countHiddenSession = 0;
    public $countSession = 0;

    public function mount(?User $user) 
    {
        $this->user = $user->id ? $user : Auth::user();
    }
    
    public function render()
    {
        if ($this->user) {
            // Auth
            $this->surveys = Survey::with(['responses' => function($q) {
                $q->whereUserId($this->user->id);
            }, 'questions.options', 'questions.responses' => function($q) {
                $q->whereUserId($this->user->id);
            }, 'sessions' => function($q) {
                $q->whereUserId($this->user->id);
            }])->orderBy('title', 'ASC')->paginate(8);
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
        $this->titleSession = '';
        $this->sessionId = '';

        // Edit if single session or admin
        if ($survey['single_survey'] || auth()->user()->isAdmin()) {
            $this->generateResponse($index);
            $this->generateTitleSession($survey['single_survey'], $index);
        }

        $this->emit('gotoTop');
        $this->openModal();
    }

    public function generateResponse($index) {
        $this->indexSession = $index + 1;
        
        $this->questions = array_values(
            array_filter($this->currentSurvey['questions'], function($question) {
                return $question['type'] !== 'hidden';
            })
        );

        if (count($this->currentSurvey['sessions'])) {

            $this->sessionId = $this->currentSurvey['sessions'][$index]['id'];
            $this->sessionResponses = Response::whereSurveySessionId($this->sessionId)->get()->toArray();
    
            foreach ($this->questions as $iQuestion => $question) {
                $responses = array_values(array_filter($this->sessionResponses, function ($response) use ($question) { 
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

    }

    private function generateTitleSession($isSingleSurvey, $index) {
        if ($isSingleSurvey) return $this->handleTitleSingleSurvey($index);

        $this->countSession = count($this->currentSurvey['sessions']);
        $this->titleSession = ' ';
    }

    private function handleTitle() {
        $this->countSession = count($this->sessionResponses);
    }

    private function handleTitleSingleSurvey($index) {
        if (count($this->currentSurvey['responses'])) {
            $responses = array_filter($this->currentSurvey['responses'], function($response) { 
                return $response['note'] !== NULL; 
            });
        } else {
            $surveyId = $this->currentSurvey['id'];
            $getRandomSession = SurveySession::whereSurveyId($surveyId)->first();
    
            $responses = Response::whereSurveyId($surveyId)
                ->whereUserId($getRandomSession->user_id)
                ->whereNotNull('note')
                ->get()
                ->toArray();
        }
            
        $this->countSession = count($responses);
        $this->countHiddenSession = count(array_filter($responses, function($response) { 
            return $response['note'] === 'hidden'; 
        }));

        if ($responses[$index]['note'] === 'hidden') {
            if (isset($responses[$index + 1])) {
                return $this->startSurvey($this->currentSurvey, $index + 1);
            }
            exit;
        }
            
        $this->titleSession = $responses[$index]['content'];
    }

    public function openModal()
    {
        $this->isOpen = true;
        $this->emit('disableBodyScroll');
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->emit('enableBodyScroll');
    }

    public function store()
    {
        \DB::beginTransaction();
        try {
            $survey = $this->currentSurvey;
            $userId = $this->user->id;      
            $session = $this->createOrUpdateSession($survey, $userId);
            
            foreach ($this->responses as $questionId => $content) {
                // content cant be null
                // if ($content) {
                    if (is_array($content)) {
                        // Array checkbox can multiple value/content
                        foreach ($content as $value) {
                            $response = new Response;
                            $response->user_id = $userId;
                            $response->question_id = $questionId;
                            $response->survey_id = $survey['id'];
                            $response->survey_session_id = $session['id'];
                            $response->content = $value;
                            
                            $response->save();
                        }
                    } else {
                        $response = new Response;
                        $response->user_id = $userId;
                        $response->question_id = $questionId;
                        $response->survey_id = $survey['id'];
                        $response->survey_session_id = $session['id'];
                        $response->content = $content;
                        
                        $response->save();
                    }
                // }
            }

            $isNotFinish = $this->indexSession < $this->countSession - $this->countHiddenSession;
            
            \DB::commit();

            if ($this->sessionId && $isNotFinish) {
                $this->closeModal();
                $this->startSurvey($this->currentSurvey, $this->indexSession);
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

    protected function createOrUpdateSession() {
        $survey = $this->currentSurvey;
        $userId =  $this->user->id;

        if ($survey['single_survey']) {
            $sessions = count($survey['sessions']) 
                ? $survey['sessions'] 
                : $this->replicateStaticSession($survey['id'], $userId);

            $session = $sessions[$this->indexSession - 1];
        } else {
            $session = $this->sessionId ? SurveySession::find($this->sessionId) : new SurveySession;
            $session->survey_id = $survey['id'];
            $session->user_id = $userId;
            $session->save();
            
            $session = $session->toArray();
        }
        
        $this->sessionId = $session['id'];

        Response::whereSurveySessionId($this->sessionId)
            ->whereNull('note')
            ->delete();

        return $session;
    }

    protected function replicateStaticSession($surveyId, $userId) {
        $getRandomSession = SurveySession::whereSurveyId($surveyId)->first();
        $staticSessions = SurveySession::with(['responses' => function ($q) {
                $q->whereNotNull('note');
            }])
            ->whereSurveyId($surveyId)
            ->whereUserId($getRandomSession->user_id)
            ->get();
        
        foreach ($staticSessions as $session) {
            $replicateSession = $session->replicate();
            $replicateSession->user_id = $userId;
            $replicateSession->save();

            
            foreach ($replicateSession->responses as $response) {
                $replicateResponse = $response->replicate();
                $replicateResponse->user_id = $userId;
                $replicateResponse->survey_session_id = $replicateSession->id;
                $replicateResponse->save();
            }
        }

        $this->currentSurvey['sessions'] = SurveySession::whereSurveyId($surveyId)
            ->whereUserId($userId)
            ->get()
            ->toArray();

        return $this->currentSurvey['sessions'];
    }
}
