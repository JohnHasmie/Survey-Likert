<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;
    
    public function render()
    {
        $users = User::with(['responses' => function ($q) {
            $q->groupBy('user_id');
            $q->groupBy('survey_id');
        }])
        ->whereNotIn('email', config('settings.admin_emails'))
        ->paginate(8);

        $countSurvey = Survey::count();

        return view('livewire.user-list', [
            'users' => $users,
            'countSurvey' => $countSurvey
        ]);
    }
}
