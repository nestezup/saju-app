<div class="max-w-2xl mx-auto">
    @if(!$result)
        <form wire:submit="submit" class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">사주팔자 분석</h2>

            {{-- Birth Date --}}
            <div class="mb-6">
                <label for="birthDate" class="block text-sm font-medium text-gray-700 mb-2">
                    생년월일 <span class="text-red-500">*</span>
                </label>
                <input
                    type="date"
                    id="birthDate"
                    wire:model="birthDate"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    required
                >
                @error('birthDate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Calendar Type --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">달력 유형</label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="isLunar"
                            value="0"
                            class="mr-2 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>양력</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="isLunar"
                            value="1"
                            class="mr-2 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>음력</span>
                    </label>
                </div>
            </div>

            {{-- Birth Time --}}
            <div class="mb-6">
                <label for="birthTime" class="block text-sm font-medium text-gray-700 mb-2">
                    태어난 시간 (선택)
                </label>
                <select
                    id="birthTime"
                    wire:model="birthTime"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">모름 / 미입력</option>
                    <option value="자시">자시 (23:00 - 01:00)</option>
                    <option value="축시">축시 (01:00 - 03:00)</option>
                    <option value="인시">인시 (03:00 - 05:00)</option>
                    <option value="묘시">묘시 (05:00 - 07:00)</option>
                    <option value="진시">진시 (07:00 - 09:00)</option>
                    <option value="사시">사시 (09:00 - 11:00)</option>
                    <option value="오시">오시 (11:00 - 13:00)</option>
                    <option value="미시">미시 (13:00 - 15:00)</option>
                    <option value="신시">신시 (15:00 - 17:00)</option>
                    <option value="유시">유시 (17:00 - 19:00)</option>
                    <option value="술시">술시 (19:00 - 21:00)</option>
                    <option value="해시">해시 (21:00 - 23:00)</option>
                </select>
            </div>

            {{-- Gender --}}
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    성별 <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="gender"
                            value="male"
                            class="mr-2 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>남성</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            wire:model="gender"
                            value="female"
                            class="mr-2 text-indigo-600 focus:ring-indigo-500"
                        >
                        <span>여성</span>
                    </label>
                </div>
                @error('gender')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Error Message --}}
            @if($error)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600">{{ $error }}</p>
                </div>
            @endif

            {{-- Submit Button --}}
            <button
                type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>사주 분석하기</span>
                <span wire:loading class="flex items-center justify-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    분석 중... (최대 2분 소요)
                </span>
            </button>
        </form>
    @else
        {{-- Result Display --}}
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-6">
                <h2 class="text-2xl font-bold text-white text-center">사주팔자 분석 결과</h2>
                <p class="text-indigo-100 text-center mt-2">
                    {{ $result->birth_date->format('Y년 m월 d일') }}
                    ({{ $result->is_lunar ? '음력' : '양력' }})
                    {{ $result->gender === 'male' ? '남성' : '여성' }}
                </p>
            </div>

            <div class="p-8">
                {{-- Saju Pillars --}}
                @if($result->metadata)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">사주팔자</h3>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-500 mb-1">시주</p>
                                <p class="text-xl font-bold text-gray-800">
                                    {{ $result->metadata['hour_gan'] ?? '' }}{{ $result->metadata['hour_ji'] ?? '' }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-500 mb-1">일주</p>
                                <p class="text-xl font-bold text-gray-800">
                                    {{ $result->metadata['day_gan'] ?? '' }}{{ $result->metadata['day_ji'] ?? '' }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-500 mb-1">월주</p>
                                <p class="text-xl font-bold text-gray-800">
                                    {{ $result->metadata['month_gan'] ?? '' }}{{ $result->metadata['month_ji'] ?? '' }}
                                </p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <p class="text-sm text-gray-500 mb-1">년주</p>
                                <p class="text-xl font-bold text-gray-800">
                                    {{ $result->metadata['year_gan'] ?? '' }}{{ $result->metadata['year_ji'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Five Elements --}}
                    @if(isset($result->metadata['five_elements']))
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">오행 분포</h3>
                            <div class="grid grid-cols-5 gap-2">
                                @php
                                    $elements = [
                                        'wood' => ['name' => '목(木)', 'color' => 'bg-green-500'],
                                        'fire' => ['name' => '화(火)', 'color' => 'bg-red-500'],
                                        'earth' => ['name' => '토(土)', 'color' => 'bg-yellow-500'],
                                        'metal' => ['name' => '금(金)', 'color' => 'bg-gray-400'],
                                        'water' => ['name' => '수(水)', 'color' => 'bg-blue-500'],
                                    ];
                                @endphp
                                @foreach($elements as $key => $element)
                                    <div class="text-center">
                                        <div class="{{ $element['color'] }} text-white rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-2 text-lg font-bold">
                                            {{ $result->metadata['five_elements'][$key] ?? 0 }}
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $element['name'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Saju Reading --}}
                @if($result->saju_result)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">사주 해석</h3>
                        <div class="prose prose-sm max-w-none bg-gray-50 p-6 rounded-lg">
                            {!! nl2br(e($result->saju_result)) !!}
                        </div>
                    </div>
                @endif

                {{-- Daily Fortune --}}
                @if($result->daily_fortune)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">오늘의 운세</h3>
                        <div class="prose prose-sm max-w-none bg-indigo-50 p-6 rounded-lg border border-indigo-100">
                            {!! nl2br(e($result->daily_fortune)) !!}
                        </div>
                    </div>
                @endif

                {{-- Reset Button --}}
                <button
                    wire:click="resetForm"
                    class="w-full py-3 px-4 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition duration-200"
                >
                    다시 분석하기
                </button>
            </div>
        </div>
    @endif
</div>
