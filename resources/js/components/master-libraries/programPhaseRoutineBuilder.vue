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
                <div v-if="scheduleWeeks.length" class="phase-schedule-panel mb-3">
                    <div class="phase-schedule-header">
                        <div>
                            <h5 class="mb-1">Phase Schedule</h5>
                            <p class="mb-0 h8 text-muted">{{ scheduleRoutineCount }} routines across {{ scheduleWeeks.length }} week(s)</p>
                        </div>
                        <span v-if="selectedScheduleDay" class="phase-schedule-pill">
                            {{ selectedScheduleDay.sectionLabel }}
                        </span>
                    </div>
                    <div class="phase-week-tabs mt-3">
                        <button
                            v-for="week in scheduleWeeks"
                            :key="week.weekNo"
                            :class="{ active: week.weekNo === activeWeekNo }"
                            @click="selectScheduleWeek(week.weekNo)"
                        >
                            Week {{ week.weekNo }}
                        </button>
                    </div>
                    <div v-if="selectedScheduleWeek" class="phase-day-grid mt-3">
                        <button
                            v-for="day in selectedScheduleWeek.days"
                            :key="day.key"
                            class="phase-day-card"
                            :class="[dayTypeClass(day.dayType), { active: day.dayNo === activeDayNo }]"
                            @click="selectScheduleDay(day)"
                        >
                            <span>{{ day.dayLabel }}</span>
                            <strong>{{ day.typeLabel }}</strong>
                            <small>{{ day.displayName }}</small>
                            <em>{{ day.exerciseCount }} exercises</em>
                        </button>
                    </div>
                    <div v-if="selectedScheduleDay" class="phase-day-detail mt-3">
                        <div class="phase-day-media">
                            <img :src="selectedScheduleDay.image || '/images/download1.png'" alt="">
                        </div>
                        <div class="phase-day-copy">
                            <h6>{{ selectedScheduleDay.displayName }}</h6>
                            <p class="mb-1">{{ selectedScheduleDay.workoutTitle }}</p>
                            <span>{{ selectedScheduleDay.dayLabel }} | {{ selectedScheduleDay.typeLabel }} | {{ selectedScheduleDay.exerciseCount }} exercises</span>
                        </div>
                    </div>
                </div>
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
                        <span class="h8 text-muted">{{ displayItemsForSection(section.id).length }} routine(s)</span>
                    </div>
                    <div v-if="displayItemsForSection(section.id).length === 0" class="text-center py-4 text-muted h8">
                        Drop a workout routine here
                    </div>
                    <div class="d-flex flex-wrap">
                        <div
                            v-for="item in displayItemsForSection(section.id)"
                            :key="item.id"
                            class="shd_card p-2 m-2 position-relative reorder-card"
                            :id="routineCardId(item)"
                            :class="{ 'phase-routine-active': isSelectedRoutine(item) }"
                            style="width: 180px;"
                            :draggable="!item.ai_section_preview"
                            @dragstart="startPhaseWorkoutDrag(item)"
                            @dragover.prevent="!item.ai_section_preview"
                            @drop.prevent="!item.ai_section_preview && onReorderDrop(section.id, item)"
                        >
                            <div v-if="!item.ai_section_preview" class="reorder-handle h8 text-muted mb-1">
                                <i class="fa-solid fa-grip-vertical me-1"></i> Drag to reorder
                            </div>
                            <div v-else class="h8 text-muted mb-1">AI section preview</div>
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
                                v-if="!item.ai_section_preview"
                                type="checkbox"
                                class="position-absolute form-check-input"
                                style="top:5px;left:10px"
                                :value="item.id"
                                v-model="selectedIds"
                            >
                            <p class="mb-1 fw-bold h7">{{ item.display_name }}</p>
                            <p class="mb-1 h8">
                                {{ item.section_exercise_count ?? item.workout_detail?.workout_exercises_count ?? 0 }} exercises
                            </p>
                            <select
                                v-if="!item.ai_section_preview"
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
                <div class="shd_card p-3 routine-library-card">
                    <div class="d-flex justify-content-between align-items-center mb-2 routine-library-head">
                        <h5 class="mb-0">Workout routines</h5>
                        <span class="h8 text-muted">Drag into a section</span>
                    </div>
                    <div class="routine-toolbar mb-3">
                        <div class="routine-search-wrap">
                            <input
                                v-model="search"
                                type="search"
                                class="routine-search-input"
                                placeholder="Search workout routines"
                                @input="applySearch"
                            >
                            <img class="routine-search-icon" src="/cms-assets/images/navbar-topbar/search.png" alt="search">
                        </div>
                        <button class="routine-filter-btn" @click="filters = true" title="Filter by tags">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="routine-filter-icon">
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
                { id: 'warm_up_cardio', label: 'Warm-up cardio' },
                { id: 'warm_up', label: 'Warm-up routine' },
                { id: 'workout_routine', label: 'Workout routine' },
                { id: 'cardio', label: 'Cardio' },
                { id: 'stretching', label: 'Stretching' },
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
            selectedBuilderWeekNo: null,
            selectedBuilderDayNo: null,
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
        scheduleWeeks() {
            const weekMap = {};
            this.orderedPhaseWorkouts().forEach((item, index) => {
                const displayName = item.display_name || item.workout_detail?.title || 'Workout routine';
                const title = item.workout_detail?.title || displayName;
                const weekMatch = String(displayName).match(/\bweek\s*(\d+)/i);
                const dayMatch = String(displayName).match(/\bday\s*(\d+)/i);
                const sequenceDay = index + 1;
                const weekNo = weekMatch ? Number(weekMatch[1]) : Math.floor((sequenceDay - 1) / 7) + 1;
                const dayNo = dayMatch ? Number(dayMatch[1]) : ((sequenceDay - 1) % 7) + 1;
                const dayType = this.dayTypeForRoutine(item);
                const normalizedSection = this.normalizedSectionTag(item.section_tag);

                if (!weekMap[weekNo]) {
                    weekMap[weekNo] = {
                        weekNo,
                        days: [],
                    };
                }

                weekMap[weekNo].days.push({
                    key: weekNo + '-' + dayNo + '-' + item.id,
                    weekNo,
                    dayNo,
                    dayLabel: 'Day ' + dayNo,
                    typeLabel: this.readableLabel(dayType),
                    dayType,
                    displayName,
                    workoutTitle: title,
                    exerciseCount: item.section_exercise_count ?? item.workout_detail?.workout_exercises_count ?? 0,
                    sectionLabel: this.sectionLabel(normalizedSection),
                    image: item.workout_detail?.image,
                    item,
                    phaseWorkoutId: item.id,
                    sequenceDay,
                });
            });

            return Object.values(weekMap)
                .sort((a, b) => a.weekNo - b.weekNo)
                .map((week) => ({
                    ...week,
                    days: week.days.sort((a, b) => {
                        const dayDiff = a.dayNo - b.dayNo;
                        return dayDiff !== 0 ? dayDiff : a.sequenceDay - b.sequenceDay;
                    }),
                }));
        },
        selectedScheduleWeek() {
            if (this.scheduleWeeks.length === 0) {
                return null;
            }
            return this.scheduleWeeks.find((week) => week.weekNo === this.activeWeekNo) || this.scheduleWeeks[0];
        },
        selectedScheduleDay() {
            if (!this.selectedScheduleWeek || this.selectedScheduleWeek.days.length === 0) {
                return null;
            }
            return this.selectedScheduleWeek.days.find((day) => day.dayNo === this.activeDayNo) || this.selectedScheduleWeek.days[0];
        },
        activeWeekNo() {
            return this.selectedBuilderWeekNo || this.scheduleWeeks[0]?.weekNo || null;
        },
        activeDayNo() {
            return this.selectedBuilderDayNo || this.selectedScheduleWeek?.days?.[0]?.dayNo || null;
        },
        scheduleRoutineCount() {
            return this.orderedPhaseWorkouts().length;
        },
    },
    watch: {
        programLanguage() {
            this.loadWorkouts();
        },
        phaseWorkouts() {
            this.selectedBuilderWeekNo = null;
            this.selectedBuilderDayNo = null;
        },
    },
    mounted() {
        this.loadWorkouts();
        this.loadTags();
    },
    methods: {
        displayItemsForSection(sectionId) {
            const realItems = this.workoutsForSection(sectionId);
            const aiItems = this.aiSectionPreviewItems(sectionId);

            return [...realItems, ...aiItems];
        },
        workoutsForSection(sectionId) {
            return (this.phaseWorkouts || [])
                .filter((item) => this.normalizedSectionTag(item.section_tag) === sectionId)
                .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0));
        },
        aiSectionPreviewItems(sectionId) {
            const sectionKeys = {
                warm_up_cardio: ['warm_up_cardio'],
                warm_up: ['mobility_dynamic_warm_up', 'muscle_activation', 'core_lower_back_preparation'],
                cardio: ['optional_additional_cardio'],
                stretching: ['cool_down_stretching'],
            }[sectionId] || [];

            if (sectionKeys.length === 0) {
                return [];
            }

            return this.orderedPhaseWorkouts()
                .filter((item) => this.normalizedSectionTag(item.section_tag) === 'workout_routine')
                .map((item) => {
                    const routineSections = item.workout_detail?.routine_sections || {};
                    const sectionCount = sectionKeys.reduce((total, key) => {
                        const exercises = routineSections[key]?.exercises;
                        return total + (Array.isArray(exercises) ? exercises.length : 0);
                    }, 0);

                    if (sectionCount === 0) {
                        return null;
                    }

                    return {
                        ...item,
                        id: 'ai-section-' + sectionId + '-' + item.id,
                        ai_section_preview: true,
                        day_number: this.dayNumberForPhaseWorkout(item),
                        display_name: 'Day ' + this.dayNumberForPhaseWorkout(item) + ' - ' + this.sectionLabel(sectionId),
                        section_exercise_count: sectionCount,
                    };
                })
                .filter(Boolean);
        },
        sectionLabel(sectionId) {
            const section = this.sections.find((item) => item.id === sectionId);
            return section ? section.label : sectionId;
        },
        normalizedSectionTag(sectionTag) {
            const tag = sectionTag || 'custom';
            if (['strength_training', 'high_intensity', 'circuit'].includes(tag)) {
                return 'workout_routine';
            }
            if (['fbs', 'ubs', 'lbs', 'psh', 'pul', 'leg', 'glu', 'cor', 'hic', 'msc'].includes(tag)) {
                return 'workout_routine';
            }
            return tag;
        },
        orderedPhaseWorkouts() {
            return [...(this.phaseWorkouts || [])].sort((a, b) => {
                const orderDiff = (a.sort_order ?? 0) - (b.sort_order ?? 0);
                return orderDiff !== 0 ? orderDiff : Number(a.id) - Number(b.id);
            });
        },
        dayNumberForPhaseWorkout(item) {
            if (item.day_number) {
                return item.day_number;
            }
            const index = this.orderedPhaseWorkouts().findIndex((phaseWorkout) => phaseWorkout.id === item.id);
            return index >= 0 ? index + 1 : 1;
        },
        dayTypeForRoutine(item) {
            const label = [
                item.display_name,
                item.workout_detail?.title,
                item.section_tag,
            ].filter(Boolean).join(' ').toLowerCase();

            if (label.includes('rest')) {
                return 'rest';
            }
            if (label.includes('recovery') || label.includes('mobility') || label.includes('stretch')) {
                return 'active_recovery';
            }
            return 'workout';
        },
        selectScheduleWeek(weekNo) {
            this.selectedBuilderWeekNo = weekNo;
            const week = this.scheduleWeeks.find((item) => item.weekNo === weekNo);
            this.selectedBuilderDayNo = week?.days?.[0]?.dayNo || null;
        },
        selectScheduleDay(day) {
            this.selectedBuilderWeekNo = day.weekNo;
            this.selectedBuilderDayNo = day.dayNo;
            this.$nextTick(() => {
                const card = document.getElementById(this.routineCardId(day.item));
                if (card) {
                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },
        dayTypeClass(dayType) {
            return {
                workout: 'phase-day-workout',
                rest: 'phase-day-rest',
                active_recovery: 'phase-day-recovery',
            }[dayType] || 'phase-day-workout';
        },
        readableLabel(value) {
            return String(value || '').replaceAll('_', ' ');
        },
        routineCardId(item) {
            return 'phase-routine-card-' + String(item.id).replace(/[^a-zA-Z0-9_-]/g, '-');
        },
        isSelectedRoutine(item) {
            return !item.ai_section_preview && this.selectedScheduleDay?.phaseWorkoutId === item.id;
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
            if (item.ai_section_preview) {
                return;
            }
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
.phase-schedule-panel {
    background: #fff;
    border: 1px solid #e7e7e7;
    border-radius: 8px;
    padding: 14px;
}
.phase-schedule-header {
    align-items: flex-start;
    display: flex;
    gap: 12px;
    justify-content: space-between;
}
.phase-schedule-header h5 {
    color: #222;
    font-size: 22px;
    font-weight: 600;
}
.phase-schedule-pill {
    background: #f5f5f5;
    border-radius: 6px;
    color: #555;
    flex: 0 0 auto;
    font-size: 12px;
    padding: 5px 9px;
}
.phase-week-tabs {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.phase-week-tabs button {
    background: #fff;
    border: 1px solid #d8d8d8;
    border-radius: 6px;
    color: #444;
    flex: 0 0 auto;
    font-size: 12px;
    padding: 6px 11px;
}
.phase-week-tabs button.active {
    background: #f2a18c;
    border-color: #f2a18c;
    color: #111;
}
.phase-day-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
}
.phase-day-card {
    border: 1px solid #e2e2e2;
    border-radius: 8px;
    min-height: 112px;
    padding: 8px;
    text-align: left;
}
.phase-day-card span,
.phase-day-card strong,
.phase-day-card small,
.phase-day-card em {
    display: block;
    overflow-wrap: anywhere;
}
.phase-day-card span {
    color: #777;
    font-size: 11px;
}
.phase-day-card strong {
    color: #222;
    font-size: 13px;
    text-transform: capitalize;
}
.phase-day-card small {
    color: #555;
    font-size: 11px;
    line-height: 1.25;
    margin-top: 5px;
}
.phase-day-card em {
    color: #777;
    font-size: 11px;
    font-style: normal;
    margin-top: 5px;
}
.phase-day-card.active {
    box-shadow: 0 0 0 2px #f2a18c inset;
}
.phase-day-workout {
    background: #fff;
}
.phase-day-rest {
    background: #f7f7f7;
}
.phase-day-recovery {
    background: #eef7f2;
}
.phase-day-detail {
    align-items: center;
    border: 1px solid #eeeeee;
    border-radius: 8px;
    display: flex;
    gap: 12px;
    padding: 10px;
}
.phase-day-media {
    background: #111;
    border-radius: 6px;
    flex: 0 0 86px;
    height: 66px;
    overflow: hidden;
}
.phase-day-media img {
    height: 100%;
    object-fit: contain;
    width: 100%;
}
.phase-day-copy {
    min-width: 0;
}
.phase-day-copy h6,
.phase-day-copy p,
.phase-day-copy span {
    display: block;
    overflow-wrap: anywhere;
}
.phase-day-copy h6 {
    color: #222;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 2px;
}
.phase-day-copy p,
.phase-day-copy span {
    color: #666;
    font-size: 12px;
}
.phase-routine-active {
    box-shadow: 0 0 0 2px #f2a18c, 0 8px 20px rgba(242, 161, 140, 0.28);
}
.routine-library-card {
    overflow: hidden;
}
.routine-library-head {
    gap: 12px;
}
.routine-toolbar {
    align-items: center;
    display: flex;
    gap: 10px;
}
.routine-search-wrap {
    flex: 1 1 auto;
    min-width: 0;
    position: relative;
}
.routine-search-input {
    border: 1px solid #9c9c9c;
    border-radius: 0;
    color: #333;
    font-size: 14px;
    height: 38px;
    line-height: 38px;
    outline: none;
    padding: 6px 12px 6px 38px;
    width: 100%;
}
.routine-search-input::placeholder {
    color: #8d8d8d;
}
.routine-search-icon {
    height: 22px;
    left: 10px;
    object-fit: contain;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
}
.routine-filter-btn {
    align-items: center;
    background: transparent;
    border: 0;
    display: inline-flex;
    flex: 0 0 42px;
    height: 38px;
    justify-content: center;
    padding: 0;
}
.routine-filter-icon {
    display: block;
    height: 30px;
    object-fit: contain;
    width: 30px;
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
@media (max-width: 575.98px) {
    .phase-schedule-header,
    .routine-library-head {
        align-items: flex-start !important;
        flex-direction: column;
    }
    .phase-day-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .phase-day-detail {
        align-items: flex-start;
    }
    .routine-toolbar {
        gap: 8px;
    }
}
@media (min-width: 576px) and (max-width: 1199.98px) {
    .phase-day-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
}
</style>
