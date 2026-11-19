<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';
import { errorLabels, statusLabels, formatCheckDate } from '../monitoring.js';

defineProps({ checks: { type: Object, required: true }, currentUrl: { type: String, required: true } });
const reasons = {
    first_check: 'First recorded check.',
    unknown_target: 'The tested URL was not recorded; comparison is unavailable.',
    different_target: 'The URL changed; these results are not compared.',
};
</script>

<template>
    <section aria-labelledby="history-title" class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
        <div class="border-b border-slate-800 px-5 py-5 sm:px-6">
            <h2 id="history-title" class="text-xl font-semibold">{{ $t('Check history') }}</h2>
            <p class="mt-2 text-sm text-slate-400">{{ $t('Newest first. Queued requests are not completed results.') }}</p>
            <p class="mt-2 text-xs leading-6 text-slate-400">{{ $t('History is preserved when the URL changes. Older entries may not include the tested URL. Timing changes compare successful checks of the same recorded URL only.') }}</p>
        </div>
        <div v-if="!checks.data.length" class="px-6 py-12 text-center">
            <h3 class="font-semibold">{{ $t('No checks recorded yet.') }}</h3>
            <p class="mt-3 text-sm leading-6 text-slate-400">{{ $t('Start the worker, request a check, then refresh the results. A queued request alone does not create a history entry.') }}</p>
        </div>
        <ol v-else class="divide-y divide-slate-800">
            <li v-for="check in checks.data" :key="check.id" class="p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <time :datetime="check.checked_at" class="text-sm font-medium">{{ formatCheckDate(check.checked_at, $page.props.locale) }}</time>
                    <StatusBadge :status="check.status" :error-type="check.error_type" />
                </div>
                <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-slate-400">HTTP</dt><dd class="mt-1 font-mono">{{ check.http_status ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ $t('Response') }}</dt><dd class="mt-1">{{ check.response_time_ms === null ? '—' : $t(':value ms', { value: check.response_time_ms }) }}</dd></div>
                    <div><dt class="text-xs text-slate-400">{{ $t('Result details') }}</dt><dd class="mt-1" :class="check.error_type ? 'text-amber-300' : 'text-emerald-300'">{{ $t(check.error_type ? errorLabels[check.error_type] ?? 'Check could not complete' : 'No failure') }}</dd></div>
                </dl>
                <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs">
                    <p class="mb-1 text-slate-400">{{ $t('Tested URL') }} · {{ $t(check.target_url === currentUrl ? 'Current URL' : check.target_url ? 'Previous URL' : 'Unknown') }}</p>
                    <p v-if="check.target_url" dir="ltr" class="break-all text-start text-slate-200">{{ check.target_url }}</p>
                    <p v-else class="text-slate-400">{{ $t('The URL was not recorded for this older check.') }}</p>
                    <template v-if="check.final_url">
                        <p class="mt-3 text-slate-400">{{ $t('Last requested URL (query and fragment omitted)') }}</p>
                        <p dir="ltr" class="mt-1 break-all text-start text-slate-200">{{ check.final_url }}</p>
                        <p class="mt-2 text-slate-400">{{ $t('Redirects: :count', { count: check.redirect_count ?? '—' }) }}</p>
                    </template>
                </div>
                <div class="mt-4 space-y-2 text-xs leading-6 text-slate-300">
                    <h3 class="font-medium text-slate-400">{{ $t('Changes since previous check') }}</h3>
                    <p v-if="check.comparison.reason">{{ $t(reasons[check.comparison.reason]) }}</p>
                    <template v-else>
                        <p v-if="check.comparison.final_url_changed">{{ $t('Resolved destination changed; timing is not compared.') }}</p>
                        <p v-if="check.comparison.status_changed">{{ $t('Status changed: :previous → :current', { previous: $t(statusLabels[check.comparison.previous_status] ?? 'Unknown'), current: $t(statusLabels[check.status] ?? 'Unknown') }) }}</p>
                        <p v-if="check.comparison.http_status_changed">{{ $t('HTTP changed: :previous → :current', { previous: check.comparison.previous_http_status ?? '—', current: check.http_status ?? '—' }) }}</p>
                        <p v-if="check.comparison.error_changed">{{ $t('Failure changed: :previous → :current', { previous: $t(check.comparison.previous_error_type ? errorLabels[check.comparison.previous_error_type] ?? 'Check could not complete' : 'No failure'), current: $t(check.error_type ? errorLabels[check.error_type] ?? 'Check could not complete' : 'No failure') }) }}</p>
                        <p v-if="!check.comparison.status_changed && !check.comparison.http_status_changed && !check.comparison.error_changed">{{ $t('No changes to status, HTTP code, or failure category.') }}</p>
                        <p v-if="check.comparison.response_time_delta_ms !== null" :class="check.comparison.response_time_delta_ms > 0 ? 'text-amber-300' : 'text-emerald-300'">{{ check.comparison.response_time_delta_ms === 0 ? $t('Response time unchanged.') : $t(check.comparison.response_time_delta_ms > 0 ? 'Response slower by :value ms.' : 'Response faster by :value ms.', { value: Math.abs(check.comparison.response_time_delta_ms) }) }}</p>
                    </template>
                </div>
            </li>
        </ol>
        <nav :aria-label="$t('Check history')" class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-800 px-5 py-4 text-sm">
            <span class="text-slate-400">{{ $t(':total results · Page :current of :last', { total: checks.meta.total, current: checks.meta.current_page, last: checks.meta.last_page }) }}</span>
            <div class="flex gap-3">
                <Link v-if="checks.links.prev" :href="checks.links.prev" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">{{ $t('Previous') }}</Link>
                <Link v-if="checks.links.next" :href="checks.links.next" preserve-scroll class="rounded-lg border border-slate-700 px-3 py-2 hover:bg-slate-800">{{ $t('Next') }}</Link>
            </div>
        </nav>
    </section>
</template>
