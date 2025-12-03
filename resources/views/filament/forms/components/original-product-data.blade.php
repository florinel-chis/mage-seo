@php
    $record = $getRecord();
    $data = $record?->original_data;

    if (!$data) {
        $displayData = 'No data';
    } else {
        $dataArray = is_array($data) ? $data : json_decode($data, true);
        $displayData = json_encode($dataArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
@endphp

<div class="fi-fo-field-wrp">
    <pre class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-mono overflow-x-auto max-h-[200px] overflow-y-auto text-gray-900 dark:text-gray-100">{{ $displayData }}</pre>
</div>
