<template>
    <div>
        <Filters
            v-if="filters"
            :tags="tags"
            :prefillTags="selectedTagsForFilter"
            @apply="applyFilters"
            @reset="clearFilters"
            @close="filters = false"
        />
        <div class="row mt-3 g-3">
            <div class="col-xl-7">
                <p class="mb-2 h8 text-muted">Drag routines into a section, reorder within a section, or change the section tag.</p>
                <div
                    v-for="section in sections"
                    :key="section.id"
                    class="mb-3 p-3 brds-2"
                    style="border: 1px solid #d9d9d9; min-height: 120px;"
                    @dragenter.prevent
                    @dragover.prevent
                    @drop="onSectionDrop(section.id)"
                >
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 fw-bold">{{ section.label }}</h6>
                        <span class="h8 text-muted">{{ workoutsForSection(section.id).length }} routine(s)</span>
                    </div>
                    <div v-if="workoutsForSection(section.id).length === 0" class="text-center py-4 text-muted h8">
                        Drop a workout routine here
                    </div>
                    <div class="d-flex flex-wrap">
                        <div
                            v-for="item in workoutsForSection(section.id)"
                            :key="item.id"
                            class="shd_card p-2 m-2 position-relative reorder-card"
                            style="width: 180px;"
                            draggable="true"
                            @dragstart="startPhaseWorkoutDrag(item)"
                            @dragover.prevent
                            @drop.prevent="onReorderDrop(section.id, item)"
                        >
                            <div class="reorder-handle h8 text-muted mb-1">
                                <i class="fa-solid fa-grip-vertical me-1"></i> Drag to reorder
                            </div>
                            <span class="day-badge">Day {{ dayNumberForPhaseWorkout(item) }}</span>
                            <div v-if="item.workout_detail != null">
                                <img
                                    v-if="item.workout_detail.image != null"
                                    :src="item.workout_detail.image"
                                    alt=""
                                    class="img-fluid"
                                    style="width: 100%; object-fit: contain; background: black; height: 120px;"
                                >
                                <img v-else src="/images/download1.png" alt="" class="img-fluid" style="width: 100%; height: 120px;">
                            </div>
                            <img v-else src="/images/download1.png" alt="" class="img-fluid" style="width: 100%; height: 120px;">
                            <input
                                type="checkbox"
                                class="position-absolute form-check-input"
                                style="top:5px;left:10px"
                                :value="item.id"
                                v-model="selectedIds"
                            >
                            <p class="mb-1 fw-bold h7">{{ item.display_name }}</p>
                            <p class="mb-1 h8">
                                {{ item.workout_detail?.workout_exercises_count ?? 0 }} exercises
                            </p>
                            <select
                                class="form-select form-select-sm"
                                :value="item.section_tag || 'custom'"
                                @change="updateSectionTag(item.id, $event.target.value)"
                            >
                                <option v-for="opt in sections" :key="opt.id" :value="opt.id">{{ opt.label }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="shd_card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Workout routines</h5>
                        <span class="h8 text-muted">Drag into a section</span>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <div class="position-relative flex-grow-1">
                            <input
                                v-model="search"
                                type="search"
                                class="searchinput w-100"
                                placeholder="Search workout routines"
                                @input="applySearch"
                            >
                            <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search">
                        </div>
                        <button class="trans_btn py-1 px-2" @click="filters = true" title="Filter by tags">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid">
                        </button>
                    </div>
                    <p v-if="selectedTagsForFilter.length" class="h8 text-muted mb-2">
                        Filtering by {{ selectedTagsForFilter.length }} tag(s)
                    </p>
                    <div style="max-height: 420px; overflow-y: auto;">
                        <div class="row">
                            <div
                                v-for="workout in visibleWorkouts"
                                :key="workout.id"
                                class="col-md-6 col-12 mt-2"
                            >
                                <div
                                    class="shd_card p-2 text-center drag-el h-100"
                                    draggable="true"
                                    @dragstart="startLibraryDrag(workout)"
                                    style="cursor: grab;"
                                >
                                    <div class="position-relative routine-thumb">
                                        <img
                                            :src="workout.image || '/images/download1.png'"
                                            alt=""
                                            class="img-fluid mb-2"
                                            style="height: 80px; width: 100%; object-fit: contain; background: #111;"
                                        >
                                        <span class="language-badge">{{ modifyLanguage(workout.language) }}</span>
                                    </div>
                                    <p class="mb-0 h8 fw-bold" style="word-break: break-word;">{{ workout.title }}</p>
                                    <p class="mb-0 h8 text-muted">{{ workout.workout_exercises_count ?? 0 }} exercises</p>
                                </div>
                            </div>
                            <p v-if="!loading && visibleWorkouts.length === 0" class="text-center text-muted mt-3 h8 col-12">
                                No workout routines found for this language or tag filter.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';
import Filters from '../filters.vue';

export default {
    components: { Filters },
    props: {
        phaseId: {
            type: [Number, String],
            required: true,
        },
        phaseWorkouts: {
            type: Array,
            default: () => [],
        },
        programLanguage: {
            type: String,
            default: 'en',
        },
        selectedDeleteIds: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['refresh', 'update:selectedDeleteIds'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token'),
                },
            },
            sections: [
                { id: 'warm_up', label: 'Warm-up' },
                { id: 'stretching', label: 'Stretching' },
                { id: 'strength_training', label: 'Strength training' },
                { id: 'cardio', label: 'Cardio' },
                { id: 'custom', label: 'Custom' },
            ],
            workouts: [],
            visibleWorkouts: [],
            search: '',
            loading: false,
            filters: false,
            tags: [],
            selectedTagsForFilter: [],
            draggedLibraryWorkout: null,
            draggedPhaseWorkout: null,
        };
    },
    computed: {
        selectedIds: {
            get() {
                return this.selectedDeleteIds;
            },
            set(value) {
                this.$emit('update:selectedDeleteIds', value);
            },
        },
    },
    watch: {
        programLanguage() {
            this.loadWorkouts();
        },
    },
    mounted() {
        this.loadWorkouts();
        this.loadTags();
    },
    methods: {
        workoutsForSection(sectionId) {
            return (this.phaseWorkouts || [])
                .filter((item) => (item.section_tag || 'custom') === sectionId)
                .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
        },
        orderedPhaseWorkouts() {
            return [...(this.phaseWorkouts || [])].sort((a, b) => {
                const orderDiff = (a.sort_order ?? 0) - (b.sort_order ?? 0);
                return orderDiff !== 0 ? orderDiff : Number(a.id) - Number(b.id);
            });
        },
        dayNumberForPhaseWorkout(item) {
            const index = this.orderedPhaseWorkouts().findIndex((phaseWorkout) => phaseWorkout.id === item.id);
            return index >= 0 ? index + 1 : 1;
        },
        modifyLanguage(language) {
            if (language === 'no') {
                return 'NA';
            }
            return String(language || '').toUpperCase();
        },
        loadTags() {
            axios.get(config.baseApiUrl + 'get-tags?category=workout', this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.tags = res.data.data || [];
                    }
                })
                .catch(() => {});
        },
        loadWorkouts() {
            this.loading = true;
            const lang = this.programLanguage || 'en';
            let url = config.baseApiUrl + 'all-workouts-list?lang=' + lang;
            if (this.selectedTagsForFilter.length > 0) {
                url += '&tag_ids=' + this.selectedTagsForFilter.join(',');
            }
            axios.get(url, this.apiConfig)
                .then((res) => {
                    this.loading = false;
                    if (res.data.status) {
                        this.workouts = res.data.data || [];
                        this.applySearch();
                    }
                })
                .catch(() => {
                    this.loading = false;
                });
        },
        applyFilters(tagIds) {
            if (!tagIds || tagIds.length === 0) {
                this.clearFilters();
                return;
            }
            this.filters = false;
            this.selectedTagsForFilter = tagIds || [];
            this.loadWorkouts();
        },
        clearFilters() {
            this.filters = false;
            this.selectedTagsForFilter = [];
            this.loadWorkouts();
        },
        applySearch() {
            const term = this.search.trim().toLowerCase();
            if (!term) {
                this.visibleWorkouts = this.workouts;
                return;
            }
            this.visibleWorkouts = this.workouts.filter((item) =>
                String(item.title || '').toLowerCase().includes(term)
            );
        },
        startLibraryDrag(workout) {
            this.draggedLibraryWorkout = workout;
            this.draggedPhaseWorkout = null;
        },
        startPhaseWorkoutDrag(item) {
            this.draggedPhaseWorkout = item;
            this.draggedLibraryWorkout = null;
        },
        onSectionDrop(sectionTag) {
            if (this.draggedPhaseWorkout) {
                if ((this.draggedPhaseWorkout.section_tag || 'custom') !== sectionTag) {
                    this.updateSectionTag(this.draggedPhaseWorkout.id, sectionTag);
                }
                this.draggedPhaseWorkout = null;
                return;
            }
            if (!this.draggedLibraryWorkout) {
                return;
            }
            const postData = {
                program_phase_id: this.phaseId,
                workout_id: this.draggedLibraryWorkout.id,
                section_tag: sectionTag,
            };
            axios.post(config.baseApiUrl + 'add-phase-workout-routine', postData, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.draggedLibraryWorkout = null;
                        this.$emit('refresh');
                    }
                })
                .catch(() => {
                    this.draggedLibraryWorkout = null;
                });
        },
        onReorderDrop(sectionId, targetItem) {
            if (!this.draggedPhaseWorkout || this.draggedPhaseWorkout.id === targetItem.id) {
                return;
            }
            const sectionItems = [...this.workoutsForSection(sectionId)];
            const fromIndex = sectionItems.findIndex((item) => item.id === this.draggedPhaseWorkout.id);
            const toIndex = sectionItems.findIndex((item) => item.id === targetItem.id);
            if (fromIndex < 0 || toIndex < 0) {
                return;
            }
            const [moved] = sectionItems.splice(fromIndex, 1);
            sectionItems.splice(toIndex, 0, moved);

            const orderedIds = [];
            this.sections.forEach((section) => {
                const items = section.id === sectionId
                    ? sectionItems
                    : this.workoutsForSection(section.id);
                items.forEach((item) => orderedIds.push(item.id));
            });

            this.draggedPhaseWorkout = null;
            axios.post(config.baseApiUrl + 'reorder-phase-workouts', {
                program_phase_id: this.phaseId,
                ordered_ids: orderedIds,
            }, this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.$emit('refresh');
                }
            });
        },
        updateSectionTag(phaseWorkoutId, sectionTag) {
            axios.post(config.baseApiUrl + 'update-phase-workout-section', {
                phase_workout_id: phaseWorkoutId,
                section_tag: sectionTag,
            }, this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.$emit('refresh');
                }
            });
        },
    },
};
</script>

<style scoped>
.drag-el:active,
.reorder-card:active {
    cursor: grabbing;
}
.reorder-handle {
    cursor: grab;
}
.day-badge,
.language-badge {
    background: #111;
    border-radius: 999px;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    padding: 7px 8px;
}
.day-badge {
    position: absolute;
    right: 8px;
    top: 8px;
    z-index: 2;
}
.routine-thumb {
    min-height: 82px;
}
.language-badge {
    bottom: 8px;
    left: 4px;
    min-width: 25px;
    padding-left: 5px;
    padding-right: 5px;
    position: absolute;
    text-align: center;
}
</style>
