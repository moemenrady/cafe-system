@props(['title','value','color'])

<div
 class="bg-white rounded-2xl shadow-lg p-4
        hover:scale-[1.03] transition">

    <p class="text-sm text-gray-500">{{ $title }}</p>
    <h3 class="text-2xl font-bold text-{{ $color }}-600">
        {{ $value }}
    </h3>

</div>
