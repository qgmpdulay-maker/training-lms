{{-- Instructors and Deployments — the two "who" rosters, as opposed to the
     training-delivery pages around them.

     They sit under Administration for a Super Admin (alongside Organizations
     and Manage Users, which is where they read as records to administer) and
     under Training for a Regional Admin, who has no Administration section.
     Defined here once so moving them between sections never means keeping two
     copies of the markup in step. --}}
<x-sidebar-nav-link :href="route('admin.instructors.index')" :active="request()->routeIs('admin.instructors.*')">
    <x-slot:icon>
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" /></svg>
    </x-slot:icon>
    {{ __('Instructors') }}
</x-sidebar-nav-link>
<x-sidebar-nav-link :href="route('admin.deployments.index')" :active="request()->routeIs('admin.deployments.*')">
    <x-slot:icon>
        <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
    </x-slot:icon>
    {{ __('Deployments') }}
</x-sidebar-nav-link>
