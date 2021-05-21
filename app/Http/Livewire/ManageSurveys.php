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
use App\Models\Header;

use App\Actions\Export\ExportSurvey;
use Excel;

use Illuminate\Support\Facades\File;

class ManageSurveys extends Component
{
    use WithPagination; 

    protected $surveys;
    public $surveyId;

    public $title;
    public $description;
    public $totalInRight;
    public $totalInBottom;
    public $averageInRight;
    public $averageInBottom;
    public $singleSurvey;
    public $customHeader;
    public $questions = [];

    public $typeOptions = [
        'text', 
        'date', 
        'year', 
        'number', 
        'radio', 
        'checkbox', 
        'textarea',
        'file', 
        'hidden'
    ];
    public $isOpen = 0;

    public $responseOptions = ['static', 'hidden'];
    public $responses = [];

    public $headers = [];

    public function render()
    {
        $this->surveys = Survey::with('questions.options')->orderBy('title', 'ASC')->paginate(8);
        $users = User::whereNotIn('email', config('settings.admin_emails'))->get()->pluck('name', 'id');
        // dd($users);
        
        return view('livewire.manage-surveys', [
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
            'alias' => '',
            'type' => 'text',
            'options' => [],
        ];
    }

    public function deleteQuestion($iQuestion)
    {
        if (isset($this->questions[$iQuestion]['id'])) {
            Question::find($this->questions[$iQuestion]['id'])->delete();
            // delete uploaed file in thi question
            File::deleteDirectory(storage_path('app/public/files/' . $this->surveyId . '/' . $this->questions[$iQuestion]['id']));
        }
        unset($this->questions[$iQuestion]);
        array_values($this->questions);
    }

    public function changeTypeQuestion($type, $iQuestion) {
        $this->questions[$iQuestion]['options'] = [];

        if (in_array($type, ['radio', 'checkbox', 'file'])) {
            $this->questions[$iQuestion]['options'][] = '';
        } else {
            $this->questions[$iQuestion]['options'] = [];
        }
    }

    public function changeSingleSurvey() {
        if ($this->singleSurvey) {
            $this->questions[0]['type'] = 'hidden';
            $this->addResponse();
        } else {
            $this->responses = [];
        }
    }

    public function changeOptionCustomHeader() {
        if ($this->customHeader) {
            $this->headers[] = [
                'title' => '',
                'columns' => '',
                'level' => '2'
            ];
        } else {
            $this->headers = [];
        }
    }

    public function changeTotalOption() {
        if ($this->totalInRight) 
            $this->averageInRight = 0;
        if ($this->totalInBottom)
            $this->averageInBottom = 0;
    }
        
    public function changeAverageOption() {
        if ($this->averageInRight)
            $this->totalInRight = 0;
        if ($this->averageInBottom)
            $this->totalInBottom = 0;
    }

    public function addHeader() {
        $this->headers[] = [
            'title' => '',
            'columns' => '',
            'level' => '2'
        ];
    }

    public function deleteHeader($iHeader) {
        unset($this->headers[$iHeader]);
        array_values($this->headers);
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
        $this->surveyId = '';
        $this->title = '';
        $this->description = '';

        $this->questions = [];

        $this->singleSurvey = 0;
        $this->totalInRight = 0;
        $this->totalInBottom = 0;
        $this->averageInRight = 0;
        $this->averageInBottom = 0;
        $this->customHeader = 0;

        $this->responses = [];
        $this->headers = [];

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
            'average_in_right' => $this->averageInRight,
            'average_in_bottom' => $this->averageInBottom,
        ];

        \DB::beginTransaction();
        try {
            $survey = Survey::updateOrCreate(['id' => $this->surveyId], $dataSurvey);

            foreach ($this->questions as $iQuestion => $question) {
                $currentQuestion = isset($question['id']) && $question['id'] ? Question::find($question['id']) : new Question;
                $currentQuestion->content = $question['content']; 
                $currentQuestion->alias = $question['alias'] ? : $question['content'];
                $currentQuestion->type = $question['type'];

                // Save or Update Question
                $currentQuestion = $survey->questions()->save($currentQuestion);

                // get First QuestionId for insert responses static
                if ($iQuestion == 0) $firstQuestionId = $currentQuestion->id;

                // Delete all options and insert one by one
                $currentQuestion->options()->delete();
                foreach ($question['options'] as $option) {
                    $currentOption = new Option;
                    $currentOption->value = $option['value'];
                    $options = $currentQuestion->options()->save($currentOption);
                }
            }

            if ($this->singleSurvey) $this->storeStaticResponses($survey->id, $firstQuestionId);
            if ($this->customHeader) $this->storeHeaders($survey);

            session()->flash('message', $this->surveyId ? 'Survey updated successfully.' : 'Survey created successfully.');

            $this->closeModal();
            $this->resetInputFields();
            $this->mount();

            \DB::commit();
        } catch (\Throwable $th) {
            $this->closeModal();
            session()->flash('message', $th);
            \DB::rollback();
        }
    }

    private function storeHeaders($survey) {
        // Header::insert($this->)
        foreach ($this->headers as $header) {
            $headerModel = isset($header['id']) ? Header::find($header['id']) : new Header($header);
            $headerModel->title = $header['title'];
            $headerModel->columns = $header['columns'];
            $headerModel->level = $header['level'];
            $survey->headers()->save($headerModel);
        }
    }

    private function storeStaticResponses($surveyId, $questionId) {
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

    public function changeQuestionContent($iQuestion) {
        $this->questions[$iQuestion]['alias'] = $this->questions[$iQuestion]['content'];
    }

    public function edit($id)
    {
        $getRandomSession = SurveySession::whereSurveyId($id)->first();
        $survey = Survey::with(['questions.options', 'headers', 'responses' => function ($q) use ($getRandomSession) {
            $q->whereNotNull('note');
            $q->whereUserId(1);
        }])
        ->findOrFail($id)
        ->toArray();

        $this->resetInputFields();
        
        $this->title = $survey['title'];
        $this->description = $survey['description'];
        $this->responses = $survey['responses'];
        $this->headers= $survey['headers'];
        $this->customHeader= !!count($survey['headers']);
        $this->singleSurvey = !!$survey['single_survey'];
        $this->totalInRight = !!$survey['total_in_right'];
        $this->totalInBottom = !!$survey['total_in_bottom'];
        $this->averageInRight = !!$survey['average_in_right'];
        $this->averageInBottom = !!$survey['average_in_bottom'];
        
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
                'alias' => $question['alias'],
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
        // delete uploaded file in deleted survey
        File::deleteDirectory(storage_path('app/public/files/' . $id));
        session()->flash('message', 'Survey deleted successfully.');
    }

    public function exportExcel($survey, $userId) {
        $fileName = $survey['title'] . ' ' . $survey['description'];

        return Excel::download(new ExportSurvey($survey['id'], $userId), $fileName . '.xlsx');
    }
}
