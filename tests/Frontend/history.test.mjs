import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { compile, createSSRApp, h } from 'vue';
import { parse } from '@vue/compiler-sfc';
import { renderToString } from '@vue/server-renderer';
import { translate } from '../../resources/js/translation.js';
import { statusLabels, errorLabels, formatCheckDate } from '../../resources/js/monitoring.js';

const source = readFileSync(new URL('../../resources/js/Components/CheckHistory.vue', import.meta.url), 'utf8');
const render = compile(parse(source).descriptor.template.content);
const url = 'https://example.com/health';
const reasons = {
    first_check: 'First recorded check.',
    unknown_target: 'The tested URL was not recorded; comparison is unavailable.',
    different_target: 'The URL changed; these results are not compared.',
};
const record = {
    id: 2, target_url: url, status: 'up', http_status: 200, response_time_ms: 40,
    error_type: null, checked_at: '2026-11-19T10:00:00Z',
    comparison: { reason: null, previous_status: 'down', previous_http_status: 503,
        previous_error_type: 'http_error', status_changed: true, http_status_changed: true,
        error_changed: true, response_time_delta_ms: null },
};

async function renderHistory(locale, data) {
    const catalog = JSON.parse(readFileSync(new URL(`../../lang/${locale}.json`, import.meta.url), 'utf8'));
    const t = (key, parameters) => translate(catalog, key, parameters);
    const checks = { data, meta: { total: data.length, current_page: 1, last_page: 1 }, links: { prev: null, next: null } };
    const app = createSSRApp({ render, setup: () => ({ checks, currentUrl: url, reasons, statusLabels, errorLabels, formatCheckDate }) });
    app.config.globalProperties.$t = t;
    app.config.globalProperties.$page = { props: { locale } };
    app.component('StatusBadge', { props: ['status', 'errorType'], setup: props => () => h('span', t(props.errorType === 'access_restricted' ? 'Protected' : statusLabels[props.status])) });
    app.component('Link', { props: ['href'], setup: (props, { slots }) => () => h('a', { href: props.href }, slots.default?.()) });
    return { html: await renderToString(app), t };
}

for (const locale of ['en', 'ar', 'fr']) {
    test(`${locale}: history renders actual status and HTTP transitions`, async () => {
        const { html, t } = await renderHistory(locale, [record]);
        assert.ok(html.includes(t('Status changed: :previous → :current', { previous: t('Down'), current: t('Up') })));
        assert.ok(html.includes(t('HTTP changed: :previous → :current', { previous: 503, current: 200 })));
        assert.ok(html.includes(url));
        assert.ok(!html.includes('undefined'));
    });
    test(`${locale}: protected access and redirect metadata are explained`, async () => {
        const protectedCheck = { ...record, status: 'unknown', error_type: 'access_restricted', http_status: 403,
            final_url: 'https://example.com/catalog', redirect_count: 1,
            comparison: { ...record.comparison, reason: 'first_check' } };
        const { html, t } = await renderHistory(locale, [protectedCheck]);
        assert.ok(html.includes(t('Protected')));
        assert.ok(html.includes(t(errorLabels.access_restricted)));
        assert.ok(html.includes('https://example.com/catalog'));
        assert.ok(html.includes(t('Redirects: :count', { count: 1 })));
    });
}

test('empty history explains why queued work is not a result', async () => {
    const { html, t } = await renderHistory('ar', []);
    assert.ok(html.includes(t('No checks recorded yet.')));
    assert.ok(html.includes(t('Start the worker, request a check, then refresh the results. A queued request alone does not create a history entry.')));
});

test('legacy records display an honest missing-URL notice', async () => {
    const legacy = { ...record, target_url: null, comparison: { ...record.comparison, reason: 'unknown_target' } };
    const { html, t } = await renderHistory('fr', [legacy]);
    assert.ok(html.includes(t('The URL was not recorded for this older check.')));
    assert.ok(html.includes(t(reasons.unknown_target)));
    assert.ok(!html.includes(t('HTTP changed: :previous → :current', { previous: 503, current: 200 })));
});

test('successful timing improvements have a localized explanation', async () => {
    const improved = { ...record, comparison: { ...record.comparison, previous_status: 'up', previous_http_status: 200,
        previous_error_type: null, status_changed: false, http_status_changed: false, error_changed: false, response_time_delta_ms: -60 } };
    const { html, t } = await renderHistory('ar', [improved]);
    assert.ok(html.includes(t('Response faster by :value ms.', { value: 60 })));
});
