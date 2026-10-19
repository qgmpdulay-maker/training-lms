{{-- Suggestions for every City field on the page, listed once rather than
     inside each input — the list holds 1,600-plus cities and municipalities,
     and search results get swapped in and out around it.

     Include once per page; any number of inputs can point at it with
     list="ph-city-options". --}}
<datalist id="ph-city-options">
    @foreach (\App\Support\CityDirectory::labels() as $cityOption)
        <option value="{{ $cityOption }}"></option>
    @endforeach
</datalist>
