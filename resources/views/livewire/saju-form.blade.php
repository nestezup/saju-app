<div class="max-w-2xl mx-auto">
    @if(!$result)
        <form wire:submit="submit" class="bg-white shadow-lg rounded-lg p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">사주팔자 만세력</h2>

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
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" wire:model="isLunar" value="0" class="mr-2 text-indigo-600">
                        <span>양력</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" wire:model="isLunar" value="1" class="mr-2 text-indigo-600">
                        <span>음력</span>
                    </label>
                </div>
            </div>

            {{-- Birth Time --}}
            <div class="mb-6">
                <label for="birthTime" class="block text-sm font-medium text-gray-700 mb-2">
                    태어난 시간 (선택)
                </label>
                <select id="birthTime" wire:model="birthTime"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
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
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" wire:model="gender" value="male" class="mr-2 text-indigo-600">
                        <span>남성</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" wire:model="gender" value="female" class="mr-2 text-indigo-600">
                        <span>여성</span>
                    </label>
                </div>
            </div>

            @if($error)
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600">{{ $error }}</p>
                </div>
            @endif

            <button type="submit"
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition duration-200"
                wire:loading.attr="disabled">
                <span wire:loading.remove>사주 보기</span>
                <span wire:loading>계산 중...</span>
            </button>
        </form>
    @else
        @php
            // 천간 데이터 (한글, 한자, 오행)
            $ganData = [
                '갑' => ['hanja' => '甲', 'element' => 'wood'],
                '을' => ['hanja' => '乙', 'element' => 'wood'],
                '병' => ['hanja' => '丙', 'element' => 'fire'],
                '정' => ['hanja' => '丁', 'element' => 'fire'],
                '무' => ['hanja' => '戊', 'element' => 'earth'],
                '기' => ['hanja' => '己', 'element' => 'earth'],
                '경' => ['hanja' => '庚', 'element' => 'metal'],
                '신' => ['hanja' => '辛', 'element' => 'metal'],
                '임' => ['hanja' => '壬', 'element' => 'water'],
                '계' => ['hanja' => '癸', 'element' => 'water'],
            ];

            // 지지 데이터 (한글, 한자, 오행)
            $jiData = [
                '자' => ['hanja' => '子', 'element' => 'water'],
                '축' => ['hanja' => '丑', 'element' => 'earth'],
                '인' => ['hanja' => '寅', 'element' => 'wood'],
                '묘' => ['hanja' => '卯', 'element' => 'wood'],
                '진' => ['hanja' => '辰', 'element' => 'earth'],
                '사' => ['hanja' => '巳', 'element' => 'fire'],
                '오' => ['hanja' => '午', 'element' => 'fire'],
                '미' => ['hanja' => '未', 'element' => 'earth'],
                '신' => ['hanja' => '申', 'element' => 'metal'],
                '유' => ['hanja' => '酉', 'element' => 'metal'],
                '술' => ['hanja' => '戌', 'element' => 'earth'],
                '해' => ['hanja' => '亥', 'element' => 'water'],
            ];

            // 오행 색상 (전통 색상 체계)
            $elementColors = [
                'wood' => ['bg' => 'bg-emerald-600', 'text' => 'text-white', 'name' => '목', 'hanja' => '木'],
                'fire' => ['bg' => 'bg-red-600', 'text' => 'text-white', 'name' => '화', 'hanja' => '火'],
                'earth' => ['bg' => 'bg-yellow-500', 'text' => 'text-gray-900', 'name' => '토', 'hanja' => '土'],
                'metal' => ['bg' => 'bg-gray-200', 'text' => 'text-gray-800', 'name' => '금', 'hanja' => '金'],
                'water' => ['bg' => 'bg-gray-900', 'text' => 'text-white', 'name' => '수', 'hanja' => '水'],
            ];

            $pillars = [
                ['label' => '시주', 'labelHanja' => '時柱', 'gan' => $result['metadata']['hour_gan'], 'ji' => $result['metadata']['hour_ji']],
                ['label' => '일주', 'labelHanja' => '日柱', 'gan' => $result['metadata']['day_gan'], 'ji' => $result['metadata']['day_ji']],
                ['label' => '월주', 'labelHanja' => '月柱', 'gan' => $result['metadata']['month_gan'], 'ji' => $result['metadata']['month_ji']],
                ['label' => '년주', 'labelHanja' => '年柱', 'gan' => $result['metadata']['year_gan'], 'ji' => $result['metadata']['year_ji']],
            ];
        @endphp

        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-6 py-5">
                <h2 class="text-xl font-bold text-white text-center">사주팔자 四柱八字</h2>
                <p class="text-slate-300 text-center mt-1 text-sm">
                    {{ $result['birth_date'] }}
                    ({{ $result['is_lunar'] ? '음력' : '양력' }})
                    @if($result['birth_time'] !== '미입력') {{ $result['birth_time'] }} @endif
                    · {{ $result['gender'] === 'male' ? '남' : '여' }}
                </p>
            </div>

            <div class="p-6">
                {{-- 사주팔자 테이블 --}}
                <div class="mb-8">
                    <table class="w-full border-collapse">
                        {{-- 주 라벨 --}}
                        <thead>
                            <tr>
                                @foreach($pillars as $pillar)
                                    <th class="text-center py-2 text-sm text-gray-500 font-medium">
                                        {{ $pillar['label'] }}<br>
                                        <span class="text-xs text-gray-400">{{ $pillar['labelHanja'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            {{-- 천간 행 --}}
                            <tr>
                                @foreach($pillars as $pillar)
                                    @php
                                        $gan = $pillar['gan'];
                                        $ganInfo = $ganData[$gan];
                                        $ganColor = $elementColors[$ganInfo['element']];
                                    @endphp
                                    <td class="p-1">
                                        <div class="{{ $ganColor['bg'] }} {{ $ganColor['text'] }} rounded-lg py-4 text-center shadow-sm">
                                            <div class="text-2xl font-bold">{{ $ganInfo['hanja'] }}</div>
                                            <div class="text-sm mt-1 opacity-80">{{ $gan }}</div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                            {{-- 지지 행 --}}
                            <tr>
                                @foreach($pillars as $pillar)
                                    @php
                                        $ji = $pillar['ji'];
                                        $jiInfo = $jiData[$ji];
                                        $jiColor = $elementColors[$jiInfo['element']];
                                    @endphp
                                    <td class="p-1">
                                        <div class="{{ $jiColor['bg'] }} {{ $jiColor['text'] }} rounded-lg py-4 text-center shadow-sm">
                                            <div class="text-2xl font-bold">{{ $jiInfo['hanja'] }}</div>
                                            <div class="text-sm mt-1 opacity-80">{{ $ji }}</div>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- 일주(日主) --}}
                <div class="mb-6 text-center">
                    @php
                        $dayGan = $result['metadata']['day_gan'];
                        $dayGanInfo = $ganData[$dayGan];
                        $dayColor = $elementColors[$dayGanInfo['element']];
                    @endphp
                    <span class="inline-flex items-center gap-2 px-4 py-2 {{ $dayColor['bg'] }} {{ $dayColor['text'] }} rounded-full text-sm font-medium">
                        일주(日主): {{ $dayGanInfo['hanja'] }} {{ $dayGan }} ({{ $elementColors[$dayGanInfo['element']]['name'] }}{{ $elementColors[$dayGanInfo['element']]['hanja'] }})
                    </span>
                </div>

                {{-- 오행 분포 --}}
                @if(isset($result['metadata']['five_elements']))
                    <div class="mb-6">
                        <h3 class="text-base font-semibold text-gray-700 mb-4 text-center">오행 분포 五行分布</h3>

                        @php
                            $elements = $result['metadata']['five_elements'];
                            $total = array_sum($elements);
                            $maxCount = max($elements);
                        @endphp

                        {{-- 오행 막대 그래프 --}}
                        <div class="flex justify-center items-end gap-3 h-32 mb-4">
                            @foreach(['wood', 'fire', 'earth', 'metal', 'water'] as $key)
                                @php
                                    $count = $elements[$key] ?? 0;
                                    $height = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                    $info = $elementColors[$key];
                                @endphp
                                <div class="flex flex-col items-center">
                                    <div class="text-sm font-bold mb-1 {{ $count > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                                        {{ $count }}
                                    </div>
                                    <div class="w-12 {{ $info['bg'] }} rounded-t-lg transition-all duration-500"
                                         style="height: {{ max($height, 10) }}%"></div>
                                    <div class="mt-2 text-center">
                                        <div class="text-lg font-bold {{ $count > 0 ? 'text-gray-800' : 'text-gray-400' }}">
                                            {{ $info['hanja'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $info['name'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- 오행 요약 테이블 --}}
                        <div class="grid grid-cols-5 gap-2 mt-4">
                            @foreach(['wood', 'fire', 'earth', 'metal', 'water'] as $key)
                                @php
                                    $count = $elements[$key] ?? 0;
                                    $info = $elementColors[$key];
                                @endphp
                                <div class="text-center p-2 rounded-lg {{ $count > 0 ? $info['bg'] : 'bg-gray-100' }} {{ $count > 0 ? $info['text'] : 'text-gray-400' }}">
                                    <div class="text-lg font-bold">{{ $info['hanja'] }}</div>
                                    <div class="text-xs">{{ $info['name'] }}</div>
                                    <div class="text-lg font-bold mt-1">{{ $count }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- LLM 분석 결과 --}}
                <div class="mb-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4 text-center">사주 분석 四柱分析</h3>

                    @if($isAnalyzing)
                        <div class="bg-gray-50 rounded-lg p-6 text-center">
                            <div class="inline-flex items-center gap-3">
                                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-gray-600">사주를 분석하고 있습니다...</span>
                            </div>
                        </div>
                    @elseif($analysis)
                        <div class="bg-gradient-to-br from-slate-50 to-gray-100 rounded-lg p-6 border border-gray-200">
                            <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed
                                        prose-headings:text-gray-800 prose-headings:font-bold
                                        prose-h1:text-xl prose-h2:text-lg prose-h3:text-base
                                        prose-p:my-2 prose-ul:my-2 prose-ol:my-2
                                        prose-li:my-0.5 prose-strong:text-gray-800">
                                {!! Str::markdown($analysis) !!}
                            </div>
                        </div>
                    @endif
                </div>

                {{-- 다시보기 버튼 --}}
                <button wire:click="resetForm"
                    class="w-full py-3 px-4 bg-slate-700 hover:bg-slate-800 text-white font-medium rounded-lg transition duration-200">
                    다시 보기
                </button>
            </div>
        </div>
    @endif
</div>
