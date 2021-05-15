<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;
use App\Models\Response;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;
    
    public function render()
    {
        // $userAnswers = DB::table('users')
            // ->select('users.*', 'surveys.*')
            // ->join('responses', 'users.id', '=', 'responses.user_id')
            // ->join('questions', 'responses.question_id', '=', 'questions.id')
            // ->leftJoin('surveys', 'responses.id', '=', 'surveys.id')
            // ->groupBy('surveys.id')
            // ->groupBy('users.id')
            // ->get();
            // ->groupBy('name');
        // $users = User::with('responses')->get();
        $users = User::with(['responses' => function ($q) {
            $q->groupBy('user_id');
            $q->groupBy('survey_id');
        }])
        ->paginate(8);
        
        return view('livewire.user-list', [
            'users' => $users,
        ]);
    }
}
