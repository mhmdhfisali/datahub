<x-filament-widgets::widget>
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center space-x-2.5">
            <div class="p-2 rounded-xl bg-amber-500/10 text-amber-500 dark:bg-amber-500/20">
                <x-filament::icon icon="heroicon-m-bolt" class="w-5 h-5 max-w-[20px] max-h-[20px]" />
            </div>
            <div>
                <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">Quick Command & Actions</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Launch ingestion pipelines, generate REST API keys, or run platform maintenance.</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions Container -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <!-- 1. Upload Dataset -->
        <a href="{{ route('filament.admin.resources.datasets.create') }}"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center">
            <div class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-arrow-up-tray" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Upload Dataset</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Excel / CSV Import</span>
        </a>

        <!-- 2. Paste JSON -->
        <a href="{{ route('filament.admin.resources.datasets.create') }}"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center">
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-code-bracket" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Paste JSON</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Direct Raw Payload</span>
        </a>

        <!-- 3. Export Catalog -->
        <button type="button" wire:click="exportSummaryReport"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center w-full">
            <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-document-arrow-down" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Export Catalog</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Download CSV Catalog</span>
        </button>

        <!-- 4. Generate API Key -->
        <button type="button" wire:click="generateApiKey" wire:confirm="Generate a new Sanctum API token for your account?"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center w-full">
            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-key" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Generate API Key</span>
            <span class="text-[10px] text-gray-400 mt-0.5">REST Integration Key</span>
        </button>

        <!-- 5. Run AI Insights -->
        <button type="button" wire:click="runAiInsightsAll"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center w-full">
            <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-sparkles" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Run AI Insights</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Automatic Synthesis</span>
        </button>

        <!-- 6. Clean Temp Files -->
        <button type="button" wire:click="cleanTempFiles" wire:confirm="Clean all temporary upload files from storage?"
           class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 hover:border-primary-500 hover:shadow-md transition-all group cursor-pointer text-center w-full">
            <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 mb-2.5">
                <x-filament::icon icon="heroicon-o-trash" class="w-6 h-6 max-w-[24px] max-h-[24px] text-primary-500 group-hover:scale-110 transition-transform shrink-0" />
            </div>
            <span class="text-xs font-bold text-gray-800 dark:text-gray-100 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">Clean Temp</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Purge Cache & Disk</span>
        </button>
    </div>

    @if($generatedToken)
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-extrabold text-amber-600 dark:text-amber-400">Sanctum API Token:</span>
                <code class="font-mono text-xs select-all text-gray-800 dark:text-gray-100 bg-white dark:bg-gray-800 px-2.5 py-1 rounded-lg border border-gray-200 dark:border-gray-700">{{ $generatedToken }}</code>
            </div>
            <button type="button" @click="navigator.clipboard.writeText('{{ $generatedToken }}')"
                    class="px-3 py-1 text-xs font-bold rounded-lg bg-amber-500 text-white hover:bg-amber-600 transition-colors shadow-sm">
                Copy Token
            </button>
        </div>
    @endif
</x-filament-widgets::widget>
