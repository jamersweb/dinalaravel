<template>
    <div class="row mt-3 g-3">
        <div class="col-xl-7">
            <p class="mb-2 h8 text-muted">Drag workout routines from the library into a section, or change the section tag after adding.</p>
            <div
                v-for="section in sections"
                :key="section.id"
                class="mb-3 p-3 brds-2"
                style="border: 1px solid #d9d9d9; min-height: 120px;"
                @dragenter.prevent
                @dragover.prevent
                @drop="onDrop(section.id)"
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
                        class="shd_card p-2 m-2 position-relative"
                        style="width: 180px;"
                    >
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
                <div class="position-relative mb-3">
                    <input
                        v-model="search"
                        type="search"
                        class="searchinput w-100"
                        placeholder="Search workout routines"
                        @input="applySearch"
                    >
                    <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search">
                </div>
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
                                @dragstart="startDrag(workout)"
                                style="cursor: grab;"
                            >
                                <img
                                    :src="workout.image || '/images/download1.png'"
                                    alt=""
                                    class="img-fluid mb-2"
                                    style="height: 80px; width: 100%; object-fit: contain; background: #111;"
                                >
                                <p class="mb-0 h8 fw-bold" style="word-break: break-word;">{{ workout.title }}</p>
                                <p class="mb-0 h8 text-muted">{{ workout.workout_exercises_count ?? 0 }} exercises</p>
                            </div>
                        </div>
                        <p v-if="!loading && visibleWorkouts.length === 0" class="text-center text-muted mt-3 h8 col-12">
                            No workout routines found for this program language.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';

export default {
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
            draggedWorkout: null,
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
    },
    methods: {
        workoutsForSection(sectionId) {
            return (this.phaseWorkouts || []).filter((item) => (item.section_tag || 'custom') === sectionId);
        },
        loadWorkouts() {
            this.loading = true;
            const lang = this.programLanguage || 'en';
            axios.get(config.baseApiUrl + 'all-workouts-list?lang=' + lang, this.apiConfig)
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
        startDrag(workout) {
            this.draggedWorkout = workout;
        },
        onDrop(sectionTag) {
            if (!this.draggedWorkout) {
                return;
            }
            const postData = {
                program_phase_id: this.phaseId,
                workout_id: this.draggedWorkout.id,
                section_tag: sectionTag,
            };
            axios.post(config.baseApiUrl + 'add-phase-workout-routine', postData, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.draggedWorkout = null;
                        this.$emit('refresh');
                    }
                })
                .catch(() => {
                    this.draggedWorkout = null;
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
.drag-el:active {
    cursor: grabbing;
}
</style>
