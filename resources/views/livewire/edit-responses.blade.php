<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        <!-- {{ __('Edit Responses') }}  -->
        {{ $user->name }} - {{ $survey->title }}
    </h2>
</x-slot>
<div class="w-11/12 overflow-scroll mx-auto px-4 sm:px-2 lg:px-2 mt-6">
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
    @if ($sessions && count($sessions)>0)
        <div>
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <!-- Table -->
                <table class='mx-auto max-w-4xl w-full whitespace-nowrap rounded-lg bg-white divide-y divide-gray-300 overflow-hidden'>
                    <thead class="bg-gray-50">
                        <tr class="text-gray-600 text-left">
                            @if($survey->single_survey)
                                <th class="px-5 py-3 border-b-2 border-black bg-black text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    <!-- Session -->
                                </th>
                            @endif
                            @foreach($questions as $question)
                                <th class="px-5 py-3 border-b-2 border-black bg-black text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    {{ $question->content }}
                                </th>
                            @endforeach
                            <th class="px-5 py-3 border-b-2 border-black bg-black text-center text-xs font-semibold text-white uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($sessions as $session)
                            <tr>
                                @foreach($session->responses as $response)
                                    <td class="px-5 py-5">
                                        <span class="font-semibold px-2 rounded-full">
                                            {{ $response->content }}
                                        </span>
                                    </td>
                                @endforeach
                                <td class="text-center">
                                    <button wire:click.prevent="editResponse({{ $session->id }}, {{ $session->responses }})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                        Edit
                                    </button>
                                    @if (!$survey->single_survey)
                                        <button wire:click.prevent="$emit('triggerDelete',{{ $session->id }})" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($isOpen)
        <div class="fixed z-100 w-full h-full bg-gray-500 opacity-75 top-0 left-0"></div>
            <div class="fixed z-101 w-full h-full top-0 left-0 overflow-y-auto">
                <div class="table w-full h-full py-6">
                    <div class="leading-loose bg-white md:max-w-xl mx-auto rounded">
                        <div class="flex items-start justify-between p-5 border-b border-solid border-blueGray-200 rounded-t">
                            <h3 class="text-3xl font-semibold break-all" id="modal-title">
                                <!-- Modal Title -->
                                {{ $survey->title }}
                                <p class="px-4 text-lg">{{ $survey->description }} </p>
                            </h3>
                            <button class="p-1 ml-auto bg-white border-0 text-black float-right text-3xl leading-none font-semibold outline-none focus:outline-none" wire:click="closeModal">
                                <span class="bg-white text-black h-6 w-6 text-2xl block outline-none focus:outline-none">
                                    ×
                                </span>
                            </button>
                        </div>
                        <form class="max-w-xl m-4 p-4 rounded">
                            @foreach($questions as $question)
                                <div class="mb-5">
                                    <label class="font-bold mb-1 block text-sm capitalize text-gray-600" for="cus_email">{{ $question->content }}</label>
                                    @if($question->type === 'text')
                                        <input class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" wire:model="responses.{{ $question->id }}" required="">
                                    @endif
                                    @if($question->type === 'textarea')
                                        <textarea rows="5" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" wire:model="responses.{{ $question->id }}"></textarea>
                                    @endif
                                    @if($question->type === 'radio')
                                        <div class="flex flex-col">
                                            @foreach($question->options as $option)
                                                <label class="inline-flex items-center mt-1">
                                                    <input value="{{ $option->value }}" type="radio" wire:model="responses.{{ $question->id }}" name="{{ $question->id }}" class="form-checkbox h-5 w-5 text-blue-600">
                                                    <span class="ml-2 text-gray-700">{{ $option->value }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($question->type === 'checkbox')
                                        <div class="flex flex-col">
                                            @foreach($question->options as $iOption => $option)
                                                <label class="inline-flex items-center mt-1">
                                                    <input value="{{ $option->value }}" wire:model="responses.{{ $question->id }}.{{ $iOption }}" type="checkbox" class="form-checkbox h-5 w-5 text-blue-600">
                                                    <span class="ml-2 text-gray-700">{{ $option->value }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if($question->type === 'date')
                                        <input type="date" wire:model="responses.{{ $question->id }}" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" required="">
                                    @endif
                                    @if($question->type === 'year')
                                        <select wire:model="responses.{{ $question->id }}">
                                            <option value="">Select Year</option>
                                            @for($year=date('Y'); $year>=1900; $year--)
                                                <option value="{{ $year }}">{{ $year }}</option>
                                            @endfor
                                        </select>
                                    @endif
                                    @if($question->type === 'number')
                                        <input type="number" wire:model="responses.{{ $question->id }}" class="w-full px-2 py-2 text-gray-700 bg-gray-100 rounded" type="text" required="">
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
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {

        @this.on('triggerDelete', sessionId => {
            Swal.fire({
                title: 'Are You Sure?',
                text: 'Response this session record will be deleted!',
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete!'
            }).then((result) => {
                if (result.value) {
                    @this.call('deleteResponse',sessionId)
                } else {
                    console.log("Canceled");
                }
            });
        });
    })
</script>
@endpush
