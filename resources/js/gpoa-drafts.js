const THIRTY_DAYS_MS = 30 * 24 * 60 * 60 * 1000;
const DAY_MS = 24 * 60 * 60 * 1000;

export function gpoaDraftStorageKey(userId, schoolYear, gpoaId = null) {
    return gpoaId
        ? `gpoa-draft:${userId}:gpoa:${gpoaId}:${schoolYear}`
        : `gpoa-draft:${userId}:${schoolYear}`;
}

export function formatDraftSavedTime(savedAt, now = Date.now()) {
    const timestamp = Number(savedAt);
    if (!Number.isFinite(timestamp)) return 'just now';

    const elapsedMilliseconds = Math.max(0, now - timestamp);
    const minutes = Math.floor(elapsedMilliseconds / 60000);
    if (minutes < 1) return 'just now';
    if (minutes < 60) return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;

    const hours = Math.floor(elapsedMilliseconds / 3600000);
    if (hours < 24) return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;

    const currentDay = new Date(now);
    currentDay.setHours(0, 0, 0, 0);
    const savedDay = new Date(timestamp);
    savedDay.setHours(0, 0, 0, 0);
    if (Math.floor((currentDay - savedDay) / DAY_MS) === 1) return 'yesterday';

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(timestamp));
}

export function isDraftStale(savedAt, now = Date.now()) {
    const timestamp = Number(savedAt);
    return !Number.isFinite(timestamp) || now - timestamp > THIRTY_DAYS_MS;
}

export function draftMatchesOwner(draft, userId, organizationId) {
    return String(draft?.userId ?? '') === String(userId ?? '')
        && String(draft?.organizationId ?? '') === String(organizationId ?? '');
}
