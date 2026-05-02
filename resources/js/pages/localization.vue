<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="brds-3" style="width:99%;border: 1px solid #e7e7e7;min-height:490px">
        <div class="d-flex w-100" style="height:55px;background-color:#EEEEEE;border-top-right-radius:inherit;border-top-left-radius:inherit;">
            <p class="ms-4" style="font-size:26px;font-weight:bold;margin-top:7px;">Localization</p>
        </div>
        <div class="p-3">
            <p class="text-muted small">Manage app languages (mobile picker + API validation). Bulk translate uses Google Cloud Translation. Select <strong>multiple target languages</strong> below; by default the job is <strong>queued</strong> (set <code>QUEUE_CONNECTION=database</code> in <code>.env</code>, run <code>php artisan migrate</code> for the jobs table, then <code>php artisan queue:work</code>). Check <code>storage/logs/laravel.log</code> for per-locale progress. Use <strong>Run in browser (slow)</strong> only for small tests. <strong>API content</strong> (meals, exercises, programs, workouts) uses <code>locale_translations</code> and follows the user&rsquo;s language after login. Flutter <strong>static UI</strong> ships with placeholder ARBs for many locales (English text until you replace ARBs or add real translations).</p>

            <h5 class="mt-4">Flutter UI strings (for translation / MT)</h5>
            <p class="text-muted small mb-2">Download all <code>app_en.arb</code> message keys as JSON (flat map). Default ARB path: sibling repo <code>fitnesswithdina_mobile/lib/l10n/app_en.arb</code>; override with <code>FLUTTER_EN_ARB_PATH</code> in API <code>.env</code> if needed. After translating, fold values into <code>lib/l10n/app_<em>locale</em>.arb</code> and run <code>flutter gen-l10n</code>.</p>
            <button type="button" class="btn btn-outline-primary btn-sm" :disabled="exportUiLoading" @click="exportEnUiStringsJson">{{ exportUiLoading ? 'Preparing…' : 'Download en UI strings (JSON)' }}</button>
            <p v-if="exportUiHint" class="small text-muted mt-2 mb-0">{{ exportUiHint }}</p>

            <h5 class="mt-4">Add language</h5>
            <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                <div>
                    <label class="form-label mb-0">Code (ISO, e.g. fr)</label>
                    <input v-model="newLang.code" class="form-control form-control-sm" style="width:120px" maxlength="16" />
                </div>
                <div>
                    <label class="form-label mb-0">English label</label>
                    <input v-model="newLang.label" class="form-control form-control-sm" style="width:180px" />
                </div>
                <div>
                    <label class="form-label mb-0">Native label</label>
                    <input v-model="newLang.native_label" class="form-control form-control-sm" style="width:180px" />
                </div>
                <div>
                    <label class="form-label mb-0">Sort</label>
                    <input v-model.number="newLang.sort_order" type="number" class="form-control form-control-sm" style="width:80px" />
                </div>
                <button class="btn btn-primary btn-sm" @click="saveLanguage">Save</button>
            </div>

            <h5>Languages</h5>
            <table class="table table-sm table-bordered w-100 bg-white">
                <thead><tr><th>Code</th><th>Label</th><th>Native</th><th>Active</th><th>Sort</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="row in languages" :key="row.id">
                        <td>{{ row.code }}</td>
                        <td><input v-model="row.label" class="form-control form-control-sm" /></td>
                        <td><input v-model="row.native_label" class="form-control form-control-sm" /></td>
                        <td><input type="checkbox" v-model="row.is_active" /></td>
                        <td><input type="number" v-model.number="row.sort_order" class="form-control form-control-sm" style="width:70px" /></td>
                        <td><button class="btn btn-outline-secondary btn-sm" @click="updateLanguage(row)">Update</button></td>
                    </tr>
                </tbody>
            </table>

            <h5 class="mt-4">Bulk translate content</h5>
            <div class="d-flex flex-wrap gap-2 align-items-end mb-2">
                <div>
                    <label class="form-label mb-0">Source locale</label>
                    <input v-model="bulk.source_locale" class="form-control form-control-sm" style="width:100px" />
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="bulk.doMeals" id="bm" />
                    <label class="form-check-label" for="bm">Meals</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="bulk.doExercises" id="be" />
                    <label class="form-check-label" for="be">Exercises</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="bulk.doPrograms" id="bp" />
                    <label class="form-check-label" for="bp">Programs</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" v-model="bulk.doWorkouts" id="bw" />
                    <label class="form-check-label" for="bw">Workouts</label>
                </div>
                <div class="form-check ms-2">
                    <input class="form-check-input" type="checkbox" v-model="bulk.runSync" id="bsync" />
                    <label class="form-check-label" for="bsync" title="Runs everything in this browser request; may timeout on many languages.">Run in browser (slow)</label>
                </div>
            </div>
            <div class="mb-2">
                <span class="small fw-bold me-2">Target languages</span>
                <button type="button" class="btn btn-link btn-sm p-0 me-2" @click="selectAllBulkTargets">All</button>
                <button type="button" class="btn btn-link btn-sm p-0" @click="clearBulkTargets">None</button>
            </div>
            <div class="d-flex flex-wrap gap-3 mb-2 p-2 brds-3" style="background:#fafafa;border:1px solid #ddd;">
                <div v-for="l in bulkTargetChoices" :key="'bt-'+l.id" class="form-check">
                    <input class="form-check-input" type="checkbox" :id="'bt-'+l.id" :value="l.code" v-model="bulk.target_locales" />
                    <label class="form-check-label" :for="'bt-'+l.id">{{ l.code }} — {{ l.native_label }}</label>
                </div>
                <span v-if="!bulkTargetChoices.length" class="text-muted small">Add languages above (targets must differ from source).</span>
            </div>
            <div>
                <button class="btn btn-warning btn-sm" :disabled="bulkRunning" @click="runBulk">{{ bulkRunning ? 'Submitting…' : (bulk.runSync ? 'Run bulk translate (browser)' : 'Queue bulk translate') }}</button>
            </div>
            <p v-if="bulkResult" class="small mt-2 mb-0">{{ bulkResult }}</p>

            <h5 class="mt-4">Edit translations (manual override)</h5>
            <div class="row g-2">
                <div class="col-md-6">
                    <p class="mb-1 small fw-bold">Meal</p>
                    <input v-model="mealEdit.id" class="form-control form-control-sm mb-1" placeholder="Meal ID" />
                    <select v-model="mealEdit.locale" class="form-select form-select-sm mb-1">
                        <option value="">Locale…</option>
                        <option v-for="l in languages" :key="'m-'+l.id" :value="l.code">{{ l.code }}</option>
                    </select>
                    <textarea v-model="mealEdit.json" class="form-control form-control-sm font-monospace" rows="6" placeholder='{"name":"...","ingredients":"...","directions":"..."}'></textarea>
                    <button class="btn btn-primary btn-sm mt-1" @click="saveMealTranslations">Save meal translations</button>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 small fw-bold">Exercise</p>
                    <input v-model="exEdit.id" class="form-control form-control-sm mb-1" placeholder="Exercise ID" />
                    <select v-model="exEdit.locale" class="form-select form-select-sm mb-1">
                        <option value="">Locale…</option>
                        <option v-for="l in languages" :key="'e-'+l.id" :value="l.code">{{ l.code }}</option>
                    </select>
                    <textarea v-model="exEdit.json" class="form-control form-control-sm font-monospace" rows="6" placeholder='{"title":"...","instructions":"..."}'></textarea>
                    <button class="btn btn-primary btn-sm mt-1" @click="saveExerciseTranslations">Save exercise translations</button>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 small fw-bold">Program</p>
                    <input v-model="progEdit.id" class="form-control form-control-sm mb-1" placeholder="Program ID" />
                    <select v-model="progEdit.locale" class="form-select form-select-sm mb-1">
                        <option value="">Locale…</option>
                        <option v-for="l in languages" :key="'p-'+l.id" :value="l.code">{{ l.code }}</option>
                    </select>
                    <textarea v-model="progEdit.json" class="form-control form-control-sm font-monospace" rows="6" placeholder='{"title":"...","discription":"..."}'></textarea>
                    <button class="btn btn-primary btn-sm mt-1" @click="saveProgramTranslations">Save program translations</button>
                </div>
                <div class="col-md-6">
                    <p class="mb-1 small fw-bold">Workout (master)</p>
                    <input v-model="wrkEdit.id" class="form-control form-control-sm mb-1" placeholder="Workout ID" />
                    <select v-model="wrkEdit.locale" class="form-select form-select-sm mb-1">
                        <option value="">Locale…</option>
                        <option v-for="l in languages" :key="'w-'+l.id" :value="l.code">{{ l.code }}</option>
                    </select>
                    <textarea v-model="wrkEdit.json" class="form-control form-control-sm font-monospace" rows="6" placeholder='{"title":"...","instructions":"...","daily_summary":"..."}'></textarea>
                    <button class="btn btn-primary btn-sm mt-1" @click="saveWorkoutTranslations">Save workout translations</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
export default {
    components: { Loader, Inform },
    data() {
        return {
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            languages: [],
            newLang: { code: '', label: '', native_label: '', sort_order: 99, is_active: true },
            bulk: { source_locale: 'en', target_locales: [], doMeals: true, doExercises: true, doPrograms: true, doWorkouts: true, runSync: false },
            bulkRunning: false,
            bulkResult: '',
            mealEdit: { id: '', locale: '', json: '' },
            exEdit: { id: '', locale: '', json: '' },
            progEdit: { id: '', locale: '', json: '' },
            wrkEdit: { id: '', locale: '', json: '' },
            exportUiLoading: false,
            exportUiHint: '',
        };
    },
    computed: {
        bulkTargetChoices() {
            const s = (this.bulk.source_locale || 'en').toLowerCase();
            return this.languages.filter((l) => (l.code || '').toLowerCase() !== s);
        },
    },
    mounted() {
        this.loadLanguages();
    },
    methods: {
        selectAllBulkTargets() {
            this.bulk.target_locales = this.bulkTargetChoices.map((l) => l.code);
        },
        clearBulkTargets() {
            this.bulk.target_locales = [];
        },
        authHeaders() {
            return { Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token') };
        },
        showInform(title, detail) {
            this.modalTitle = title;
            this.modalDetail = detail;
            this.informModal = true;
        },
        acknowledged() {
            this.informModal = false;
        },
        async loadLanguages() {
            this.pageLoading = true;
            this.loaderText = 'Loading languages';
            try {
                const res = await axios.get(config.baseApiUrl + 'languages-admin', { headers: this.authHeaders() });
                if (res.data.status) this.languages = res.data.data;
                else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.pageLoading = false;
            }
        },
        async exportEnUiStringsJson() {
            this.exportUiHint = '';
            this.exportUiLoading = true;
            try {
                const res = await axios.get(config.baseApiUrl + 'localization/export-en-ui-strings', { headers: this.authHeaders() });
                if (!res.data.status) {
                    this.showInform('Export failed', res.data.message || 'Unknown error');
                    return;
                }
                const payload = { meta: res.data.meta, strings: res.data.strings };
                const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'flutter_app_en_ui_strings.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                const mc = res.data.meta && res.data.meta.message_count;
                const sf = res.data.meta && res.data.meta.source_file;
                this.exportUiHint = mc != null && sf ? `Downloaded ${mc} keys from ${sf}.` : 'Download complete.';
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.exportUiLoading = false;
            }
        },
        async saveLanguage() {
            if (!this.newLang.code || !this.newLang.label || !this.newLang.native_label) {
                this.showInform('Validation', 'Code, English label and native label are required.');
                return;
            }
            try {
                const res = await axios.post(config.baseApiUrl + 'languages-admin', this.newLang, { headers: this.authHeaders() });
                if (res.data.status) {
                    this.showInform('OK', 'Language added.');
                    this.newLang = { code: '', label: '', native_label: '', sort_order: 99, is_active: true };
                    await this.loadLanguages();
                } else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
        async updateLanguage(row) {
            try {
                const res = await axios.post(config.baseApiUrl + 'languages-admin/' + row.id, {
                    label: row.label,
                    native_label: row.native_label,
                    is_active: row.is_active ? 1 : 0,
                    sort_order: row.sort_order,
                }, { headers: this.authHeaders() });
                if (res.data.status) this.showInform('OK', 'Updated ' + row.code);
                else this.showInform('Error', res.data.message || 'Failed reload');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
        async runBulk() {
            const scopes = [];
            if (this.bulk.doMeals) scopes.push('meals');
            if (this.bulk.doExercises) scopes.push('exercises');
            if (this.bulk.doPrograms) scopes.push('programs');
            if (this.bulk.doWorkouts) scopes.push('workouts');
            const targets = [...new Set((this.bulk.target_locales || []).map((c) => (c || '').toString().trim()))].filter(Boolean);
            if (!targets.length) {
                this.showInform('Validation', 'Select at least one target language.');
                return;
            }
            if (!scopes.length) {
                this.showInform('Validation', 'Select at least one scope.');
                return;
            }
            this.bulkRunning = true;
            this.bulkResult = '';
            const timeout = this.bulk.runSync ? 3600000 : 120000;
            try {
                const res = await axios.post(config.baseApiUrl + 'bulk-translate-content', {
                    source_locale: this.bulk.source_locale || 'en',
                    target_locales: targets,
                    scopes,
                    sync: !!this.bulk.runSync,
                }, { headers: this.authHeaders(), timeout });
                if (res.data.status) {
                    if (res.data.queued) {
                        this.bulkResult = res.data.message || 'Queued.';
                        this.showInform('Queued', this.bulkResult + '\nTargets: ' + (res.data.target_locales || []).join(', '));
                    } else {
                        this.bulkResult = (res.data.message || 'Done') + ' — meals: ' + (res.data.meals_updated ?? 0) + ', exercises: ' + (res.data.exercises_updated ?? 0) + ', programs: ' + (res.data.programs_updated ?? 0) + ', workouts: ' + (res.data.workouts_updated ?? 0);
                        this.showInform('Bulk translate', this.bulkResult);
                    }
                } else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.bulkRunning = false;
            }
        },
        async saveMealTranslations() {
            let translations = {};
            try {
                translations = JSON.parse(this.mealEdit.json || '{}');
            } catch {
                this.showInform('JSON', 'Invalid JSON for meal translations.');
                return;
            }
            try {
                const res = await axios.post(config.baseApiUrl + 'meal-locale-translations', {
                    meal_id: parseInt(this.mealEdit.id, 10),
                    locale: this.mealEdit.locale,
                    translations,
                }, { headers: this.authHeaders() });
                if (res.data.status) this.showInform('OK', 'Meal translations saved.');
                else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
        async saveProgramTranslations() {
            let translations = {};
            try {
                translations = JSON.parse(this.progEdit.json || '{}');
            } catch {
                this.showInform('JSON', 'Invalid JSON for program translations.');
                return;
            }
            try {
                const res = await axios.post(config.baseApiUrl + 'program-locale-translations', {
                    program_id: parseInt(this.progEdit.id, 10),
                    locale: this.progEdit.locale,
                    translations,
                }, { headers: this.authHeaders() });
                if (res.data.status) this.showInform('OK', 'Program translations saved.');
                else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
        async saveWorkoutTranslations() {
            let translations = {};
            try {
                translations = JSON.parse(this.wrkEdit.json || '{}');
            } catch {
                this.showInform('JSON', 'Invalid JSON for workout translations.');
                return;
            }
            try {
                const res = await axios.post(config.baseApiUrl + 'workout-locale-translations', {
                    workout_id: parseInt(this.wrkEdit.id, 10),
                    locale: this.wrkEdit.locale,
                    translations,
                }, { headers: this.authHeaders() });
                if (res.data.status) this.showInform('OK', 'Workout translations saved.');
                else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
        async saveExerciseTranslations() {
            let translations = {};
            try {
                translations = JSON.parse(this.exEdit.json || '{}');
            } catch {
                this.showInform('JSON', 'Invalid JSON for exercise translations.');
                return;
            }
            try {
                const res = await axios.post(config.baseApiUrl + 'exercise-locale-translations', {
                    exercise_id: parseInt(this.exEdit.id, 10),
                    locale: this.exEdit.locale,
                    translations,
                }, { headers: this.authHeaders() });
                if (res.data.status) this.showInform('OK', 'Exercise translations saved.');
                else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            }
        },
    },
};
</script>
