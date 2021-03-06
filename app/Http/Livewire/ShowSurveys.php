<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

use App\Models\Survey;
use App\Models\Response;
use App\Models\User;
use App\Models\SurveySession;

use Illuminate\Support\Facades\Auth;

use App\Actions\Export\ExportSurvey;
use Excel;

class ShowSurveys extends Component
{
    use WithPagination;
    use WithFileUploads; 

    protected $surveys;
    public $currentSurvey;
    public $questions = [];
    public $responses = [];
    public $fileInputs = [];
    public $isOpen;
    public $user;

    public $prefixTitle = 'Tabel ';
    public $prefixHeaderGroup = 'Kriteria ';
    public $headerGroup = [];

    public $sessionId;
    public $indexSession = 0;
    public $number = 1;
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
                }])
                ->orderByRaw('(SUBSTR(title,7,1) * 1) ASC')
                ->orderBy('title', 'ASC')
                ->paginate(8);
        } else {
            // Guest
            $this->surveys = Survey::orderBy('created_at', 'desc')->paginate(8);
        }

        return view('livewire.show-surveys',  [
            'surveys' => $this->surveys,
        ]);
    }

    public function startSurvey($survey, $next = false) {
        $this->currentSurvey = $survey;
        $this->responses = [];
        $this->titleSession = '';
        $this->sessionId = '';
        
        $this->resetValidation();
        $this->generateQuestion();

        if (!$next) {
            $this->indexSession = 0;
            $this->number = 1;
        }

        // Edit if single session or admin
        if ($survey['single_survey'] || auth()->user()->isAdmin()) {
            $this->generateResponse();
            $this->generateTitleSession($survey['single_survey']);
        }

        $this->emit('gotoTop');
        $this->openModal();
    }

    public function generateQuestion() {
        $this->questions = [];
        $this->fileInputs = [];
        foreach ($this->currentSurvey['questions'] as $question) {
            if ($question['type'] !== 'hidden') 
                $this->questions[] = $question;
            if ($question['type'] === 'file') 
                $this->fileInputs[$question['id']] = $question['options'];
        }
    }

    public function generateResponse() {
        // $this->indexSession = $index + 1;

        if (count($this->currentSurvey['sessions'])) {
            $this->sessionId = $this->currentSurvey['sessions'][$this->indexSession]['id'];
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

    private function generateTitleSession($isSingleSurvey) {
        if ($isSingleSurvey) return $this->handleTitleSingleSurvey($this->indexSession);

        $this->countSession = count($this->currentSurvey['sessions']);
        $this->titleSession = ' ';
    }

    private function handleTitle() {
        $this->countSession = count($this->sessionResponses);
    }

    private function handleTitleSingleSurvey() {
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

        if ($responses[$this->indexSession]['note'] === 'hidden') {
            if (isset($responses[$this->indexSession + 1])) {
                $this->indexSession++;
                return $this->startSurvey($this->currentSurvey, true);
            }
            exit;
        }
            
        $this->titleSession = $responses[$this->indexSession]['content'];
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
        $rules = [];
        $survey = $this->currentSurvey;
        $userId = $this->user->id;      
        $this->resetValidation();

        foreach ($this->fileInputs as $iInput => $input) {
            $nameInput = 'responses.' . $iInput;
            $extesions = array_column($input, 'value');
            
            $currentRule = 'file|mimes:' . implode(',', $extesions);
            $rules[$nameInput] = $currentRule;
        }
        
        if (!$this->sessionId && count($rules)) $this->validate($rules);
        
        foreach ($this->fileInputs as $iInput => $input) {
            // $fileNameWithExtension = $this->responses[$iInput]->getClientOriginalName();
            // $fileNameWithoutExtension = str_replace('.', ' ', $fileNameWithExtension);
            $originalName = $this->responses[$iInput]->getClientOriginalName();
            $originalNameWithTime = time() . '_' . $originalName;

            $destinationPath = 'files/' . $survey['id'] . '/' . $iInput . '/' . $userId;
            $this->responses[$iInput]->storeAs($destinationPath, $originalNameWithTime, 'public');
            $this->responses[$iInput] = $originalNameWithTime;

            $this->fileInputs[$iInput]['link'] = asset('storage/' . $destinationPath . '/' . $originalNameWithTime);
            $this->fileInputs[$iInput]['file_path'] = storage_path('app/public/' . $destinationPath . '/') . $originalNameWithTime;
        }
        
        \DB::beginTransaction();
        try {
            $session = $this->createOrUpdateSession($survey, $userId);

            foreach ($this->responses as $questionId => $content) {
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

                    if (isset($this->fileInputs[$questionId])) {
                        $response->link = $this->fileInputs[$questionId]['link'];
                        $response->file_path = $this->fileInputs[$questionId]['file_path'];

                    }
                    
                    $response->save();
                }
            }

            $isNotFinish = $this->indexSession + 1 < $this->countSession - $this->countHiddenSession;
            
            \DB::commit();

            if ($this->sessionId && $isNotFinish) {
                $this->indexSession++;
                $this->number++;
                $this->closeModal();
                $this->startSurvey($this->currentSurvey, $this->indexSession, true);
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

            $session = $sessions[$this->indexSession];
        } else {
            $session = $this->sessionId ? SurveySession::find($this->sessionId) : new SurveySession;
            $session->survey_id = $survey['id'];
            $session->user_id = $userId;
            $session->save();
            
            $session = $session->toArray();
        }
        
        $this->sessionId = $session['id'];

        $oldResponses = Response::whereSurveySessionId($this->sessionId)
                ->whereNull('note')->get();

        // one by one for triggering event delete in model
        foreach ($oldResponses as $oldResponse) {
            $oldResponse->delete();
        }

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

    public function export(User $user, Survey $survey) {
        if ((auth()->user()->id !== $user->id) && !auth()->user()->isAdmin() ) abort(404);

        $fileName = $survey->title . ' ' . $survey->description;

        return Excel::download(new ExportSurvey($survey->id, $user->id), $fileName . '.xlsx');
    }
}
