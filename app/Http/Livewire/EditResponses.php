<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

use App\Models\Survey;
use App\Models\SurveySession;
use App\Models\User;
use App\Models\Question;
use App\Models\Response;

class EditResponses extends Component
{
    use WithFileUploads; 

    public $user;
    public $survey;
    public $sessions;
    public $questions;

    public $responses = [];
    public $fileInputs = [];
    public $sessionId;
    public $isOpen;

    public function mount(User $user, Survey $survey) 
    {
        if ((auth()->user()->id !== $user->id) && !auth()->user()->isAdmin() ) abort(404);

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

        $this->resetValidation();
        $this->findFileInputs();
        $this->generateResponse($responses);
        $this->openModal();
    }

    public function findFileInputs() {
        $this->fileInputs = [];

        foreach ($this->questions as $question) {
            if ($question['type'] == 'file') 
                $this->fileInputs[$question['id']] = $question['options'];
        }
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
        $rules = [];
        $this->resetValidation();

        foreach ($this->fileInputs as $iInput => $input) {
            $nameInput = 'responses.' . $iInput;
            $extesions = array_column($input, 'value');
            $currentRule = 'file|mimes:' . implode(',', $extesions);

            $rules[$nameInput] = $currentRule;
        }

        $this->validate($rules);

        foreach ($this->fileInputs as $iInput => $input) {
            // $fileNameWithExtension = $this->responses[$iInput]->getClientOriginalName();
            // $fileNameWithoutExtension = str_replace('.', ' ', $fileNameWithExtension);
            $originalName = $this->responses[$iInput]->getClientOriginalName();
            $originalNameWithTime = time() . '_' . $originalName;

            $destinationPath = 'files/' . $this->survey->id . '/' . $iInput . '/' . $this->user->id;
            $this->responses[$iInput]->storeAs($destinationPath, $originalNameWithTime, 'public');
            $this->responses[$iInput] = $originalNameWithTime;

            $this->fileInputs[$iInput]['link'] = asset('storage/' . $destinationPath . '/' . $originalNameWithTime);
            $this->fileInputs[$iInput]['file_path'] = storage_path('app/public/' . $destinationPath . '/') . $originalNameWithTime;
        }

        \DB::beginTransaction();

        try {
            $oldResponses = Response::whereSurveySessionId($this->sessionId)
                ->whereNull('note')->get();

            // one by one for triggering event delete in model
            foreach ($oldResponses as $oldResponse) {
                $oldResponse->delete();
            }

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

                    if (isset($this->fileInputs[$questionId])) {
                        $response->link = $this->fileInputs[$questionId]['link'];
                        $response->file_path = $this->fileInputs[$questionId]['file_path'];

                    }
                    
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
