<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="brds-3" style="width:99%;border: 1px solid #e7e7e7;min-height:490px">
        <div class="d-flex w-100" style="height:55px;background-color:#EEEEEE;border-top-right-radius:inherit;border-top-left-radius:inherit;">
            <p class="ms-4 mb-0" style="font-size:26px;font-weight:bold;margin-top:7px;">Flutter UI strings</p>
        </div>
        <div class="p-3">
            <p class="text-muted small">
                Keys come from <code>app_<em>source</em>.arb</code> in the mobile repo (see <code>FLUTTER_L10N_DIR</code>).
                Target text is stored in the database and can be exported as <code>app_<em>target</em>.arb</code> for <code>lib/l10n</code> and <code>flutter gen-l10n</code>.
            </p>

            <div class="d-flex flex-wrap gap-3 align-items-end mb-3">
                <div>
                    <label class="form-label mb-0">Source language (read-only reference)</label>
                    <select v-model="sourceLocale" class="form-select form-select-sm" style="min-width:160px">
                        <option v-for="l in languages" :key="'src-'+l.id" :value="l.code">{{ l.code }} — {{ l.native_label }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label mb-0">Target language (edit &amp; save)</label>
                    <select v-model="targetLocale" class="form-select form-select-sm" style="min-width:160px">
                        <option v-for="l in targetChoices" :key="'tgt-'+l.id" :value="l.code">{{ l.code }} — {{ l.native_label }}</option>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm" :disabled="loadLoading" @click="loadRows">
                    {{ loadLoading ? 'Loading…' : 'Load strings' }}
                </button>
                <button type="button" class="btn btn-success btn-sm" :disabled="saving || !rows.length" @click="saveAll">
                    {{ saving ? 'Saving…' : 'Save target translations' }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" :disabled="exportLoading || !targetLocale" @click="exportArb">
                    {{ exportLoading ? 'Export…' : 'Download .arb (merged)' }}
                </button>
                <button type="button" class="btn btn-outline-dark btn-sm" :disabled="zipTopLoading" @click="exportTop20Zip" title="zh, hi, es, fr, ar, pt, ru, id, de, ja, tr, ko, vi, it, pl, uk, th, nl, he, sv">
                    {{ zipTopLoading ? 'Zip…' : 'Download top 20 locales (.zip)' }}
                </button>
            </div>

            <div v-if="meta.count" class="small text-muted mb-2">
                {{ meta.count }} keys — {{ meta.saved_in_db }} saved in DB for {{ meta.target_locale }}
            </div>

            <div class="mb-2" v-if="rows.length">
                <label class="form-label mb-0 small">Filter by key or source text</label>
                <input v-model="filter" class="form-control form-control-sm" placeholder="Search…" style="max-width:320px" />
            </div>

            <div v-if="rows.length" class="table-responsive" style="max-height:65vh;overflow:auto;border:1px solid #ddd">
                <table class="table table-sm table-bordered mb-0 bg-white">
                    <thead class="sticky-top" style="background:#f8f9fa">
                        <tr>
                            <th style="width:18%">Key</th>
                            <th style="width:36%">Source</th>
                            <th style="width:46%">Translation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in filteredRows" :key="r.key">
                            <td class="small font-monospace text-break">{{ r.key }}</td>
                            <td class="small text-muted text-break">{{ r.source }}</td>
                            <td class="p-1">
                                <textarea
                                    v-model="r.target"
                                    class="form-control form-control-sm"
                                    rows="2"
                                    :dir="isRtlTarget ? 'rtl' : 'ltr'"
                                ></textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!rows.length && !loadLoading" class="text-muted small mb-0">Choose languages and click <strong>Load strings</strong>.</p>
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
            sourceLocale: 'en',
            targetLocale: 'ar',
            rows: [],
            meta: { count: 0, saved_in_db: 0, target_locale: '', source_locale: '' },
            filter: '',
            loadLoading: false,
            saving: false,
            exportLoading: false,
            zipTopLoading: false,
        };
    },
    computed: {
        targetChoices() {
            return this.languages.filter((l) => (l.code || '').toLowerCase() !== (this.sourceLocale || '').toLowerCase());
        },
        filteredRows() {
            const q = (this.filter || '').trim().toLowerCase();
            if (!q) return this.rows;
            return this.rows.filter(
                (r) =>
                    r.key.toLowerCase().includes(q) ||
                    (r.source && r.source.toLowerCase().includes(q)) ||
                    (r.target && r.target.toLowerCase().includes(q))
            );
        },
        isRtlTarget() {
            const rtl = ['ar', 'he', 'fa', 'ur'];
            return rtl.includes((this.targetLocale || '').toLowerCase());
        },
    },
    watch: {
        sourceLocale() {
            const tgt = (this.targetLocale || '').toLowerCase();
            const src = (this.sourceLocale || '').toLowerCase();
            if (tgt === src && this.targetChoices.length) {
                this.targetLocale = this.targetChoices[0].code;
            }
        },
    },
    mounted() {
        this.loadLanguages();
    },
    methods: {
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
                if (res.data.status) {
                    this.languages = res.data.data || [];
                    if (!this.languages.some((l) => l.code === this.sourceLocale)) {
                        this.sourceLocale = (this.languages[0] && this.languages[0].code) || 'en';
                    }
                    if (!this.targetChoices.some((l) => l.code === this.targetLocale)) {
                        const ar = this.targetChoices.find((l) => l.code === 'ar');
                        this.targetLocale = ar ? ar.code : (this.targetChoices[0] && this.targetChoices[0].code) || 'ar';
                    }
                } else this.showInform('Error', res.data.message || 'Failed');
            } catch ( e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.pageLoading = false;
            }
        },
        async loadRows() {
            if (!this.sourceLocale || !this.targetLocale) {
                this.showInform('Validation', 'Select source and target.');
                return;
            }
            if (this.sourceLocale.toLowerCase() === this.targetLocale.toLowerCase()) {
                this.showInform('Validation', 'Source and target must differ.');
                return;
            }
            this.loadLoading = true;
            this.filter = '';
            try {
                const res = await axios.get(config.baseApiUrl + 'localization/ui-strings', {
                    headers: this.authHeaders(),
                    params: { source_locale: this.sourceLocale, target_locale: this.targetLocale },
                });
                if (!res.data.status) {
                    this.showInform('Error', res.data.message || 'Failed');
                    this.rows = [];
                    return;
                }
                this.meta = res.data.meta || {};
                this.rows = (res.data.rows || []).map((row) => ({
                    key: row.key,
                    source: row.source,
                    target: row.target == null ? '' : String(row.target),
                }));
            } catch (e) {
                this.rows = [];
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.loadLoading = false;
            }
        },
        async saveAll() {
            if (!this.rows.length || !this.targetLocale) return;
            this.saving = true;
            try {
                const strings = {};
                this.rows.forEach((r) => {
                    strings[r.key] = r.target != null ? String(r.target) : '';
                });
                const res = await axios.post(
                    config.baseApiUrl + 'localization/ui-strings',
                    { target_locale: this.targetLocale, strings },
                    { headers: this.authHeaders() }
                );
                if (res.data.status) {
                    this.showInform('Saved', res.data.message + ' (upserted: ' + res.data.upserted + ', cleared empty: ' + res.data.removed_empty + ')');
                    await this.loadRows();
                } else this.showInform('Error', res.data.message || 'Failed');
            } catch (e) {
                this.showInform('Error', e.response?.data?.message || e.message);
            } finally {
                this.saving = false;
            }
        },
        async exportTop20Zip() {
            this.zipTopLoading = true;
            try {
                const res = await axios.get(config.baseApiUrl + 'localization/export-top-arbs-zip', {
                    headers: this.authHeaders(),
                    responseType: 'blob',
                });
                const blob = new Blob([res.data], { type: 'application/zip' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'l10n_top20_spoken.zip';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (e) {
                let msg = e.message;
                if (e.response && e.response.data instanceof Blob) {
                    try {
                        const text = await e.response.data.text();
                        const j = JSON.parse(text);
                        msg = j.message || text;
                    } catch {
                        msg = 'Download failed';
                    }
                }
                this.showInform('Error', msg);
            } finally {
                this.zipTopLoading = false;
            }
        },
        async exportArb() {
            if (!this.targetLocale) return;
            this.exportLoading = true;
            try {
                const res = await axios.get(config.baseApiUrl + 'localization/ui-strings-export-arb', {
                    headers: this.authHeaders(),
                    params: { target_locale: this.targetLocale },
                    responseType: 'blob',
                });
                const blob = new Blob([res.data], { type: 'application/json;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'app_' + this.targetLocale + '.arb';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } catch (e) {
                let msg = e.message;
                if (e.response && e.response.data instanceof Blob) {
                    try {
                        const text = await e.response.data.text();
                        const j = JSON.parse(text);
                        msg = j.message || text;
                    } catch {
                        msg = 'Export failed';
                    }
                }
                this.showInform('Error', msg);
            } finally {
                this.exportLoading = false;
            }
        },
    },
};
</script>
