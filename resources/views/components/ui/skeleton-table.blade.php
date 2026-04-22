@props([
    'cols' => 4,
    'rows' => 6,
])

<table class="min-w-full divide-y divide-white/5 text-sm">
    <thead>
        <tr>
            @for($i = 0; $i < $cols; $i++)
                <th class="px-4 py-3">
                    <div class="h-3 w-20 rounded-full bg-white/10"></div>
                </th>
            @endfor
        </tr>
    </thead>
    <tbody class="divide-y divide-white/5 animate-pulse">
        @for($r = 0; $r < $rows; $r++)
            <tr>
                @for($c = 0; $c < $cols; $c++)
                    <td class="px-4 py-3">
                        <div class="h-3 w-full max-w-[160px] rounded-full bg-white/10"></div>
                    </td>
                @endfor
            </tr>
        @endfor
    </tbody>
</table>
