<template>
    <div class="ai-tags-page">
        <Loader v-if="loading" :loadingText="loadingText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>

        <div class="page-head">
            <div>
                <h2 class="mb-1">AI Video Tags</h2>
                <p class="mb-0 muted">Review Ollama/Qwen proposals. Generation runs in the background and uses exercise metadata plus the stored thumbnail when available.</p>
            </div>
            <div class="head-actions">
                <button v-if="(summary.rejected || 0) + (summary.failed || 0) > 0" class="scnd_btn px-4 py-2" @click="clearRejectedProposals">Clear rejected/failed</button>
                <button class="prim_btn px-4 py-2" @click="generateProposals">Generate AI Tags</button>
            </div>
        </div>

        <div class="filters panel">
            <input v-model="filters.search" @keyup.enter="fetchProposals" class="form-control control" placeholder="Search video title or code">
            <select v-model="filters.language" class="form-select control" @change="fetchProposals">
                <option value="">All languages</option>
                <option value="en">English</option>
                <option value="ar">Arabic</option>
            </select>
            <select v-model="filters.equipment_category" class="form-select control">
                <option value="">All equipment</option>
                <option value="bodyweight">Bodyweight</option>
                <option value="home_dumbbell">Home Dumbbell</option>
                <option value="gym">Gym</option>
                <option value="full_gym">Full Gym</option>
            </select>
            <select v-model="filters.scope" class="form-select control">
                <option value="all">All videos</option>
                <option value="untagged">Untagged only</option>
                <option value="tagged">Tagged only</option>
            </select>
            <select v-model="filters.status" class="form-select control" @change="fetchProposals">
                <option value="">All proposals</option>
                <option value="queued">Queued</option>
                <option value="processing">Processing</option>
                <option value="proposed">Proposed</option>
                <option value="applied">Applied</option>
                <option value="rejected">Rejected</option>
                <option value="failed">Failed</option>
            </select>
            <input v-model.number="filters.limit" type="number" min="1" max="50" class="form-control small-control" title="Generate limit">
            <input v-model="filters.model" class="form-control model-control" placeholder="Ollama model">
            <button class="scnd_btn px-3" @click="fetchProposals">Refresh</button>
        </div>

        <div class="summary-row">
            <span>Total {{ summary.total || 0 }}</span>
            <span>Queued {{ summary.queued || 0 }}</span>
            <span>Processing {{ summary.processing || 0 }}</span>
            <span>Proposed {{ summary.proposed || 0 }}</span>
            <span>Applied {{ summary.applied || 0 }}</span>
            <span>Rejected {{ summary.rejected || 0 }}</span>
            <span>Failed {{ summary.failed || 0 }}</span>
        </div>

        <div class="panel table-panel">
            <div class="bulk-actions">
                <label class="select-all">
                    <input type="checkbox" :checked="allVisibleSelected" :disabled="proposals.length === 0" @change="toggleSelectAll($event)">
                    <span>{{ selectedIds.length }} selected</span>
                </label>
                <button class="tiny-btn" :disabled="selectedIds.length === 0 || selectedHasApprovalBlockers" :title="selectedHasApprovalBlockers ? 'One or more selected proposals need manual review.' : ''" @click="bulkAction('apply_approve')">Apply + approve selected</button>
                <button class="tiny-btn" :disabled="selectedIds.length === 0" @click="bulkAction('apply_pending')">Apply pending selected</button>
                <button class="tiny-btn reject" :disabled="selectedIds.length === 0" @click="bulkAction('remove')">Remove selected</button>
                <button class="tiny-btn" :disabled="selectedIds.length === 0" @click="selectedProposalIds = []">Clear selection</button>
            </div>
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th class="select-col">
                            <input type="checkbox" :checked="allVisibleSelected" :disabled="proposals.length === 0" @change="toggleSelectAll($event)">
                        </th>
                        <th>Video</th>
                        <th>Current tag</th>
                        <th>Qwen proposal</th>
                        <th>Safety / usage</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="proposals.length === 0">
                        <td colspan="7" class="text-center muted py-4">No AI tag proposals yet.</td>
                    </tr>
                    <tr v-for="proposal in proposals" :key="proposal.id">
                        <td class="select-col">
                            <input type="checkbox" :value="proposal.id" v-model="selectedProposalIds">
                        </td>
                        <td>
                            <div class="video-cell">
                                <img v-if="proposal.exercise && proposal.exercise.image" :src="proposal.exercise.image" alt="">
                                <div>
                                    <strong>{{ proposal.exercise?.title || 'Deleted exercise' }}</strong>
                                    <div class="muted small-text">ID {{ proposal.exercise_id }} | {{ proposal.exercise?.content_code || '-' }}</div>
                                    <div class="muted small-text">{{ readableLanguage(proposal.exercise?.language) }} | {{ proposal.exercise?.video_type || '-' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div v-if="proposal.current_tag_payload">
                                <strong>{{ readableStatus(proposal.current_tag_payload.exercise_type) }}</strong>
                                <div>{{ readableEquipment(proposal.current_tag_payload.equipment_category) }}</div>
                                <div>{{ proposal.current_tag_payload.muscle_group || '-' }}</div>
                            </div>
                            <span v-else class="muted">No current tag</span>
                        </td>
                        <td>
                            <div v-if="proposal.proposed_payload">
                                <strong>{{ readableStatus(proposal.proposed_payload.primary_category || proposal.proposed_payload.exercise_type) }}</strong>
                                <div v-if="proposal.proposed_payload.secondary_categories && proposal.proposed_payload.secondary_categories.length" class="muted small-text">
                                    + {{ proposal.proposed_payload.secondary_categories.map(readableStatus).join(', ') }}
                                </div>
                                <div>{{ readableStatus(proposal.proposed_payload.training_adaptation || '-') }}</div>
                                <div class="muted small-text">Role: {{ readableStatus(proposal.proposed_payload.program_role || '-') }}</div>
                                <div>{{ readableEquipment(proposal.proposed_payload.equipment_category) }}</div>
                                <div>{{ proposal.proposed_payload.muscle_group || '-' }} | {{ proposal.proposed_payload.difficulty }}</div>
                                <div v-if="proposal.proposed_payload.body_regions && proposal.proposed_payload.body_regions.length" class="muted small-text">
                                    Regions: {{ proposal.proposed_payload.body_regions.map(readableStatus).join(', ') }}
                                </div>
                                <div v-if="proposal.proposed_payload.exercise_family" class="muted small-text">
                                    Family: {{ readableStatus(proposal.proposed_payload.exercise_family) }}
                                </div>
                                <div class="muted small-text">
                                    {{ readableStatus(proposal.proposed_payload.movement_direction || '-') }} /
                                    {{ readableStatus(proposal.proposed_payload.stability_demand || '-') }} /
                                    {{ readableStatus(proposal.proposed_payload.variation_type || '-') }}
                                </div>
                                <div v-if="proposal.proposed_payload.confidence_bucket" class="muted small-text">
                                    Bucket: {{ readableStatus(proposal.proposed_payload.confidence_bucket) }}
                                </div>
                                <div class="muted small-text">Confidence {{ confidence(proposal.confidence) }}</div>
                            </div>
                            <span v-else class="text-danger">{{ proposal.error_message || 'No proposal' }}</span>
                        </td>
                        <td>
                            <div v-if="proposal.proposed_payload">
                                <div>{{ proposal.proposed_payload.impact_level || '-' }} impact / {{ proposal.proposed_payload.intensity_level || '-' }} intensity</div>
                                <div v-if="proposal.proposed_payload.safety_flags && proposal.proposed_payload.safety_flags.unsafe_as_warmup" class="danger-text">Unsafe as warm-up</div>
                                <div class="usage-list">{{ enabledUsage(proposal.proposed_payload.usage_flags).join(', ') || 'No usage flags' }}</div>
                                <div v-if="hasApprovalBlockers(proposal)" class="danger-text">
                                    {{ approvalBlockers(proposal).join('; ') }}
                                </div>
                                <div v-if="proposal.reasoning" class="muted small-text">{{ proposal.reasoning }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge" :class="'status-' + proposal.status">{{ readableStatus(proposal.status) }}</span>
                            <div class="muted small-text">{{ proposal.model }}</div>
                        </td>
                        <td class="text-end">
                            <button v-if="proposal.status === 'proposed'" class="tiny-btn" :disabled="hasApprovalBlockers(proposal)" :title="hasApprovalBlockers(proposal) ? 'Apply pending first; this proposal needs manual review.' : ''" @click="applyProposal(proposal.id, true)">Apply + approve</button>
                            <button v-if="proposal.status === 'proposed'" class="tiny-btn" @click="applyProposal(proposal.id, false)">Apply pending</button>
                            <button v-if="['queued', 'processing', 'proposed', 'failed'].includes(proposal.status)" class="tiny-btn reject" @click="rejectProposal(proposal.id)">Remove</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';

export default {
    components: { Loader, Inform },
    data() {
        return {
            loading: false,
            loadingText: 'Loading...',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            proposals: [],
            summary: {},
            selectedProposalIds: [],
            filters: {
                search: '',
                language: '',
                equipment_category: '',
                scope: 'all',
                status: 'proposed',
                limit: 10,
                model: ''
            },
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            pollingTimer: null
        };
    },
    computed: {
        selectedIds() {
            return this.selectedProposalIds.map(id => Number(id)).filter(Boolean);
        },
        selectedProposals() {
            return this.proposals.filter(proposal => this.selectedIds.includes(Number(proposal.id)));
        },
        selectedHasApprovalBlockers() {
            return this.selectedProposals.some(proposal => this.hasApprovalBlockers(proposal));
        },
        allVisibleSelected() {
            return this.proposals.length > 0 && this.proposals.every(proposal => this.selectedIds.includes(Number(proposal.id)));
        }
    },
    mounted() {
        this.fetchProposals();
    },
    beforeUnmount() {
        this.stopPolling();
    },
    methods: {
        fetchProposals() {
            this.loading = !this.pollingTimer;
            axios.get(config.baseApiUrl + 'routine-library/ai-video-tags', {
                ...this.apiConfig,
                params: {
                    search: this.filters.search || undefined,
                    language: this.filters.language || undefined,
                    status: this.filters.status || undefined,
                    per_page: 50
                }
            }).then(res => {
                this.proposals = res.data.data?.data || [];
                this.summary = res.data.summary || {};
                const visibleIds = this.proposals.map(proposal => Number(proposal.id));
                this.selectedProposalIds = this.selectedIds.filter(id => visibleIds.includes(id));
                if (!this.filters.model) {
                    this.filters.model = res.data.options?.default_model || 'qwen2.5vl:7b';
                }
                this.togglePolling();
            }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                });
        },
        generateProposals() {
            this.loading = true;
            this.loadingText = 'Asking Ollama/Qwen to tag videos...';
            axios.post(config.baseApiUrl + 'routine-library/ai-video-tags/generate', {
                search: this.filters.search || undefined,
                language: this.filters.language || undefined,
                equipment_category: this.filters.equipment_category || undefined,
                scope: this.filters.scope,
                limit: this.filters.limit,
                model: this.filters.model || undefined
            }, this.apiConfig).then(res => {
                this.modalTitle = 'AI tagging queued';
                this.modalDetail = res.data.message || 'AI proposals queued.';
                this.informModal = true;
                this.fetchProposals();
                this.startPolling();
            }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                    this.loadingText = 'Loading...';
                });
        },
        applyProposal(id, approve) {
            this.loading = true;
            axios.post(config.baseApiUrl + 'routine-library/ai-video-tags/' + id + '/apply', {
                approve
            }, this.apiConfig).then(res => {
                this.modalTitle = 'Applied';
                this.modalDetail = res.data.message || 'AI proposal applied.';
                this.informModal = true;
                this.fetchProposals();
            }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                });
        },
        rejectProposal(id) {
            this.loading = true;
            axios.post(config.baseApiUrl + 'routine-library/ai-video-tags/' + id + '/reject', {}, this.apiConfig)
                .then(res => {
                    this.modalTitle = 'Removed';
                    this.modalDetail = res.data.message || 'AI proposal removed.';
                    this.informModal = true;
                    this.fetchProposals();
                }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                });
        },
        clearRejectedProposals() {
            this.loading = true;
            axios.post(config.baseApiUrl + 'routine-library/ai-video-tags/clear-rejected', {}, this.apiConfig)
                .then(res => {
                    this.modalTitle = 'Cleared';
                    this.modalDetail = res.data.message || 'Rejected/failed proposals removed.';
                    this.informModal = true;
                    this.filters.status = 'proposed';
                    this.fetchProposals();
                }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                });
        },
        bulkAction(action) {
            if (this.selectedIds.length === 0) {
                return;
            }
            const labels = {
                apply_approve: 'apply and approve selected proposals',
                apply_pending: 'apply selected proposals as pending',
                remove: 'remove selected proposals'
            };
            if (!window.confirm('Bulk action: ' + labels[action] + '?')) {
                return;
            }

            this.loading = true;
            axios.post(config.baseApiUrl + 'routine-library/ai-video-tags/bulk', {
                ids: this.selectedIds,
                action
            }, this.apiConfig).then(res => {
                this.modalTitle = 'Bulk action complete';
                this.modalDetail = res.data.message || 'Selected proposals updated.';
                this.informModal = true;
                this.selectedProposalIds = [];
                this.fetchProposals();
            }).catch(er => this.showError(er.response?.data?.message || er.message))
                .finally(() => {
                    this.loading = false;
                });
        },
        toggleSelectAll(event) {
            if (event.target.checked) {
                this.selectedProposalIds = this.proposals.map(proposal => Number(proposal.id));
                return;
            }
            this.selectedProposalIds = [];
        },
        enabledUsage(flags) {
            if (!flags) {
                return [];
            }
            return Object.keys(flags).filter(key => flags[key]).map(this.readableStatus);
        },
        hasApprovalBlockers(proposal) {
            return this.approvalBlockers(proposal).length > 0;
        },
        approvalBlockers(proposal) {
            const blockers = proposal?.proposed_payload?.review_blockers;
            const list = Array.isArray(blockers) ? [...blockers] : [];
            if (this.isLowConfidence(proposal)) {
                list.push('Low AI confidence; apply pending and review manually before approval.');
            }
            return [...new Set(list.filter(Boolean))];
        },
        isLowConfidence(proposal) {
            const bucket = proposal?.proposed_payload?.confidence_bucket;
            if (bucket === 'low') {
                return true;
            }
            if (proposal?.confidence === null || proposal?.confidence === undefined || proposal?.confidence === '') {
                return false;
            }
            return Number(proposal.confidence) < 0.55;
        },
        hasActiveQueue() {
            return (Number(this.summary.queued || 0) + Number(this.summary.processing || 0)) > 0;
        },
        togglePolling() {
            if (this.hasActiveQueue()) {
                this.startPolling();
            } else {
                this.stopPolling();
            }
        },
        startPolling() {
            if (this.pollingTimer) {
                return;
            }
            this.pollingTimer = setInterval(() => {
                this.fetchProposals();
            }, 5000);
        },
        stopPolling() {
            if (!this.pollingTimer) {
                return;
            }
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        },
        confidence(value) {
            if (value === null || value === undefined) {
                return '-';
            }
            return Math.round(Number(value) * 100) + '%';
        },
        readableEquipment(value) {
            return String(value || '-').replaceAll('_', ' ');
        },
        readableStatus(value) {
            return String(value || '-').replaceAll('_', ' ');
        },
        readableLanguage(value) {
            const map = { en: 'English', ar: 'Arabic', no_audio: 'No audio' };
            return map[value] || value || '-';
        },
        showError(message) {
            this.modalTitle = 'Error';
            this.modalDetail = message || 'Something went wrong';
            this.informModal = true;
            this.loading = false;
        }
    }
};
</script>

<style scoped>
.ai-tags-page {
    padding: 14px;
}
.page-head,
.filters,
.summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.panel {
    background: #fff;
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    padding: 14px;
}
.control {
    width: 180px;
}
.small-control {
    width: 90px;
}
.model-control {
    width: 180px;
}
.muted {
    color: #68717c;
}
.small-text {
    font-size: 12px;
}
.danger-text {
    color: #b30000;
    font-weight: 700;
}
.summary-row {
    justify-content: flex-start;
}
.summary-row span {
    border: 1px solid #e2e2e2;
    border-radius: 6px;
    padding: 6px 10px;
    background: #fff;
}
.head-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.table-panel {
    overflow-x: auto;
}
.bulk-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}
.select-all {
    align-items: center;
    display: flex;
    gap: 6px;
    margin: 0 8px 0 0;
}
.select-col {
    width: 34px;
}
.tiny-btn:disabled {
    color: #999;
    cursor: not-allowed;
    opacity: 0.55;
}
.video-cell {
    display: flex;
    gap: 10px;
    align-items: center;
    min-width: 240px;
}
.video-cell img {
    width: 76px;
    height: 48px;
    object-fit: cover;
    border-radius: 4px;
    background: #f4f4f4;
}
.usage-list {
    max-width: 260px;
}
.status-badge {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 6px;
    background: #f2f2f2;
    font-weight: 700;
}
.status-proposed {
    background: #fff0df;
    color: #a85000;
}
.status-queued {
    background: #eef3ff;
    color: #2350a3;
}
.status-processing {
    background: #e8f5ff;
    color: #006c9c;
}
.status-applied {
    background: #dff6e8;
    color: #08783a;
}
.status-rejected,
.status-failed {
    background: #ffe5e5;
    color: #b30000;
}
.tiny-btn {
    border: 1px solid #d2d8df;
    background: #fff;
    border-radius: 5px;
    padding: 5px 8px;
    margin: 2px;
    color: #007a2f;
}
.tiny-btn.reject {
    color: #b30000;
}
</style>
