<template>
    <div class="shd_card heavy_shd p-md-3 p-1">
        <Filters
            v-if="filtersOpen"
            :tags="workoutTags"
            :prefillTags="selectedRoutineTags"
        />
        <div class="d-flex gap-2 mb-3">
            <button
                class="prim_btn py-1 px-3 brds-2"
                :class="{ 'opacity-50': libraryTab !== 'exercises' }"
                @click="libraryTab = 'exercises'"
            >
                Exercises
            </button>
            <button
                class="prim_btn py-1 px-3 brds-2"
                :class="{ 'opacity-50': libraryTab !== 'routines' }"
                @click="switchToRoutines()"
            >
                Workout routines
            </button>
        </div>

        <div class="d-flex justify-content-between gray_bg p-3">
            <div class="position-relative w-100">
                <input
                    @input="onSearchInput"
                    type="text"
                    class="w-100 exSearch"
                    :placeholder="libraryTab === 'exercises' ? 'Search for an Exercise' : 'Search workout routines'"
                    v-model="localSearch"
                >
                <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
            </div>
            <div>
                <button class="trans_btn py-1 ps-3" @click="openFilters">
                    <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid">
                </button>
            </div>
        </div>

        <p v-if="libraryTab === 'routines' && selectedRoutineTags.length" class="h8 text-muted px-3 mb-0">
            Filtering routines by {{ selectedRoutineTags.length }} tag(s)
        </p>

        <div class="mt-4 p-3 d-flex justify-content-between shd_card">
            <h5 class="mb-0">
                {{ libraryTab === 'exercises' ? 'Click or drag an exercise to add' : 'Click or drag a workout routine to add' }}
            </h5>
        </div>

        <div v-if="libraryTab === 'exercises'" class="row">
            <div v-for="exr in visibleExercises" :key="'ex-' + exr.id" class="col-xl-3 col-md-4 col-sm-6 col-12 mt-3">
                <div
                    @click="$emit('exercise-click', exr)"
                    class="shd_card p-2 h-100 text-center"
                    style="width:100%;cursor:pointer"
                    draggable="true"
                    @dragstart="$emit('exercise-dragstart', $event, exr)"
                >
                    <div class="w-100 overflow-hidden" style="height:100px">
                        <img :src="exr.image" alt="" class="img-fluid">
                        <div class="py-1" style="background:black;color:white;border-radius: 18px;width: 25px;height: 25px;padding-left: -2px;margin-top: 2px;font-size: 11px;">
                            {{ modifyLanguage(exr.language) }}
                        </div>
                    </div>
                    <div class="w-100 text-center mt-2 mb-0 fw-bold" style="max-height:50px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;">
                        {{ exr.title }}
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="row">
            <div v-for="routine in visibleRoutines" :key="'wr-' + routine.id" class="col-xl-4 col-md-6 col-12 mt-3">
                <div
                    class="shd_card p-2 h-100 text-center drag-el"
                    style="width:100%;cursor:grab"
                    draggable="true"
                    @click="$emit('routine-click', routine)"
                    @dragstart="$emit('routine-dragstart', $event, routine)"
                >
                    <img
                        :src="routine.image || '/images/download1.png'"
                        alt=""
                        class="img-fluid mb-2"
                        style="height: 80px; width: 100%; object-fit: contain; background: #111;"
                    >
                    <p class="mb-0 h8 fw-bold" style="word-break: break-word;">{{ routine.title }}</p>
                    <p class="mb-0 h8 text-muted">Full routine</p>
                </div>
            </div>
            <p v-if="!loadingRoutines && visibleRoutines.length === 0" class="text-center text-muted mt-3 h8 col-12">
                No other workout routines available for this language.
            </p>
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
        language: { type: String, default: 'en' },
        excludeWorkoutId: { type: [Number, String], default: null },
        visibleExercises: { type: Array, default: () => [] },
        search: { type: String, default: '' },
    },
    emits: [
        'update:search',
        'open-filters',
        'exercise-click',
        'exercise-dragstart',
        'routine-click',
        'routine-dragstart',
    ],
    data() {
        return {
            libraryTab: 'exercises',
            routines: [],
            visibleRoutines: [],
            loadingRoutines: false,
            localSearch: this.search,
            filtersOpen: false,
            workoutTags: [],
            selectedRoutineTags: [],
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token'),
                },
            },
        };
    },
    watch: {
        search(value) {
            this.localSearch = value;
        },
        language() {
            if (this.libraryTab === 'routines') {
                this.loadRoutines();
            }
        },
    },
    mounted() {
        this.loadWorkoutTags();
    },
    methods: {
        openFilters() {
            if (this.libraryTab === 'exercises') {
                this.$emit('open-filters');
                return;
            }
            this.filtersOpen = true;
        },
        applyFilters(tagIds) {
            this.selectedRoutineTags = tagIds || [];
            this.loadRoutines();
        },
        clearFilters() {
            this.selectedRoutineTags = [];
            this.loadRoutines();
        },
        loadWorkoutTags() {
            axios.get(config.baseApiUrl + 'get-tags?category=workout', this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.workoutTags = res.data.data || [];
                    }
                })
                .catch(() => {});
        },
        modifyLanguage(language) {
            if (language === 'no') {
                return 'NA';
            }
            return String(language || '').toUpperCase();
        },
        onSearchInput() {
            this.$emit('update:search', this.localSearch);
            if (this.libraryTab === 'routines') {
                this.applyRoutineSearch();
            }
        },
        switchToRoutines() {
            this.libraryTab = 'routines';
            if (this.routines.length === 0) {
                this.loadRoutines();
            } else {
                this.applyRoutineSearch();
            }
        },
        loadRoutines() {
            this.loadingRoutines = true;
            const lang = this.language || 'en';
            let url = config.baseApiUrl + 'all-workouts-list?lang=' + lang;
            if (this.selectedRoutineTags.length > 0) {
                url += '&tag_ids=' + this.selectedRoutineTags.join(',');
            }
            axios.get(url, this.apiConfig)
                .then((res) => {
                    this.loadingRoutines = false;
                    if (res.data.status) {
                        const excludeId = this.excludeWorkoutId != null ? Number(this.excludeWorkoutId) : null;
                        this.routines = (res.data.data || []).filter((item) => {
                            if (excludeId && Number(item.id) === excludeId) {
                                return false;
                            }
                            return true;
                        });
                        this.applyRoutineSearch();
                    }
                })
                .catch(() => {
                    this.loadingRoutines = false;
                });
        },
        applyRoutineSearch() {
            const term = this.localSearch.trim().toLowerCase();
            if (!term) {
                this.visibleRoutines = this.routines;
                return;
            }
            this.visibleRoutines = this.routines.filter((item) =>
                String(item.title || '').toLowerCase().includes(term)
            );
        },
    },
};
</script>

<style scoped>
.drag-el:active {
    cursor: grabbing;
}
.opacity-50 {
    opacity: 0.55;
}
</style>
