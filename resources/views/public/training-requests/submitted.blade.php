<x-public-layout>
    <div class="pt-28 sm:pt-32 pb-16">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
                @if ($referenceNumber)
                    <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>

                    <h1 class="text-xl font-bold text-[#152A4E] mb-2">{{ __('Request Received') }}</h1>
                    <p class="text-sm text-gray-500 mb-6">
                        {{ __('Thank you for requesting :training. CDTI will review your request and get in touch using the contact details you provided.', ['training' => $trainingTitle]) }}
                    </p>

                    <p class="text-xs text-gray-400 mb-1">{{ __('Please keep this reference number for your records') }}</p>
                    <p class="text-2xl font-bold text-[#152A4E] tracking-wide mb-8">{{ $referenceNumber }}</p>

                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                        {{ __('Back to Trainings') }}
                    </a>
                @else
                    <h1 class="text-xl font-bold text-[#152A4E] mb-2">{{ __('Nothing to show here') }}</h1>
                    <p class="text-sm text-gray-500 mb-6">{{ __("We don't have a pending confirmation for this session.") }}</p>
                    <a href="{{ route('public.training-requests.create') }}" class="inline-flex items-center justify-center bg-[#152A4E] hover:bg-[#1E3A66] text-white text-sm font-semibold rounded-lg px-6 py-3 transition">
                        {{ __('Request Technical Assistance') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-public-layout>
