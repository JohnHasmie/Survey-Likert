<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Option;
use App\Models\Response;
use App\Models\SurveySession;

use App\Actions\Export\ExportSurvey;
use Excel;

class ManageSurvey extends Component
{
    use WithPagination; 

    protected $surveys;
    public $surveyId;

    public $title;
    public $description;
    public $totalInRight;
    public $totalInBottom;
    public $singleSurvey;
    public $questions = [];

    public $typeOptions = ['text', 'date', 'year', 'number', 'radio', 'checkbox', 'textarea'];
    public $isOpen = 0;

    public $responseOptions = ['static'];
    public $responses = [];

    public function render()
    {
        $this->surveys = Survey::with('questions.options')->orderBy('title', 'ASC')->paginate(8);
        $users = User::whereNotIn('email', config('settings.admin_emails'))->get()->pluck('name', 'id');
        // dd($users);
        
        return view('livewire.manage-survey', [
            'surveys' => $this->surveys,
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function addQuestion()
    {
        $this->questions[] = [
            'content' => '',
            'type' => 'text',
            'options' => [],
        ];
    }

    public function deleteQuestion($iQuestion)
    {
        if (isset($this->questions[$iQuestion]['id'])) Question::find($this->questions[$iQuestion]['id'])->delete();
        unset($this->questions[$iQuestion]);
        array_values($this->questions);
    }

    public function changeTypeQuestion($type, $iQuestion) {
        $this->questions[$iQuestion]['options'] = [];

        if (in_array($type, ['radio', 'checkbox'])) {
            $this->questions[$iQuestion]['options'][] = '';
        } else {
            $this->questions[$iQuestion]['options'] = [];
        }
    }

    public function changeSingleSurvey() {
        if ($this->singleSurvey) {
            $this->questions[0]['type'] = 'text';
            $this->addResponse();
        } else {
            $this->responses = [];
        }
    }

    public function addResponse() {
        $this->responses[] = [
            'content' => '',
            'note' => '',
        ];
    }

    public function deleteResponse($iResponse) {
        unset($this->responses[$iResponse]);
        array_values($this->responses);
    }

    public function addOption($iQuestion) {
        $this->questions[$iQuestion]['options'][] = ['value' => ''];
    }

    public function deleteOption($iQuestion, $iOption) {
        unset($this->questions[$iQuestion]['options'][$iOption]);
        array_values($this->questions[$iQuestion]['options']);
    }

    private function resetInputFields(){
        $this->title = '';
        $this->description = '';
        $this->questions = [];
        $this->responses = [];
        $this->totalInRight = 0;
        $this->totalInBottom = 0;
        $this->singleSurvey = 0;
        $this->surveyId = '';
        $this->addQuestion();
    }

    public function store()
    {
        $this->validate([
            'title' => 'required|unique:surveys,title,'.$this->surveyId,
        ]);
        
        $dataSurvey = [
            'title' => $this->title,
            'description' => $this->description,
            'single_survey' => (boolean)$this->singleSurvey,
            'total_in_right' => $this->totalInRight,
            'total_in_bottom' => $this->totalInBottom,
        ];

        \DB::beginTransaction();
        try {
            $survey = Survey::updateOrCreate(['id' => $this->surveyId], $dataSurvey);
            foreach ($this->questions as $iQuestion => $question) {
                $currentQuestion = isset($question['id']) && $question['id'] ? Question::find($question['id']) : new Question;
                $currentQuestion->content = $question['content']; 
                $currentQuestion->type = $question['type'];

                // Save or Update Question
                $currentQuestion = $survey->questions()->save($currentQuestion);

                // get First QuestionId for insert responses static
                if ($iQuestion == 0) $firstQuestionId = $currentQuestion->id;

                // Delete all options
                $currentQuestion->options()->delete();
                foreach ($question['options'] as $option) {
                    // Save Option
                    $currentOption = new Option;
                    $currentOption->value = $option['value'];
                    $options = $currentQuestion->options()->save($currentOption);
                }
            }

            // Insert responses static
            if ($this->singleSurvey) $this->storeResponses($survey->id, $firstQuestionId);

            session()->flash('message', $this->surveyId ? 'Survey updated successfully.' : 'Survey created successfully.');
            $this->closeModal();
            $this->resetInputFields();
            $this->mount();

            \DB::commit();
        } catch (\Throwable $th) {
            session()->flash('message', $th);
            \DB::rollback();
        }
    }

    private function storeResponses($surveyId, $questionId) {
        foreach ($this->responses as $response) {
            $sessionModel = isset($response['survey_session_id']) ? SurveySession::find($response['survey_session_id']) : new SurveySession;
            $responseModel = isset($response['id']) ? Response::find($response['id']) : new Response;

            $sessionModel->user_id = auth()->user()->id;
            $sessionModel->survey_id = $surveyId;
            $sessionModel->save();

            $responseModel->user_id = auth()->user()->id;
            $responseModel->question_id = $questionId;
            $responseModel->survey_id = $surveyId;
            $responseModel->survey_session_id = $sessionModel->id;
            $responseModel->content = $response['content'];
            $responseModel->note = $response['note'] ? : 'static';
            $responseModel->save();
        }
    }

    public function edit($id)
    {
        $survey = Survey::with(['questions.options', 'responses' => function ($q) {
            $q->whereNotNull('note');
        }])
        ->findOrFail($id)
        ->toArray();
        
        $this->title = $survey['title'];
        $this->description = $survey['description'];
        $this->responses = $survey['responses'];
        $this->singleSurvey = !!$survey['single_survey'];
        $this->totalInRight = !!$survey['total_in_right'];
        $this->totalInBottom = !!$survey['total_in_bottom'];
        
        $this->questions = [];
        $this->surveyId = $id;

        foreach ($survey['questions'] as $question) {
            $options = [];

            if (count($question['options'])) {
                foreach ($question['options'] as $option) {
                    $options[] = [
                        'id' => $option['id'],
                        'value' => $option['value'],
                    ];
                }
            }

            $this->questions[] = [
                'id' => $question['id']  ,
                'content' => $question['content'],
                'type' => $question['type'],
                'options' => $options,
            ];
        }

        $this->openModal();
    }

    public function delete($id)
    {
        $this->surveyId = $id;
        Survey::find($id)->delete();
        session()->flash('message', 'Survey deleted successfully.');
    }

    public function exportExcel($survey, $userId) {
        $fileName = $survey['title'];
        return Excel::download(new ExportSurvey($survey['id'], $userId), $fileName . '.xlsx');
    }
}
