import test from 'node:test';
import assert from 'node:assert/strict';
import { draftMatchesOwner, formatDraftSavedTime, gpoaDraftStorageKey, isDraftStale } from '../../resources/js/gpoa-drafts.js';

const minute = 60 * 1000;
const hour = 60 * minute;
const day = 24 * hour;
const now = Date.UTC(2026, 9, 7, 12);

test('formats recent save ages with singular and plural units', () => {
    assert.equal(formatDraftSavedTime(now - 30 * 1000, now), 'just now');
    assert.equal(formatDraftSavedTime(now - 5 * minute, now), '5 minutes ago');
    assert.equal(formatDraftSavedTime(now - 1 * hour, now), '1 hour ago');
    assert.equal(formatDraftSavedTime(now - 2 * hour, now), '2 hours ago');
    assert.equal(formatDraftSavedTime(now - 248 * minute, now), '4 hours ago');
    assert.equal(formatDraftSavedTime(now - 25 * hour, now), 'yesterday');
    assert.equal(formatDraftSavedTime(now - 48 * hour, now), 'Oct 5, 2026');
    assert.equal(formatDraftSavedTime(Date.UTC(2026, 9, 3, 12), now), 'Oct 3, 2026');
});

test('isolates drafts by user and organization', () => {
    const draft = { userId: 'user-a', organizationId: 'org-a' };
    assert.equal(draftMatchesOwner(draft, 'user-a', 'org-a'), true);
    assert.equal(draftMatchesOwner(draft, 'user-b', 'org-a'), false);
    assert.equal(draftMatchesOwner(draft, 'user-a', 'org-b'), false);
    assert.notEqual(gpoaDraftStorageKey('user-a', '2026-2027'), gpoaDraftStorageKey('user-b', '2026-2027'));
    assert.equal(gpoaDraftStorageKey('user-a', '2026-2027', '42'), 'gpoa-draft:user-a:gpoa:42:2026-2027');
});

test('treats drafts older than 30 days as stale', () => {
    assert.equal(isDraftStale(now - 30 * day, now), false);
    assert.equal(isDraftStale(now - 30 * day - 1, now), true);
    assert.equal(isDraftStale('corrupt', now), true);
});
