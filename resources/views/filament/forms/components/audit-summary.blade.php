@php
    $record = $getRecord();
    $flags = $record?->audit_flags ?? [];
    $flagCount = is_array($flags) ? count($flags) : 0;
@endphp

<div class="fi-fo-field-wrp">
    @if ($flagCount === 0)
        <div class="text-success-600 dark:text-success-400 font-semibold">
            ✓ No issues detected
        </div>
    @else
        <div class="space-y-3">
            <div class="text-warning-600 dark:text-warning-400 font-semibold">
                ⚠ {{ $flagCount }} issue(s) detected:
            </div>

            <ul class="space-y-2 ml-7">
                @foreach ($flags as $flag)
                    <li class="text-sm text-gray-700 dark:text-gray-300">
                        @if (is_array($flag))
                            <div class="flex flex-col gap-1">
                                @if (isset($flag['type']))
                                    <span class="text-xs font-semibold text-gray-500 uppercase">{{ $flag['type'] }}</span>
                                @endif
                                <span>{{ $flag['message'] ?? json_encode($flag) }}</span>
                                @if (isset($flag['severity']))
                                    <span class="text-xs px-2 py-0.5 rounded inline-block
                                        @if($flag['severity'] === 'high') bg-danger-100 text-danger-700
                                        @elseif($flag['severity'] === 'medium') bg-warning-100 text-warning-700
                                        @else bg-info-100 text-info-700
                                        @endif">
                                        {{ ucfirst($flag['severity']) }} severity
                                    </span>
                                @endif
                            </div>
                        @else
                            {{ $flag }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
