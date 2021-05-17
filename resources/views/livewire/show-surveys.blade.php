<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ $user ? $user->name : '' }} {{ __('Survey') }}
    </h2>
</x-slot>
<div class="max-w-7xl mx-auto px-4 sm:px-2 lg:px-2">
    @if (session()->has('message'))
        <div id="alert" class="text-white px-6 py-4 border-0 rounded relative mt-4 mb-2 bg-green-500">
            <span class="inline-block align-middle mr-8">
                {{ session('message') }}
            </span>
            <button class="absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-4 mr-6 outline-none focus:outline-none" onclick="document.getElementById('alert').remove();">
                <span>×</span>
            </button>
        </div>
    @endif
    @if ($surveys && count($surveys)>0)
        @foreach($surveys as $survey)
            @php 
                $onlyEdit = $survey->single_survey && $user && count($survey->sessions) > 0 && $survey->sessions[0]->user_id === $user->id; 
                $hasAnswered = $survey->single_survey && $user && count($survey->sessions) > 0 && $survey->sessions[0]->user_id !== 1;
            @endphp 
            <div class="inline-block m-5">
                <div class="relative px-8 py-4 {{ $hasAnswered ? 'bg-gray-900 text-white' : 'bg-white' }} border border-gray-200 w-64 h-80 max-w-xs rounded-lg shadow-md bg-white hover:shadow-xl transition-shadow duration-300 ease-in-out">
                    <!-- <img src="https://cdn3.iconfinder.com/data/icons/logos-and-brands-adobe/512/152_Google_Play-512.png" class="logo-area h-4"> -->
                    <h3 class="absolute w-44 break-all py-2 text-xl font-bold font-mono">{{ $survey->title }}</h3>
                    <div class="absolute bottom-5 w-full pr-12">
                        <p class="text-lg font-bold">{{ $survey->description }} </p>
                        <p class="text-sm">Created at {{ \Carbon\Carbon::parse($survey->created_at)->format('d/m/Y') }}</p>
                        <div class="text-center py-2 leading-none flex justify-between w-full">
                            <span class="mr-3 inline-flex items-center leading-none text-base font-semibold py-1 ">
                                {{ count($survey->questions) }} Questions
                            </span>
                        </div>
                        @auth
                            <button @if (!$onlyEdit && $hasAnswered) disabled @endif wire:click.prevent="startSurvey({{ $survey }})" type="button" class="w-48 justify-center inline-flex {{ $onlyEdit ? 'bg-green-500 hover:bg-green-700 ' : ($hasAnswered ? 'bg-gray-500 hover:bg-gray-700' : 'bg-blue-500 hover:bg-blue-700') }} text-white font-bold py-2 px-4 rounded">
                                <span class="text-center">{{ $onlyEdit ? 'Edit Survey' : ($hasAnswered ? 'Answered' : 'Start Survey') }}</span>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="w-48 justify-center inline-flex text-white bg-yellow-500 font-bold py-2 px-4 rounded">Log in to Survey</a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
        <div class="px-6 py-6">
            {{ $surveys->links() }}
        </div>
    @endif
    @if($isOpen)
        <div class="fixed z-100 w-full h-full bg-gray-500 opacity-75 top-0 left-0"></div>
            <div class="fixed z-101 w-full h-full top-0 left-0 overflow-y-auto">
                <div class="table w-full h-full py-6">
                    <div class="leading-loose bg-white md:max-w-xl mx-auto rounded">
                        <div class="flex items-start justify-between p-5 border-b border-solid border-blueGray-200 rounded-t">
                            <h3 class="text-3xl font-semibold break-all">
                                <!-- Modal Title -->
                                {{ $currentSurvey['title'] }}
                                <p class="px-4 text-lg">{{ $survey->description }} </p>
                            </h3>
                            <button class="p-1 ml-auto bg-white border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none" wire:click="closeModal">
                                <span class="bg-white text-black h-6 w-6 text-2xl block outline-none focus:outline-none">
                                    ×
                                </span>
                            </button>
                        </div>
                        @if($titleSingleSurvey)
                            <div class="py-2 text-center text-xl">
                                {{ $titleSingleSurvey }}
                                <span class="text-sm">({{ $indexSession }}/{{ count($currentSurvey['sessions']) }} Steps)</span>
                            </div>
                        @endif
                        <form class="max-w-xl m-4 p-4 rounded">
                            @foreach($questions as $question)
                                <div class="mb-5">
                                    <label class="font-bold mb-1 block text-sm capitalize text-gray-600" for="cus_email">{{ $question['content'] }}</label>
                                    @if($question['type'] === 'text')
                                        <input class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" wire:model="responses.{{ $question['id'] }}" required="">
                                    @endif
                                    @if($question['type'] === 'textarea')
                                        <textarea rows="5" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" wire:model="responses.{{ $question['id'] }}"></textarea>
                                    @endif
                                    @if($question['type'] === 'radio')
                                        <div class="flex flex-col">
                                            @foreach($question['options'] as $option)
                                                <label class="inline-flex items-center mt-1">
                                                    <input value="{{ $option['value'] }}" type="radio" wire:model="responses.{{ $question['id'] }}" name="{{ $question['id'] }}" class="form-checkbox h-5 w-5 text-blue-600">
                                                    <span class="ml-2 text-gray-700">{{ $option['value'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($question['type'] === 'checkbox')
                                        <div class="flex flex-col">
                                            @foreach($question['options'] as $iOption => $option)
                                                <label class="inline-flex items-center mt-1">
                                                    <input value="{{ $option['value'] }}" wire:model="responses.{{ $question['id'] }}.{{ $iOption }}" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                    <span class="ml-2 text-gray-700">{{ $option['value'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($question['type'] === 'date')
                                        <input type="date" wire:model="responses.{{ $question['id'] }}" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" required="">
                                    @endif
                                    @if($question['type'] === 'year')
                                        <select wire:model="responses.{{ $question['id'] }}">
                                            @for($year=date('Y'); $year>=1900; $year--)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endfor
                                        </select>
                                    @endif
                                    @if($question['type'] === 'number')
                                        <input type="number" wire:model="responses.{{ $question['id'] }}" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" required="">
                                    @endif
                                </div>
                            @endforeach
                        </form>
                        <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <span class="flex w-full sm:ml-3 sm:w-auto">
                                <button wire:click.prevent="store()" class="px-4 py-1 font-light tracking-wider bg-blue-500 hover:bg-blue-700 text-white rounded font-bold" type="submit">
                                    Save
                                </button>
                            </span>
                            <span class="mt-3 flex w-full sm:mt-0 sm:w-auto">
                                <button wire:click="closeModal()" class="px-4 py-1 font-light tracking-wider bg-white hover:bg-gray-200 border border-gray-300 text-gray-500 font-bold rounded" type="submit">Cancel</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.js"></script>
@endpush
