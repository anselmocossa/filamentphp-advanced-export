@php
    use Carbon\Carbon;

    // Get the first key from the data (table name)
    $tableName = collect(get_defined_vars())
        ->keys()
        ->filter(fn($key) => !in_array($key, ['__env', 'app', '__data', '__path', 'columnsConfig']))
        ->first();

    $records = $$tableName ?? collect();
@endphp
<table>
    <thead>
    <tr>
        @if($records->isNotEmpty())
            @php
                $firstRecord = $records->first();
                $attributes = $firstRecord->getAttributes();
            @endphp
            @foreach(array_keys($attributes) as $column)
                <th>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $column)) }}</th>
            @endforeach
        @endif
    </tr>
    </thead>
    <tbody>
    @foreach($records as $record)
        <tr>
            @foreach($record->getAttributes() as $key => $value)
                <td>
                    @if($value instanceof \Carbon\Carbon || $value instanceof \DateTime)
                        {{ $value->format(config('advanced-export.date_format', 'd/m/Y H:i')) }}
                    @elseif(is_null($value))
                        -
                    @else
                        {{ $value }}
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
