<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Survey;
use App\Models\User;

use App\Actions\Export\ExportSurvey;
use Excel;

class SurveyUsers extends Component
{
    use WithPagination;

    public function render()
    {
        $surveys = Survey::with(['responses' => function ($q) {
            $q->orderBy('user_id');
            $q->groupBy('survey_id');
            $q->groupBy('user_id');
        }])->orderBy('title', 'ASC')->get();

        // dd($surveys->toArray());

        $users = User::whereNotIn('email', config('settings.admin_emails'))->get();

        return view('livewire.survey-users', [
            'surveys' => $surveys,
            'users' => $users,
        ]);
    }

    public function exportExcel($survey, $userId) {
        $fileName = $survey['title'];
        return Excel::download(new ExportSurvey($survey['id'], $userId), $fileName . '.xlsx');
    }
}
