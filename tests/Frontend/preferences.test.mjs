import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import { translate } from '../../resources/js/translation.js';
import { resolveTheme, setTheme, theme } from '../../resources/js/appearance.js';

const english = JSON.parse(readFileSync(new URL('../../lang/en.json', import.meta.url), 'utf8'));
const arabic = JSON.parse(readFileSync(new URL('../../lang/ar.json', import.meta.url), 'utf8'));
const french = JSON.parse(readFileSync(new URL('../../lang/fr.json', import.meta.url), 'utf8'));

for (const [locale, catalog] of Object.entries({ en: english, ar: arabic, fr: french })) {
    test(`${locale}: catalogs cover the same interface`, () => {
        assert.deepEqual(Object.keys(catalog), Object.keys(english));
        assert.ok(Object.values(catalog).every((value) => typeof value === 'string' && value.length > 0));
    });
}

test('translation follows the provided catalog and safely substitutes literal parameters', () => {
    assert.equal(translate(arabic, 'Sign in'), 'تسجيل الدخول');
    assert.equal(translate(french, 'Sign in'), 'Se connecter');
    assert.equal(translate(arabic, 'Actions for :name', { name: '$& :other <tag>' }), 'إجراءات $& :other <tag>');
    assert.equal(translate({}, 'Missing :value'), 'Missing :value');
    assert.equal(translate({ ':a :ab :a': ':a :ab :a' }, ':a :ab :a', { a: 0, ab: 42 }), '0 42 0');
});

test('theme selection honors valid saved choices, otherwise the device preference', () => {
    assert.equal(resolveTheme('dark', false), 'dark');
    assert.equal(resolveTheme('light', true), 'light');
    assert.equal(resolveTheme(null, true), 'dark');
    assert.equal(resolveTheme('invalid', false), 'light');
});

test('theme switching updates the document and persists the preference', () => {
    const saved = [];
    const root = { dataset: {}, style: {} };
    const environment = { document: { documentElement: root }, localStorage: { setItem: (...args) => saved.push(args) } };
    setTheme('light', environment);
    assert.equal(root.dataset.theme, 'light');
    assert.equal(root.style.colorScheme, 'light');
    assert.equal(theme.value, 'light');
    assert.deepEqual(saved, [['devpulse-theme', 'light']]);
    setTheme('invalid', environment);
    assert.equal(root.dataset.theme, 'light');
    assert.equal(saved.length, 1);
    setTheme('dark', environment);
    assert.equal(root.dataset.theme, 'dark');
});

test('blocked storage does not prevent changing the appearance', () => {
    const root = { dataset: {}, style: {} };
    setTheme('light', { document: { documentElement: root }, get localStorage() { throw new Error('Storage unavailable'); } });
    assert.equal(root.dataset.theme, 'light');
});

test('the first-paint script uses the saved theme before assets and tolerates blocked storage', () => {
    const blade = readFileSync(new URL('../../resources/views/app.blade.php', import.meta.url), 'utf8');
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1];
    assert.ok(blade.indexOf('<script>') < blade.indexOf('@vite'));
    for (const [saved, systemDark, expected] of [['light', true, 'light'], ['dark', false, 'dark'], [null, true, 'dark'], ['invalid', false, 'light']]) {
        const root = { dataset: {}, style: {} };
        runInNewContext(script, { document: { documentElement: root }, localStorage: { getItem: () => saved }, matchMedia: () => ({ matches: systemDark }) });
        assert.equal(root.dataset.theme, expected);
        assert.equal(root.style.colorScheme, expected);
    }
    const root = { dataset: {}, style: {} };
    runInNewContext(script, { document: { documentElement: root }, get localStorage() { throw new Error('Blocked'); }, matchMedia: () => ({ matches: true }) });
    assert.equal(root.dataset.theme, 'dark');
});
