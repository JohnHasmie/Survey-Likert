<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Option;
use Livewire\WithPagination;
use App\Actions\Export\ExportSurvey;
use Excel;

class ManageSurvey extends Component
{
    use WithPagination; 

    protected $surveys;
    public $surveyId;

    public $title;
    public $description;
    public $questions = [];

    public $typeOptions = [];
    public $isOpen = 0;

    // public function mount() 
    // {
    // }
    
    public function render()
    {
        $this->surveys = Survey::with('questions.options')->orderBy('created_at', 'desc')->paginate(8);
        $this->typeOptions = ['text', 'date', 'year', 'number', 'radio', 'checkbox', 'textarea'];
        
        return view('livewire.manage-survey', [
            'surveys' => $this->surveys,
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
        $this->questions[] = [
            'content' => '',
            'type' => 'text',
            'options' => [],
        ];
    }

    public function store()
    {
        $this->validate([
            'title' => 'required|unique:surveys,title,'.$this->surveyId,
        ]);
        $dataSurvey = [
            'title' => $this->title,
            'description' => $this->description,
        ];
        // foreach ($this->questions as $iQuestion => $question) {
        //     $this->questions[$iQuestion]['options'] = implode('|', $question['options']);
        // }

        \DB::beginTransaction();
        try {
            $survey = Survey::updateOrCreate(['id' => $this->surveyId], $dataSurvey);
            foreach ($this->questions as $question) {
                $currentQuestion = isset($question['id']) && $question['id'] ? Question::find($question['id']) : new Question;
                $currentQuestion->content = $question['content']; 
                $currentQuestion->type = $question['type'];

                // Save or Update Question
                $currentQuestion = $survey->questions()->save($currentQuestion);
                // Delete all options
                $currentQuestion->options()->delete();
                foreach ($question['options'] as $option) {
                    // Save Option
                    $currentOption = new Option;
                    $currentOption->value = $option['value'];
                    $options = $currentQuestion->options()->save($currentOption);
                }
            }
            // $questions = $survey->questions()->createMany($this->questions);
            session()->flash('message', $this->surveyId ? 'Survey updated successfully.' : 'Survey created successfully.');
            $this->closeModal();
            $this->resetInputFields();
            $this->mount();

            \DB::commit();
        } catch (\Throwable $th) {
            session()->flash('message', $this->surveyId ? $th : 'Survey created failed.');
            \DB::rollback();
        }
    }

    public function edit($id)
    {
        $survey = Survey::with('questions.options')->findOrFail($id);
        $survey = $survey->toArray();
        $this->surveyId = $id;
        $this->title = $survey['title'];
        $this->description = $survey['description'];
        $this->questions = [];

        foreach ($survey['questions'] as $question) {
            $options = [];
            // dd($question);
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

    public function exportExcel($survey) {
        $fileName = $survey['title'];
        return Excel::download(new ExportSurvey($survey['id']), $fileName . '.xlsx');
    }
}
