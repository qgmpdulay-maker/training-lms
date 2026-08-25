@php $inputId = $field.'-'.$record->id; @endphp

<form method="POST" action="{{ route('admin.tools.files', $record) }}" enctype="multipart/form-data" class="flex items-center gap-1.5">
    @csrf
    <input type="file" name="{{ $field }}" id="{{ $inputId }}" accept="{{ $accept }}" class="hidden" onchange="this.form.submit()">

    @if ($path)
        <a href="{{ asset('storage/'.$path) }}" target="_blank"
            class="inline-flex items-center gap-1.5 text-xs font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-full pl-2 pr-2.5 py-1 hover:bg-green-100 dark:hover:bg-green-900/50 transition">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ __('View') }}
        </a>
    @else
        <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded-full pl-2 pr-2.5 py-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            {{ __('No file') }}
        </span>
    @endif

    <label for="{{ $inputId }}" title="{{ $path ? __('Replace file') : __('Upload file') }}"
        class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 hover:text-[#152A4E] dark:hover:text-white cursor-pointer transition">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
    </label>
</form>
