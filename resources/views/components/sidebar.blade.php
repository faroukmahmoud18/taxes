<!-- Sidebar -->
<div x-show="isSidebarOpen"
     @click.away="isSidebarOpen = false"
     x-transition:enter="transition ease-in-out duration-300"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed inset-y-0 left-0 z-40 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex-shrink-0 transform md:relative md:translate-x-0 md:block"
     x-cloak>
    <div class="h-full flex flex-col">
        <!-- Logo / App Name (Optional here, could be in top nav only) -->
        <div class="px-4 py-6 text-center border-b border-gray-200 dark:border-gray-700">
            <a href="{{ route('dashboard') }}"> {{-- Or landing if preferred --}}
                <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200 mx-auto" />
            </a>
        </div>

        <nav class="flex-grow space-y-1 py-4">
            @auth
                <!-- User Specific Links -->
                <x-sidebar-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-sidebar-nav-link>
                <x-sidebar-nav-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                    {{ __('Expenses') }}
                </x-sidebar-nav-link>
                <x-sidebar-nav-link :href="route('subscriptions.index')" :active="request()->routeIs('subscriptions.*')">
                    {{ __('Subscriptions') }}
                </x-sidebar-nav-link>
                <x-sidebar-nav-link :href="route('tax-estimation.show')" :active="request()->routeIs('tax-estimation.*')">
                    {{ __('Tax Estimation') }}
                </x-sidebar-nav-link>

                @if(Auth::user()->is_admin)
                    <!-- Admin Specific Links -->
                    <div class="pt-4 pb-2">
                        <h6 class="px-4 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                            {{ __('Admin Menu') }}
                        </h6>
                    </div>
                    <x-sidebar-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        {{ __('Admin Dashboard') }}
                    </x-sidebar-nav-link>
                    {{-- Placeholder for Manage Users link - ensure route exists or comment out --}}
                    {{-- <x-sidebar-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')"> --}}
                    {{--    {{ __('Manage Users') }} --}}
                    {{-- </x-sidebar-nav-link> --}}
                    <x-sidebar-nav-link :href="route('admin.subscription-plans.index')" :active="request()->routeIs('admin.subscription-plans.*')">
                        {{ __('Subscription Plans') }}
                    </x-sidebar-nav-link>
                    <x-sidebar-nav-link :href="route('admin.static-pages.index')" :active="request()->routeIs('admin.static-pages.*')">
                        {{ __('Static Pages') }}
                    </x-sidebar-nav-link>
                    <x-sidebar-nav-link :href="route('admin.tax-configuration.index')" :active="request()->routeIs('admin.tax-configuration.*')">
                        {{ __('Tax Configuration') }}
                    </x-sidebar-nav-link>

                    {{-- Link for Admin to view User Dashboard (if different from Admin Dashboard) --}}
                    {{-- Consider if this is needed if user links are already present for admin --}}
                    {{-- <div class="pt-4 pb-2"> --}}
                    {{--    <h6 class="px-4 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"> --}}
                    {{--        {{ __('User View') }} --}}
                    {{--    </h6> --}}
                    {{-- </div> --}}
                    {{-- <x-sidebar-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard') && !request()->routeIs('admin.dashboard')"> --}}
                    {{--    {{ __('View User Dashboard') }} --}}
                    {{-- </x-sidebar-nav-link> --}}
                @endif

                <!-- Common Account Links -->
                 <div class="pt-4 pb-2 border-t border-gray-200 dark:border-gray-700 mt-auto"> {{-- Pushes to bottom if sidebar has fixed height and nav is flex-grow --}}
                    <h6 class="px-4 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold">
                        {{ __('Account') }}
                    </h6>
                </div>
                <x-sidebar-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')">
                    {{ __('Profile') }}
                </x-sidebar-nav-link>
                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <x-sidebar-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                                        class="w-full text-left">
                        {{ __('Log Out') }}
                    </x-sidebar-nav-link>
                </form>
            @endauth
        </nav>
    </div>
</div>
<!-- Overlay for mobile when sidebar is open -->
<div x-show="isSidebarOpen" class="fixed inset-0 z-30 bg-black opacity-50 md:hidden" @click="isSidebarOpen = false" x-cloak></div>
