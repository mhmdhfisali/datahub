<div x-data="{
    isDark: false,
    init() {
        this.isDark = document.documentElement.classList.contains('dark') ||
            localStorage.getItem('theme') === 'dark';
        this.sync();
    },
    toggle() {
        this.isDark = !this.isDark;
        const newTheme = this.isDark ? 'dark' : 'light';
        localStorage.setItem('theme', newTheme);
        this.sync();
    },
    sync() {
        if (this.isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: this.isDark ? 'dark' : 'light' }));
    }
}" class="flex items-center mr-2">
    <button type="button" @click="toggle()"
            :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            class="relative inline-flex items-center justify-center p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors duration-300 focus:outline-none">
        <!-- Sun Icon (shows in dark) -->
        <svg x-show="isDark" class="w-5 h-5 max-w-[20px] max-h-[20px] flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9h-1m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <!-- Moon Icon (shows in light) -->
        <svg x-show="!isDark" class="w-5 h-5 max-w-[20px] max-h-[20px] flex-shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>
</div>

