<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('List Survey') }}
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
    @if ($users && count($users)>0)
        <div>
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <!-- Table -->
                <table class='mx-auto max-w-4xl w-full whitespace-nowrap rounded-lg bg-white divide-y divide-gray-300 overflow-hidden'>
                    <thead class="bg-gray-50">
                        <tr class="text-gray-600 text-left">
                            <th class="px-5 py-3 border-b-2 border-black bg-black text-left text-xs font-semibold text-white uppercase tracking-wider">
                                Survey Name
                            </th>
                            @foreach($users as $user)
                                <th class="px-5 py-3 border-b-2 border-black bg-black text-center text-xs font-semibold text-white uppercase tracking-wider">
                                    {{ $user->name }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($surveys as $survey)
                            <tr>
                                <td class="px-5 py-5">
                                    <p class="">
                                        {{ $survey->title }}
                                    </p>
                                    <p class="text-gray-500 text-sm font-semibold tracking-wide">
                                    {{ $survey->description }}
                                    </p>
                                </td>
                                @foreach($users as $user)
                                    @php 
                                        $responseExist = in_array($user->id, array_column($survey->responses->toArray(), 'user_id'))
                                    @endphp
                                    <td class="px-5 py-5 text-center">
                                        @if($responseExist)
                                            <button wire:click="exportExcel({{ $survey }}, {{$user->id }})" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                Answered
                                            </button>
                                        @else
                                            <span class="{{ $responseExist ? 'text-green-800 bg-green-200' : 'text-red-800 bg-red-200' }} font-semibold px-2 rounded-full">
                                                {{ $responseExist ? 'Answered' : 'Not Yet' }}
                                            </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
