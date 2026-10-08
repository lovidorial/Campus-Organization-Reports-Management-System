<script>
window.gpoaImportReview = () => ({
    reviewOpen: false,
    reviewRows: [],
    reviewSkippedRows: [],
    reviewWarnings: [],
    reviewNeedsOnly: false,
    reviewExpandedIndex: null,
    reviewMode: 'replace',
    reviewScope: 'all',
    reviewApplyError: '',
    hasDraft: false,
    savedDraft: null,
    draftSaveError: '',
    draftTooLarge: false,
    errorSummaryOpen: false,
    clientValidationErrors: {},
    draftTimer: null,
    discardConfirmOpen: false,
    pdfMustReupload: false,
    get draftStorageKey() {
        const schoolYear = this.$root.closest('form')?.elements.namedItem('school_year')?.value || this.schoolYear || 'unspecified';
        return window.gpoaDraftTools.gpoaDraftStorageKey(this.draftUserId, schoolYear, this.editingGpoaId);
    },
    get draftSavedLabel() {
        if (!this.savedDraft?.savedAt) return '';
        return `saved ${window.gpoaDraftTools.formatDraftSavedTime(this.savedDraft.savedAt)}`;
    },
    init() {
        const form = this.$root.closest('form');
        form?.addEventListener('input', () => this.queueDraftSave());
        form?.addEventListener('change', event => {
            this.queueDraftSave();
            if (event.target?.name === 'school_year') this.checkSavedDraft();
        });
        form?.elements.namedItem('document_path')?.addEventListener('change', event => {
            if (event.target.files?.length) this.pdfMustReupload = false;
        });
        this.$watch('activities', () => this.queueDraftSave());
        this.$nextTick(() => {
            for (const item of this.activityErrorItems) {
                if (this.activities[item.index]) this.activities[item.index].detailsOpen = true;
            }
            if (this.activityErrorItems.length) this.errorSummaryOpen = true;
            this.checkSavedDraft();
        });
    },
    get reviewFoundCount() { return this.reviewRows.length + this.reviewSkippedRows.length; },
    get reviewHasExistingActivities() { return this.activities.some(activity => this.activityHasData(activity)); },
    get reviewAttentionCount() { return this.reviewRows.filter(activity => this.reviewNeedsAttention(activity)).length; },
    get reviewCandidateRows() {
        return this.reviewScope === 'ok' ? this.reviewRows.filter(activity => !this.reviewNeedsAttention(activity)) : this.reviewRows;
    },
    get reviewSlots() {
        return this.reviewMode === 'append' ? Math.max(0, this.maxActivities - this.activities.length) : this.maxActivities;
    },
    get reviewImportCount() { return Math.min(this.reviewCandidateRows.length, this.reviewSlots); },
    get reviewNotFitCount() { return this.reviewSkippedRows.length + Math.max(0, this.reviewCandidateRows.length - this.reviewSlots); },
    get visibleReviewRows() {
        return this.reviewNeedsOnly ? this.reviewRows.filter(activity => this.reviewNeedsAttention(activity)) : this.reviewRows;
    },
    activityComplete(activity) {
        const validDate = activity.time_frame === 'exact_date' ? Boolean(activity.date)
            : activity.time_frame === 'date_range' ? Boolean(activity.date && activity.end_date)
            : activity.time_frame === 'month_only' ? Boolean(activity.date) : false;
        return Boolean(activity.title?.trim() && validDate && activity.venue?.trim() && activity.sdgs?.length
            && activity.objectives?.trim() && activity.expected_outcome?.trim() && activity.plan_key_strategy?.trim()
            && activity.target_participants?.trim() && activity.person_in_charge?.trim()
            && activity.estimated_budget !== '' && activity.estimated_budget !== null);
    },
    get activityErrorItems() {
        const items = [];
        const errors = { ...this.validationErrors };
        for (const [key, messages] of Object.entries(this.clientValidationErrors)) {
            errors[key] = [...(errors[key] || []), ...messages];
        }
        for (const [key, messages] of Object.entries(errors)) {
            const match = key.match(/^planned_activities\.(\d+)\.([^.]+)/);
            if (!match) continue;
            const index = Number(match[1]);
            const message = Array.isArray(messages) ? messages[0] : messages;
            items.push({ index, field: match[2], message: String(message).startsWith(`Activity ${index + 1}:`) ? message : `Activity ${index + 1}: ${message}` });
        }
        return items;
    },
    get errorActivityCount() {
        return new Set(this.activityErrorItems.map(item => item.index)).size;
    },
    focusActivityError(item) {
        if (this.activities[item.index]) this.activities[item.index].detailsOpen = true;
        this.$nextTick(() => {
            const name = item.field === 'sdgs'
                ? `planned_activities[${item.index}][sdgs][]`
                : `planned_activities[${item.index}][${item.field}]`;
            const field = Array.from(document.getElementsByName(name)).find(element => element.offsetParent !== null);
            const card = this.$root.querySelectorAll('[data-activity-card]')[item.index];
            (field || card)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field?.focus({ preventScroll: true });
        });
    },
    handleSubmit(event) {
        const form = event.target;
        this.clientValidationErrors = {};
        const nonActivityInvalidField = Array.from(form.querySelectorAll('[required]'))
            .find(field => !field.name.startsWith('planned_activities[') && !field.checkValidity());
        if (nonActivityInvalidField) {
            nonActivityInvalidField.reportValidity();
            return;
        }
        const requiredFields = [
            ['title', 'Title'], ['venue', 'Venue'], ['objectives', 'Objectives'],
            ['expected_outcome', 'Expected Outcome'], ['plan_key_strategy', 'Delivery Strategy'],
            ['target_participants', 'Target Participants'], ['person_in_charge', 'Person in Charge'],
        ];
        this.activities.forEach((activity, index) => {
            if (!this.activityHasData(activity)) return;
            const addError = (field, label) => {
                const key = `planned_activities.${index}.${field}`;
                (this.clientValidationErrors[key] ||= []).push(`Activity ${index + 1}: ${label} is required.`);
            };
            for (const [field, label] of requiredFields) if (!String(activity[field] ?? '').trim()) addError(field, label);
            if (!activity.time_frame) addError('time_frame', 'Date or time frame');
            if (activity.time_frame === 'exact_date' && !activity.date) addError('date', 'Date');
            if (activity.time_frame === 'date_range' && !activity.date) addError('date', 'Start date');
            if (activity.time_frame === 'date_range' && !activity.end_date) addError('end_date', 'End date');
            if (activity.time_frame === 'month_only' && !activity.date) addError('date', 'Month');
            if (!(activity.sdgs || []).length) addError('sdgs', 'Select at least one SDG');
            if (activity.estimated_budget === '' || activity.estimated_budget === null || !Number.isFinite(Number(activity.estimated_budget))) addError('estimated_budget', 'Estimated Budget is required and must be numeric');
        });
        this.errorSummaryOpen = this.activityErrorItems.length > 0;
        if (this.errorSummaryOpen) {
            this.focusActivityError(this.activityErrorItems[0]);
            return;
        }
        form.submit();
    },
    checkSavedDraft() {
        this.hasDraft = false;
        this.savedDraft = null;
        try {
            const key = this.draftStorageKey;
            const serialized = localStorage.getItem(key);
            if (!serialized) return;
            const draft = JSON.parse(serialized);
            if (!draft || !Array.isArray(draft.activities) || !draft.fields || typeof draft.fields !== 'object') {
                localStorage.removeItem(key);
                return;
            }
            const timestamp = Number(draft?.savedAt);
            const stale = window.gpoaDraftTools.isDraftStale(timestamp);
            const belongsToCurrentOrganization = window.gpoaDraftTools.draftMatchesOwner(draft, this.draftUserId, this.draftOrganizationId);

            if (stale) {
                localStorage.removeItem(key);
                return;
            }
            if (belongsToCurrentOrganization) {
                this.savedDraft = draft;
                this.hasDraft = true;
            }
        } catch (error) {
            localStorage.removeItem(this.draftStorageKey);
        }
    },
    queueDraftSave() {
        clearTimeout(this.draftTimer);
        this.draftTimer = setTimeout(() => this.saveDraft(), 3000);
    },
    saveDraft() {
        const form = this.$root.closest('form');
        if (!form) return;
        const fields = {};
        for (const name of ['term', 'school_year', 'prepared_by']) {
            const element = form.elements.namedItem(name);
            if (!element) continue;
            fields[name] = element.value;
        }
        const activityFields = [
            'id', 'title', 'time_frame', 'date', 'end_date', 'start_time', 'end_time', 'venue', 'category', 'sdgs',
            'objectives', 'expected_outcome', 'plan_key_strategy', 'target_participants', 'person_in_charge',
            'facilities_materials', 'estimated_budget', 'source_of_funds',
        ];
        const draft = {
            userId: this.draftUserId,
            organizationId: this.draftOrganizationId,
            savedAt: Date.now(),
            fields,
            activities: this.activities.map(activity => Object.fromEntries(
                activityFields.map(field => [field, field === 'sdgs'
                    ? (Array.isArray(activity.sdgs) ? activity.sdgs : [])
                    : (activity[field] ?? (field === 'id' ? null : ''))])
            )),
        };
        const key = this.draftStorageKey;
        const serialized = JSON.stringify(draft);
        try {
            localStorage.setItem(key, serialized);
            this.draftSaveError = '';
            this.draftTooLarge = false;
            this.savedDraft = draft;
        } catch (error) {
            console.warn(error.name, error.message);
            if (error?.name !== 'QuotaExceededError') {
                this.draftTooLarge = false;
                this.draftSaveError = 'The draft could not be saved in this browser.';
                return;
            }

            try {
                this.removeOldDraftsForQuota(key);
            } catch (cleanupError) {
                console.warn(cleanupError.name, cleanupError.message);
            }
            try {
                localStorage.setItem(key, serialized);
                this.draftSaveError = '';
                this.draftTooLarge = false;
                this.savedDraft = draft;
            } catch (retryError) {
                console.warn(retryError.name, retryError.message);
                this.draftSaveError = '';
                this.draftTooLarge = retryError?.name === 'QuotaExceededError';
                if (!this.draftTooLarge) this.draftSaveError = 'The draft could not be saved in this browser.';
            }
        }
    },
    removeOldDraftsForQuota(currentKey) {
        const prefix = 'gpoa-draft:';
        const currentSchoolYear = String(this.$root.closest('form')?.elements.namedItem('school_year')?.value || this.schoolYear || '');

        for (let index = localStorage.length - 1; index >= 0; index--) {
            const key = localStorage.key(index);
            if (!key?.startsWith(prefix) || key === currentKey) continue;

            const keyParts = key.split(':');
            const ownerId = keyParts[1] || '';
            const savedSchoolYear = keyParts[keyParts.length - 1] || '';
            if (ownerId !== String(this.draftUserId) || (savedSchoolYear && currentSchoolYear && savedSchoolYear < currentSchoolYear)) {
                localStorage.removeItem(key);
            }
        }
    },
    resumeDraft() {
        const form = this.$root.closest('form');
        for (const [name, value] of Object.entries(this.savedDraft?.fields || {})) {
            const element = form?.elements.namedItem(name);
            if (!element || element.type === 'file') continue;
            if (element.type === 'checkbox') element.checked = Boolean(value);
            else element.value = value ?? '';
        }
        this.activities = (this.savedDraft?.activities || []).slice(0, this.maxActivities).map((activity, index) => ({
            ...activity, sdgs: Array.isArray(activity.sdgs) ? activity.sdgs : [], detailsOpen: false,
            _key: activity._key || `draft-${Date.now()}-${index}`,
        }));
        this.pdfMustReupload = true;
        this.hasDraft = false;
        this.queueDraftSave();
    },
    discardDraft() {
        localStorage.removeItem(this.draftStorageKey);
        this.hasDraft = false;
        this.savedDraft = null;
        this.discardConfirmOpen = false;
    },
    cancelDiscardDraft() {
        this.discardConfirmOpen = false;
    },
    activityHasData(activity) {
        return Boolean(activity.title?.trim() || activity.date || activity.venue?.trim() || activity.category || activity.source_of_funds?.trim()
            || activity.objectives?.trim() || activity.expected_outcome?.trim() || activity.plan_key_strategy?.trim()
            || activity.target_participants?.trim() || activity.person_in_charge?.trim() || activity.facilities_materials?.trim()
            || (activity.sdgs || []).length || activity.estimated_budget !== '' && activity.estimated_budget !== null);
    },
    activitySummaryDate(activity) {
        if (activity.time_frame === 'date_range') return `${activity.date || 'Date missing'} – ${activity.end_date || 'End date missing'}`;
        if (activity.time_frame === 'month_only') return activity.date || 'Month missing';
        return activity.date || 'Date missing';
    },
    expandAllActivities() {
        this.activities.forEach(activity => { activity.detailsOpen = true; });
    },
    collapseAllActivities() {
        this.activities.forEach(activity => { activity.detailsOpen = false; });
    },
    reviewNeedsAttention(activity) {
        const validDate = activity.time_frame === 'exact_date' ? Boolean(activity.date)
            : activity.time_frame === 'date_range' ? Boolean(activity.date && activity.end_date)
            : activity.time_frame === 'month_only' ? Boolean(activity.date) : false;
        return (activity.importWarnings || []).length > 0
            || !activity.title?.trim() || !validDate || !activity.venue?.trim() || !(activity.sdgs || []).length
            || !activity.objectives?.trim() || !activity.expected_outcome?.trim() || !activity.plan_key_strategy?.trim()
            || !activity.target_participants?.trim() || !activity.person_in_charge?.trim()
            || activity.estimated_budget === '' || activity.estimated_budget === null || !Number.isFinite(Number(activity.estimated_budget));
    },
    reviewDateLabel(activity) {
        if (activity.time_frame === 'date_range') return `${activity.date || 'Date missing'} – ${activity.end_date || 'End date missing'}`;
        if (activity.time_frame === 'month_only') return activity.date || 'Month missing';
        return activity.date || 'Date missing';
    },
    mapImportedActivity(row, index) {
        return {
            id: null,
            _key: `import-${Date.now()}-${index}-${Math.random()}`,
            title: row.title || '',
            time_frame: ({ exact: 'exact_date', range: 'date_range', month: 'month_only' })[row.time_frame] || '',
            date: row.time_frame === 'month' && row.date ? row.date.slice(0, 7) : (row.date || ''),
            end_date: row.end_date || '', start_time: row.start_time || '', end_time: row.end_time || '',
            venue: row.venue || '', category: row.category || '', sdgs: Array.isArray(row.sdgs) ? row.sdgs.map(Number) : [],
            objectives: row.objectives || '', expected_outcome: row.expected_outcome || '', plan_key_strategy: row.plan_key_strategy || '',
            target_participants: row.target_participants || '', person_in_charge: row.person_in_charge || '',
            facilities_materials: row.facilities_materials || '', estimated_budget: row.estimated_budget ?? '',
            source_of_funds: row.source_of_funds || '', importWarnings: row.warnings || [], detailsOpen: false,
        };
    },
    stageImport(result) {
        this.reviewRows = result.rows.map((row, index) => this.mapImportedActivity(row, index));
        this.reviewWarnings = result.warnings || [];
        this.reviewSkippedRows = result.skipped_rows || [];
        this.reviewNeedsOnly = false;
        this.reviewExpandedIndex = null;
        this.reviewMode = 'replace';
        this.reviewScope = 'all';
        this.reviewApplyError = '';
        const repeatedTitles = (result.repeated_titles || []).map(item => `${item.title} x${item.count}`);
        this.repeatedTitleHint = repeatedTitles.length ? `Repeated titles (check if intentional): ${repeatedTitles.join('; ')}` : '';
        this.reviewOpen = true;
    },
    cancelImportReview() {
        this.reviewOpen = false;
        this.reviewRows = [];
        this.reviewSkippedRows = [];
    },
    confirmImportReview(scope) {
        this.reviewScope = scope;
        const rows = this.reviewCandidateRows.slice(0, this.reviewSlots);
        if (!rows.length) {
            this.reviewApplyError = this.reviewCandidateRows.length
                ? 'No selected rows fit the remaining activity slots.'
                : 'There are no rows selected for import.';
            return;
        }
        if (this.reviewMode === 'replace') this.activities = [];
        this.activities.push(...rows.map((activity, index) => ({ ...activity, _key: `reviewed-${Date.now()}-${index}-${Math.random()}` })));
        this.importSummary = `Imported ${rows.length} activities`;
        this.importNotes = this.reviewWarnings;
        this.skippedRows = this.reviewSkippedRows;
        this.reviewOpen = false;
        this.reviewRows = [];
    },
    fieldError(index, field) {
        return this.validationErrors[`planned_activities.${index}.${field}`]?.[0]
            || this.clientValidationErrors[`planned_activities.${index}.${field}`]?.[0] || '';
    },
    detailsHaveErrors(index) {
        return this.detailFields.some(field => this.fieldError(index, field)) || Boolean(this.fieldError(index, 'sdgs'));
    },
    copyDetailsFrom(index, sourceIndex) {
        if (sourceIndex === '' || sourceIndex === null) return;
        const source = this.activities[Number(sourceIndex)];
        if (!source) return;
        ['title', 'time_frame', 'date', 'end_date', 'start_time', 'end_time', 'venue', 'category', ...this.detailFields]
            .forEach(field => { this.activities[index][field] = source[field] ?? ''; });
        this.activities[index].sdgs = [...(source.sdgs || [])];
    },
    copyFieldToAll(index, field) {
        const value = this.activities[index][field];
        this.activities.forEach((activity, activityIndex) => { if (activityIndex !== index) activity[field] = value; });
    },
    copyCommonDetailsToAll(index) {
        ['target_participants', 'person_in_charge', 'source_of_funds', 'facilities_materials'].forEach(field => this.copyFieldToAll(index, field));
    },
    removeActivity(index) {
        this.activities.splice(index, 1);
    },
});
</script>
