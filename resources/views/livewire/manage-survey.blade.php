<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Manage Survey') }}
    </h2>
</x-slot>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
    <button wire:click="create()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mt-10">Create New Survey</button>
    @if ($surveys && count($surveys)>0)
        <div class="py-10">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr>
                            <th
                                class="px-5 py-3 border-b-2 border-black bg-black text-left text-xs font-semibold text-white uppercase tracking-wider">
                                {{ __('List Survey') }}
                            </th>
                            <th
                                class="px-5 py-3 border-b-2 border-black bg-black text-left text-xs font-semibold text-white uppercase tracking-wider">
                                {{ __('Questions') }}
                            </th>
                            <th
                                class="px-5 py-3 border-b-2 border-black bg-black text-left text-xs font-semibold text-white uppercase tracking-wider text-right">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surveys as $survey) 
                            <tr>
                                <td class="px-5 py-5 bg-white text-xxs @if (!$loop->last) border-gray-200 border-b @endif">
                                    <!-- {{ Str::limit($survey->title, 25) }} -->
                                    {{ $survey->title }} - {{ $survey->description }}
                                </td>
                                <td class="px-5 py-5 bg-white text-xxs @if (!$loop->last) border-gray-200 border-b @endif">
                                    {{ count($survey->questions) - $survey->single_survey }}
                                </td>
                                <td class="px-5 py-5 bg-white text-sm @if (!$loop->last) border-gray-200 border-b @endif text-right">
                                    <div class="inline-block whitespace-no-wrap">
                                        <button wire:click="$emit('triggerEdit',{{ $survey->id }})" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Edit</button>
                                        <button wire:click="$emit('triggerDelete',{{ $survey->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Delete</button>
                                        <button wire:click="$emit('triggerExport',{{ $survey }})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Export</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-6">
                    {{ $surveys->links() }}
                </div>
            </div>
        </div>
    @endif
    @if($isOpen)
        <div class="fixed z-100 w-full h-full bg-gray-500 opacity-75 top-0 left-0"></div>
        <div class="fixed z-101 w-full h-full top-0 left-0 overflow-y-auto">
            <div class="table w-full h-full py-6">
                <div class="table-cell text-center align-middle">
                    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="bg-white rounded-lg text-left overflow-hidden shadow-xl">
                            <div class="flex items-start justify-between p-5 border-b border-solid border-blueGray-200 rounded-t">
                                <h3 class="text-3xl font-semibold">
                                    <!-- Modal Title -->
                                    Create New Survey
                                </h3>
                                <button class="p-1 ml-auto bg-white border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none" wire:click="closeModal">
                                    <span class="bg-white text-black h-6 w-6 text-2xl block outline-none focus:outline-none">
                                        ×
                                    </span>
                                </button>
                            </div>
                            <form>
                                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <span class="text-xs mb-3 font-semibold inline-block py-1 px-2 uppercase rounded text-white bg-blue-900 uppercase last:mr-0 mr-1">
                                        Survey Detail
                                    </span>
                                    <div class="flex flex-wrap -mx-3 mb-6">
                                        <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                                            <label for="titleInput" class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="titleInput" wire:model="title">
                                            @error('title') <span class="text-red-500">{{ $message }}</span>@enderror
                                        </div>
                                        <div class="w-full md:w-2/3 px-3 mb-6 md:mb-0">
                                            <label for="titleInput" class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                                            <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="descriptionInput" wire:model="description">
                                            @error('description') <span class="text-red-500">{{ $message }}</span>@enderror
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap -mx-3 mb-6">
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center @if($surveyId) bg-gray-500 px-2 rounded @endif">
                                                <input value="1" @if($surveyId) disabled @endif wire:change="changeSingleSurvey()" wire:model="singleSurvey" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Single Survey</span>
                                            </label>
                                        </div>
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center">
                                                <input value="1" wire:change="changeTotalOption" wire:model="totalInRight" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Total in Right</span>
                                            </label>
                                        </div>
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center">
                                                <input value="1" wire:change="changeTotalOption" wire:model="totalInBottom" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Total in Bottom</span>
                                            </label>
                                        </div>
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center">
                                                <input value="1" wire:change="changeAverageOption" wire:model="averageInRight" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Average in Right</span>
                                            </label>
                                        </div>
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center">
                                                <input value="1" wire:change="changeAverageOption" wire:model="averageInBottom" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Average in Bottom</span>
                                            </label>
                                        </div>
                                        <div class="w-full md:w-1/6 px-3 mb-3 md:mb-0">
                                            <label class="inline-flex items-center">
                                                <input value="1" wire:model="customHeader" wire:change="changeOptionCustomHeader" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                <span class="ml-2 text-gray-700">Custom Header</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    @if(count($headers))
                                        <div class="flex flex-wrap -mx-3 mb-3">
                                            @foreach($headers as $iHeader => $header)
                                                <div class="w-full md:w-1/2 px-3 mb-3">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Header {{ $iHeader+1 }}</label>
                                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="headers.{{$iHeader}}.title">
                                                    @error('title') <span class="text-red-500">{{ $message }}</span>@enderror
                                                </div>
                                                <div class="w-full md:w-1/6 px-3 mb-3">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Columns</label>
                                                    <input type="text" placeholder="capital with comma" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="headers.{{$iHeader}}.columns">
                                                </div>
                                                <div class="w-full md:w-1/6 px-3 mb-3">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Level</label>
                                                    <select wire:model="headers.{{$iHeader}}.level"class="w-full leading-tight shadow appearance-none border rounded px-3 py-2 outline-none">
                                                        <option value="2" class="py-1 capitalize">2</option>
                                                        <option value="3" class="py-1 capitalize">3</option>
                                                    </select>
                                                </div>
                                                @if(count($headers) > 1)
                                                    <div class="w-full md:w-1/6 px-3 md:mb-0">
                                                        <button wire:click.prevent="deleteHeader({{ $iHeader }})" type="button" class="mt-7 leading-tight inline-flex bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                            Delete
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="mb-5">
                                            <button wire:click.prevent="addHeader" type="button" class="inline-flex bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                Add Header
                                            </button>
                                        </div>
                                    @endif

                                    @if($singleSurvey)
                                        <span class="text-xs mb-3 font-semibold inline-block py-1 px-2 uppercase rounded text-white bg-blue-900 uppercase last:mr-0 mr-1">
                                            {{ $questions[0]['content'] }}
                                        </span>
                                        <div class="flex flex-wrap -mx-3 mb-3">
                                            @foreach($responses as $iResponse => $response)
                                                <div class="w-full md:w-1/2 px-3 mb-3">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Response {{ $iResponse+1 }}</label>
                                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="responses.{{$iResponse}}.content">
                                                    @error('title') <span class="text-red-500">{{ $message }}</span>@enderror
                                                </div>
                                                <div class="w-full md:w-1/4 px-3 mb-6 md:mb-0">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                                                    <select wire:model="responses.{{$iResponse}}.note" class="w-full leading-tight shadow appearance-none border rounded px-3 py-2 outline-none">
                                                        @foreach($responseOptions as $responseOption) 
                                                            <option value="{{ $responseOption }}" class="py-1 capitalize">{{ $responseOption }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('type') <span class="text-red-500">{{ $message }}</span>@enderror
                                                </div>
                                                @if(count($responses) > 1)
                                                    <div class="w-full md:w-1/5 px-3 md:mb-0">
                                                        <button wire:click.prevent="deleteResponse({{ $iResponse }})" type="button" class="mt-7 leading-tight inline-flex bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                            Delete
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <div class="mb-5">
                                            <button wire:click.prevent="addResponse" type="button" class="inline-flex bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                Add Response
                                            </button>
                                        </div>
                                    @endif

                                    <span class="text-xs mt-2 mb-3 font-semibold inline-block py-1 px-2 uppercase rounded text-white bg-blue-900 uppercase last:mr-0 mr-1">
                                        Question Detail
                                    </span>
                                    @foreach($questions as $iQuestion => $question) 
                                        <div class="flex flex-wrap -mx-3 mb-6">
                                            <div class="w-full md:w-1/2 px-3 mb-6 md:mb-0">
                                                @php $index = $singleSurvey ? $iQuestion : $iQuestion + 1 @endphp
                                                @if ($singleSurvey && $iQuestion === 0)
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Session Name</label>
                                                @else
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Question {{ $index }}</label>
                                                @endif
                                                <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="questions.{{$iQuestion}}.content">
                                                @error('content') <span class="text-red-500">{{ $question['content'] }}</span>@enderror
                                            </div>
                                            <div class="w-full md:w-1/4 px-3 mb-6 md:mb-0">
                                                <label class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                                                <select @if ($singleSurvey && $iQuestion === 0) disabled @endif wire:model="questions.{{$iQuestion}}.type" wire:change="changeTypeQuestion($event.target.value, {{$iQuestion}})" class="w-full @if ($singleSurvey && $iQuestion === 0) bg-gray-200 @endif leading-tight shadow appearance-none border rounded px-3 py-2 outline-none">
                                                    @foreach($typeOptions as $typeOption) 
                                                        <option value="{{ $typeOption }}" class="py-1 capitalize">{{ $typeOption }}</option>
                                                    @endforeach
                                                </select>
                                                <!-- <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="questions.{{$iQuestion}}.type"> -->
                                                @error('type') <span class="text-red-500">{{ $message }}</span>@enderror
                                            </div>
                                            @if(count($questions) > 1 && !($singleSurvey && $iQuestion === 0))
                                            <div class="w-full md:w-1/4 px-3 mb-6 md:mb-0">
                                                <button wire:click="$emit('triggerDeleteQuestion', {{ $iQuestion }})" type="button" class="mt-7 leading-tight inline-flex bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                    Delete
                                                </button>
                                            </div>
                                            @endif
                                            @foreach($question['options'] as $iOption => $option)
                                                <div class="w-full md:w-3/4 px-3 mb-6 md:mb-0 mt-4 ml-8">
                                                    <label class="block text-gray-700 text-sm font-bold mb-2">Option {{ $iOption + 1 }}</label>
                                                    <input type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" wire:model="questions.{{$iQuestion}}.options.{{$iOption}}.value">
                                                    @error('content') <span class="text-red-500">{{ $option }}</span>@enderror
                                                </div>
                                                @if(count($question['options']) > 1)
                                                <div class="w-full md:w-1/5 px-3 mb-6 md:mb-0">
                                                    <button wire:click.prevent="deleteOption({{ $iQuestion }}, {{ $iOption }})" type="button" class="mt-11 leading-tight inline-flex bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                        Delete
                                                    </button>
                                                </div>
                                                @endif
                                            @endforeach
                                            @if(count($question['options']) > 0)
                                            <div class="w-full md:w-1/4 px-3 mb-6 md:mb-0 mt-4">
                                                <button wire:click.prevent="addOption({{$iQuestion}})" type="button" class="ml-8 inline-flex bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                                    Add Option
                                                </button>
                                            </div>
                                            @endif
                                        </div>
                                    @endforeach
                                    <button wire:click.prevent="addQuestion()" type="button" class="inline-flex bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Add Question
                                    </button>
                                </div>
                                <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <span class="flex w-full sm:ml-3 sm:w-auto">
                                        <button wire:click.prevent="store()" type="button" class="inline-flex bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save</button>
                                    </span>
                                    <span class="mt-3 flex w-full sm:mt-0 sm:w-auto">
                                        <button wire:click="closeModal()" type="button" class="inline-flex bg-white hover:bg-gray-200 border border-gray-300 text-gray-500 font-bold py-2 px-4 rounded">Cancel</button>
                                    </span>
                                </div>
                            </form> 
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
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {

        @this.on('triggerDelete', surveyId => {
            Swal.fire({
                title: 'Are You Sure?',
                text: 'Survey record will be deleted!',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete!'
            }).then((result) => {
                if (result.value) {
                    @this.call('delete',surveyId)
                } else {
                    console.log("Canceled");
                }
            });
        });

        @this.on('triggerEdit', surveyId => {
            Swal.fire({
                title: 'Are You Sure?',
                text: 'This action can affect of answers/responses from user',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Edit'
            }).then((result) => {
                if (result.value) {
                    @this.call('edit',surveyId)
                } else {
                    console.log("Canceled");
                }
            });
        });

        @this.on('triggerDeleteQuestion', iQuestion => {
            Swal.fire({
                title: 'Are You Sure?',
                text: 'Question and Answer record will be deleted!',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete!'
            }).then((result) => {
                if (result.value) {
                    @this.call('deleteQuestion', iQuestion)
                } else {
                    console.log("Canceled");
                }
            });
        });

        @this.on('triggerExport', survey => {
            Swal.fire({
                title: 'Export User Responses',
                input: 'select',
                inputOptions: {!! json_encode($users, 1) !!},
                inputPlaceholder: 'Select User',
                showCancelButton: true,
                inputValidator: function (value) {
                    return new Promise(function (resolve, reject) {
                        if (value !== '') {
                            resolve();
                        } else {
                            resolve('You need to select a Tier');
                        }
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const userId = result.value
                    @this.call('exportExcel', survey, userId)
                }
            });
        });
    })
</script>
@endpush