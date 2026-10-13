<div class="space-y-6" x-data="{
    selectedVersionA: {{ $dataset->current_version ?? 1 }},
    selectedVersionB: {{ $versions->count() > 1 ? ($versions->skip(1)->first()->version_number ?? $dataset->current_version) : ($dataset->current_version ?? 1) }},
    versionsData: {{ json_encode($versions->mapWithKeys(fn($v) => [$v->version_number => [
        'version' => $v->version_number,
        'records' => $v->record_count,
        'health' => $v->health_score,
        'date' => $v->created_at ? $v->created_at->format('d M Y, H:i') : '-',
        'summary' => $v->change_summary,
        'payload' => $v->payload,
    ]])) }},
    get diffSummary() {
        const a = this.versionsData[this.selectedVersionA]?.payload || [];
        const b = this.versionsData[this.selectedVersionB]?.payload || [];
        return {
            countA: a.length,
            countB: b.length,
            delta: a.length - b.length,
        };
    }
}">
    <!-- Snapshot Version Timeline -->
    <div class="space-y-3">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Available Snapshots & Restore Points</h4>

        <div class="divide-y divide-slate-100 dark:divide-slate-800 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden bg-white dark:bg-slate-900/50">
            @forelse ($versions as $ver)
                <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="flex items-center space-x-3">
                        <span class="px-2.5 py-1 rounded-md text-xs font-black
                            {{ $ver->version_number === $dataset->current_version ? 'bg-amber-500 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                            v{{ $ver->version_number }}
                        </span>
                        <div>
                            <div class="text-xs font-bold text-slate-800 dark:text-slate-100">
                                {{ $ver->change_summary ?: 'Dataset update snapshot' }}
                                @if($ver->version_number === $dataset->current_version)
                                    <span class="ml-1 text-[10px] text-amber-500 font-bold uppercase tracking-wider">(Current Active)</span>
                                @endif
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                {{ $ver->record_count }} records • Health: {{ $ver->health_score }}% • {{ $ver->created_at ? $ver->created_at->format('d M Y, H:i:s') : '-' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if($ver->version_number !== $dataset->current_version)
                            <button type="button" wire:click="rollbackVersion({{ $ver->version_number }})"
                                    wire:confirm="Rollback dataset payload to Version {{ $ver->version_number }}?"
                                    class="px-2.5 py-1 text-xs font-bold rounded-lg border border-amber-500/40 text-amber-600 dark:text-amber-400 hover:bg-amber-500 hover:text-white transition-colors">
                                Rollback to v{{ $ver->version_number }}
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-xs text-slate-400">
                    <p class="text-xs text-gray-500">Belum ada riwayat versi untuk dataset ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Interactive Diff Tool -->
    @if($versions->count() >= 2)
        <div class="p-4 border border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/30 space-y-4">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Side-by-Side Diff Comparison</h4>
                <div class="flex items-center space-x-2 text-xs">
                    <span class="text-slate-500">Compare</span>
                    <select x-model="selectedVersionA" class="text-xs py-1 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                        <template x-for="(v, num) in versionsData" :key="num">
                            <option :value="num" x-text="`v${num}`"></option>
                        </template>
                    </select>
                    <span class="text-slate-500">with</span>
                    <select x-model="selectedVersionB" class="text-xs py-1 px-2 rounded border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800">
                        <template x-for="(v, num) in versionsData" :key="num">
                            <option :value="num" x-text="`v${num}`"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Version A Rows</span>
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-200" x-text="diffSummary.countA"></div>
                </div>
                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Version B Rows</span>
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-200" x-text="diffSummary.countB"></div>
                </div>
                <div class="p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800">
                    <span class="text-[10px] text-slate-400 uppercase font-bold">Row Delta</span>
                    <div class="text-lg font-bold"
                         :class="diffSummary.delta > 0 ? 'text-emerald-500' : (diffSummary.delta < 0 ? 'text-rose-500' : 'text-slate-400')"
                         x-text="(diffSummary.delta > 0 ? '+' : '') + diffSummary.delta"></div>
                </div>
            </div>
        </div>
    @endif
</div>
