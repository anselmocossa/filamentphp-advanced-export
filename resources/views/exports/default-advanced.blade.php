@php
    use Carbon\Carbon;

    // Get the first key from the data (table name)
    $tableName = collect(get_defined_vars())
        ->keys()
        ->filter(fn($key) => !in_array($key, ['__env', 'app', '__data', '__path', 'columnsConfig']))
        ->first();

    $records = $$tableName ?? collect();
    $dateFormat = config('advanced-export.date_format', 'd/m/Y H:i');
@endphp
<table>
    <thead>
    <tr>
        @foreach($columnsConfig as $columnConfig)
            <th>{{ $columnConfig['title'] ?? __('advanced-export::messages.undefined_title') }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($records as $record)
        <tr>
            @foreach($columnsConfig as $columnConfig)
                @php
                    $field = $columnConfig['field'] ?? '';
                    $value = data_get($record, $field);
                @endphp
                <td>
                    @if($value instanceof \Carbon\Carbon || $value instanceof \DateTime)
                        {{ $value->format($dateFormat) }}
                    @elseif(is_null($value))
                        -
                    @elseif(is_bool($value))
                        {{ $value ? __('advanced-export::messages.yes') : __('advanced-export::messages.no') }}
                    @elseif(is_array($value) || is_object($value))
                        {{ json_encode($value) }}
                    @else
                        {{ $value }}
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
