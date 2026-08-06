{{-- 顧客アバター：絵文字があれば絵文字、なければ名前の頭文字＋名前由来の色。$name, $emoji, $lg(bool) --}}
@php($palette = ['#d6889b', '#c9a227', '#6a8caf', '#8a6db1', '#5aa576', '#c17a54', '#b25c72', '#4f9d9d'])
@php($color = $palette[abs(crc32((string) ($name ?? '?'))) % count($palette)])
@if(! empty($emoji))
    <span style="font-size:{{ ($lg ?? false) ? 32 : 26 }}px;line-height:1">{{ $emoji }}</span>
@else
    <span class="avatar {{ ($lg ?? false) ? 'lg' : '' }}" style="background:{{ $color }}">{{ mb_strtoupper(mb_substr((string) ($name ?? '?'), 0, 1)) }}</span>
@endif
