@php
    // spec: { title?, headers: [..], rows: [[..], ..] }
    $headers = $spec['headers'] ?? [];
    $rows = $spec['rows'] ?? [];
@endphp
<figure class="my-8 not-prose overflow-x-auto">
    @isset($spec['title'])
        <figcaption class="text-sm text-neutral-400 mb-4">{{ $spec['title'] }}</figcaption>
    @endisset
    <table class="w-full text-sm border border-neutral-700/50 rounded-2xl overflow-hidden">
        @if ($headers)
            <thead class="bg-neutral-800/50">
                <tr>
                    @foreach ($headers as $h)
                        <th class="text-left font-medium text-neutral-400 px-4 py-2">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-neutral-800">
            @foreach ($rows as $row)
                <tr>
                    @foreach ((array) $row as $cell)
                        <td class="px-4 py-2 text-neutral-300 font-mono tabular-nums">{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</figure>
