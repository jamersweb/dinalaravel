<template>
    <div class="routine-card">
        <Loader v-if="loading" :loadingText="loadingText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>

        <div class="routine-head">
            <div class="d-flex justify-content-between flex-wrap align-items-center">
                <h2 class="mb-0" style="font-size:26px;">Routine library</h2>
                <div class="d-flex align-items-center flex-wrap">
                    <select v-model="filters.language" @change="reloadReviewScope" class="form-select form-select-sm mx-1 control">
                        <option value="">All languages</option>
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                        <option value="no_audio">No audio</option>
                    </select>
                    <select v-model="filters.equipment_category" @change="reloadReviewScope" class="form-select form-select-sm mx-1 control">
                        <option value="">All equipment</option>
                        <option value="bodyweight">Bodyweight</option>
                        <option value="home_dumbbell">Home Dumbbell</option>
                        <option value="gym">Gym</option>
                        <option value="full_gym">Full Gym</option>
                    </select>
                    <select v-model="filters.fitness_level" @change="reloadReviewScope" class="form-select form-select-sm mx-1 control">
                        <option value="">All levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                    <button class="prim_btn mx-1 py-1" @click="reloadReviewScope">Audit</button>
                </div>
            </div>
        </div>

        <div class="routine-body p-3">
            <div class="row">
                <div class="col-lg-4 col-12 mb-3">
                    <div class="panel h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Audit</strong>
                            <span class="badge" :class="auditStatusClass">{{ auditReport.status || 'unknown' }}</span>
                        </div>
                        <div class="metric-row">
                            <span>Tagged exercises</span>
                            <strong>{{ auditReport.total_tagged_exercises || 0 }}</strong>
                        </div>
                        <div class="metric-row">
                            <span>Approved</span>
                            <strong>{{ auditReport.approved_for_generation || 0 }}</strong>
                        </div>
                        <div class="metric-row">
                            <span>Untagged</span>
                            <strong>{{ auditReport.untagged_exercises || 0 }}</strong>
                        </div>
                        <div class="sync-actions mt-3">
                            <button class="prim_btn py-1 w-100" @click="syncExerciseTags">Sync New Exercises</button>
                            <label class="sync-option">
                                <input type="checkbox" v-model="syncOptions.replace">
                                <span>Re-sync existing tags</span>
                            </label>
                            <label class="sync-option">
                                <input type="checkbox" v-model="syncOptions.approve">
                                <span>Auto-approve synced tags</span>
                            </label>
                            <small>Use after adding new videos or changing exercise tags.</small>
                        </div>
                        <div class="missing-list mt-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Missing</th>
                                        <th class="text-end">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="missingContent.length === 0">
                                        <td colspan="2">None</td>
                                    </tr>
                                    <tr v-for="item in missingContent" :key="item.language + item.equipment_category + item.usage">
                                        <td>{{ missingContentLabel(item) }}</td>
                                        <td class="text-end">{{ item.approved_count }}/{{ item.minimum_required }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8 col-12 mb-3">
                    <div class="panel h-100">
                        <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                            <strong>Generate batch</strong>
                            <div class="d-flex align-items-center">
                                <select v-model.number="generate.target_minutes" class="form-select form-select-sm mx-1 small-input">
                                    <option :value="15">15</option>
                                    <option :value="20">20</option>
                                    <option :value="30">30</option>
                                    <option :value="45">45</option>
                                    <option :value="60">60</option>
                                </select>
                                <input v-model.number="generate.limit" type="number" min="1" max="2340" class="form-control form-control-sm mx-1 small-input">
                                <input v-model.number="generate.variations_per_type" type="number" min="1" max="15" class="form-control form-control-sm mx-1 small-input">
                                <button class="prim_btn mx-1 py-1" @click="generateBatch">Generate</button>
                            </div>
                        </div>
                        <div class="batch-list">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Batch</th>
                                        <th>Status</th>
                                        <th class="text-end">Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="batches.length === 0">
                                        <td colspan="3">No batches</td>
                                    </tr>
                                    <tr v-for="batch in batches" :key="batch.id">
                                        <td>{{ batch.batch_code }}</td>
                                        <td>{{ batch.status }}</td>
                                        <td class="text-end">{{ batch.created_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel launch-panel mb-3">
                <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                    <div>
                        <strong>Launch matrix dashboard</strong>
                        <div class="launch-summary mt-2">
                            <span>Programs {{ launchSummary.programs || 0 }}</span>
                            <span>Built valid {{ launchSummary.built_valid || 0 }}</span>
                            <span>Ready to build {{ launchSummary.ready_to_build || 0 }}</span>
                            <span>Need routines {{ launchSummary.needs_routines || 0 }}</span>
                            <span>Need approval {{ launchSummary.needs_routine_review || 0 }}</span>
                            <span>Invalid {{ launchSummary.built_invalid || 0 }}</span>
                            <span>Blocked {{ launchSummary.blocked || 0 }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap review-controls">
                        <input v-model.number="launchBuild.weeks" type="number" min="1" max="16" class="form-control form-control-sm small-input">
                        <label class="launch-checkbox">
                            <input type="checkbox" v-model="launchBuild.replace">
                            <span>Replace</span>
                        </label>
                        <button class="tiny-btn" @click="fetchLaunchDashboard">Refresh</button>
                        <button class="tiny-btn approve" @click="buildFullLaunchMatrix">Build Matrix</button>
                    </div>
                </div>
                <div class="launch-table">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Program</th>
                                <th>Level</th>
                                <th>Equipment</th>
                                <th>Days</th>
                                <th>Minutes</th>
                                <th>English</th>
                                <th>Arabic</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="launchMatrixReadiness.length === 0">
                                <td colspan="8">Load the launch matrix dashboard.</td>
                            </tr>
                            <tr v-for="program in launchMatrixReadiness" :key="program.number">
                                <td>{{ program.number }}</td>
                                <td>{{ program.name }}</td>
                                <td>{{ program.level }}</td>
                                <td>{{ readableEquipment(program.equipment_category) }}</td>
                                <td>{{ program.days_per_week }}</td>
                                <td>{{ program.minutes }}</td>
                                <td v-for="language in launchLanguages" :key="program.number + language">
                                    <div class="launch-language-cell">
                                        <span class="badge" :class="launchLanguageClass(program, language)">{{ launchLanguageStatus(program, language) }}</span>
                                        <small v-if="launchLanguage(program, language)?.content_code">{{ launchLanguage(program, language).content_code }}</small>
                                        <small v-if="launchLanguage(program, language)?.routine_readiness">
                                            Routines {{ launchLanguage(program, language).routine_readiness.approved_count }}/{{ launchLanguage(program, language).routine_readiness.minimum_required }} approved<span v-if="launchLanguage(program, language).routine_readiness.pending_review_count">, {{ launchLanguage(program, language).routine_readiness.pending_review_count }} pending</span>
                                        </small>
                                        <small v-if="launchLanguage(program, language)?.program_id">ID {{ launchLanguage(program, language).program_id }} - {{ launchLanguage(program, language).days }} days</small>
                                        <button v-if="launchLanguage(program, language)?.status === 'needs_routines'" class="tiny-btn revise" @click="generateLaunchRoutines(program, language)">Generate Routines</button>
                                        <button v-if="launchLanguage(program, language)?.status === 'needs_routine_review'" class="tiny-btn revise" @click="showPendingLaunchRoutines(program, language)">Review Routines</button>
                                        <button v-if="['ready_to_build', 'built_invalid'].includes(launchLanguage(program, language)?.status)" class="tiny-btn approve" @click="buildLaunchProgram(program.number, language)">Build</button>
                                        <button v-if="launchLanguage(program, language)?.program_id" class="tiny-btn" @click="openProgram(launchLanguage(program, language).program_id)">Open</button>
                                        <button v-if="launchLanguage(program, language)?.validation && !launchLanguage(program, language).validation.valid"
                                            class="tiny-btn revise" @click="showLaunchErrors(program, language)">Errors</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel exercise-review-panel mb-3">
                <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                    <div>
                        <strong>Review exercise tags</strong>
                        <div class="review-summary mt-2">
                            <span>All {{ tagSummary.total || 0 }}</span>
                            <span>Pending {{ tagSummary.pending_review || 0 }}</span>
                            <span>Needs fix {{ tagSummary.needs_fix || 0 }}</span>
                            <span>Approved {{ tagSummary.approved || 0 }}</span>
                            <span>Rejected {{ tagSummary.rejected || 0 }}</span>
                            <span>Generation {{ tagSummary.available_for_generation || 0 }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center flex-wrap review-controls">
                        <input v-model="tagFilters.search" @keyup.enter="fetchExerciseTags" class="form-control form-control-sm tag-search" placeholder="Search exercise">
                        <select v-model="tagFilters.review_status" @change="fetchExerciseTags" class="form-select form-select-sm control">
                            <option value="pending_review">Pending</option>
                            <option value="needs_fix">Needs Fix</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="">All</option>
                        </select>
                        <button class="tiny-btn approve" @click="bulkReviewExerciseTags('approved')">Approve Page</button>
                        <button class="tiny-btn reject" @click="bulkReviewExerciseTags('rejected')">Reject Page</button>
                        <button class="prim_btn py-1" @click="fetchExerciseTags">Load</button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-7 col-12 mb-3 mb-lg-0">
                        <div class="exercise-tag-table">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Exercise</th>
                                        <th>Type</th>
                                        <th>Muscle</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="exerciseTags.length === 0">
                                        <td colspan="4">No exercise tags</td>
                                    </tr>
                                    <tr v-for="tag in exerciseTags" :key="tag.id" :class="{ selected: selectedTag && selectedTag.id === tag.id }" @click="selectExerciseTag(tag)">
                                        <td>
                                            <div class="exercise-title">{{ tag.exercise ? tag.exercise.title : ('Exercise #' + tag.exercise_id) }}</div>
                                            <small>{{ tag.exercise ? tag.exercise.content_code : '' }} {{ tag.language }} / {{ readableEquipment(tag.equipment_category) }}</small>
                                        </td>
                                        <td>
                                            <div>{{ readableStatus(tag.primary_category || tag.exercise_type) }}</div>
                                            <small>{{ readableStatus(tag.training_adaptation || '') }}</small>
                                        </td>
                                        <td>{{ tag.muscle_group || '-' }}</td>
                                        <td>{{ readableStatus(tag.review_status) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-lg-5 col-12">
                        <div v-if="selectedTag" class="tag-editor">
                            <div class="media-preview mb-2">
                                <img v-if="selectedTag.exercise && selectedTag.exercise.image" :src="selectedTag.exercise.image" alt="">
                                <video v-else-if="selectedTag.exercise && selectedTag.exercise.video_type !== 'youtube' && selectedTag.exercise.video_url" :src="selectedTag.exercise.video_url" controls></video>
                                <div v-else class="media-empty">No preview</div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label>Language</label>
                                    <select v-model="selectedTag.language" class="form-select form-select-sm">
                                        <option value="en">English</option>
                                        <option value="ar">Arabic</option>
                                        <option value="no_audio">No audio</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Equipment</label>
                                    <select v-model="selectedTag.equipment_category" class="form-select form-select-sm">
                                        <option value="bodyweight">Bodyweight</option>
                                        <option value="home_dumbbell">Home Dumbbell</option>
                                        <option value="gym">Gym</option>
                                        <option value="full_gym">Full Gym</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Difficulty</label>
                                    <select v-model="selectedTag.difficulty" class="form-select form-select-sm">
                                        <option value="beginner">Beginner</option>
                                        <option value="intermediate">Intermediate</option>
                                        <option value="advanced">Advanced</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Status</label>
                                    <select v-model="selectedTag.review_status" class="form-select form-select-sm">
                                        <option value="pending_review">Pending</option>
                                        <option value="needs_fix">Needs Fix</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Primary category</label>
                                    <select v-model="selectedTag.primary_category" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        <option v-for="option in taxonomyOptions.primary_categories" :key="option" :value="option">{{ readableStatus(option) }}</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Training adaptation</label>
                                    <select v-model="selectedTag.training_adaptation" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        <option v-for="option in taxonomyOptions.training_adaptations" :key="option" :value="option">{{ readableStatus(option) }}</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Program role</label>
                                    <select v-model="selectedTag.program_role" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        <option v-for="option in taxonomyOptions.program_roles" :key="option" :value="option">{{ readableStatus(option) }}</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Exercise type</label>
                                    <input v-model="selectedTag.exercise_type" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Muscle group</label>
                                    <input v-model="selectedTag.muscle_group" class="form-control form-control-sm">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Impact</label>
                                    <select v-model="selectedTag.impact_level" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        <option value="low">Low</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Intensity</label>
                                    <select v-model="selectedTag.intensity_level" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        <option value="low">Low</option>
                                        <option value="moderate">Moderate</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Video variant</label>
                                    <select v-model="selectedTag.video_variant" class="form-select form-select-sm">
                                        <option value="explained">Explained</option>
                                        <option value="no_audio">No audio</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Rest seconds</label>
                                    <input v-model.number="selectedTag.recommended_rest_seconds" type="number" min="0" max="600" class="form-control form-control-sm">
                                </div>
                            </div>

                            <label>Usage</label>
                            <div class="usage-grid mb-2">
                                <label v-for="usage in usageOptions" :key="usage" class="usage-option">
                                    <input type="checkbox" v-model="selectedTag.usage_flags[usage]">
                                    <span>{{ readableStatus(usage) }}</span>
                                </label>
                            </div>

                            <label>Safety</label>
                            <div class="usage-grid mb-2">
                                <label class="usage-option">
                                    <input type="checkbox" v-model="selectedTag.safety_flags.safe_for_warmup">
                                    <span>Safe for warm-up</span>
                                </label>
                                <label class="usage-option">
                                    <input type="checkbox" v-model="selectedTag.safety_flags.unsafe_as_warmup">
                                    <span>Unsafe as warm-up</span>
                                </label>
                                <label class="usage-option">
                                    <input type="checkbox" v-model="selectedTag.safety_flags.safe_for_cooldown">
                                    <span>Safe for cooldown</span>
                                </label>
                                <label class="usage-option">
                                    <input type="checkbox" v-model="selectedTag.safety_flags.high_impact">
                                    <span>High impact</span>
                                </label>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-2">
                                    <label>Secondary muscles</label>
                                    <input v-model="secondaryMusclesText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Movement patterns</label>
                                    <input v-model="movementPatternsText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Secondary categories</label>
                                    <input v-model="secondaryCategoriesText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Body regions</label>
                                    <input v-model="bodyRegionsText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Training styles</label>
                                    <input v-model="trainingStylesText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Equipment tags</label>
                                    <input v-model="equipmentTagsText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Injury cautions</label>
                                    <input v-model="injuryText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                                <div class="col-6 mb-2">
                                    <label>Goal fit</label>
                                    <input v-model="goalText" class="form-control form-control-sm" placeholder="comma separated">
                                </div>
                            </div>

                            <label>Notes</label>
                            <textarea v-model="selectedTag.notes" class="form-control form-control-sm mb-2" rows="2"></textarea>

                            <div class="d-flex justify-content-end flex-wrap">
                                <button class="tiny-btn revise" @click="saveExerciseTag('needs_fix')">Needs Fix</button>
                                <button class="tiny-btn reject" @click="saveExerciseTag('rejected')">Reject</button>
                                <button class="tiny-btn" @click="saveExerciseTag('pending_review')">Save Pending</button>
                                <button class="tiny-btn approve" @click="saveExerciseTag('approved')">Approve</button>
                            </div>
                        </div>
                        <div v-else class="empty-editor">Select an exercise to review.</div>
                    </div>
                </div>
            </div>

            <div class="panel routines-panel">
                <div class="d-flex justify-content-between flex-wrap align-items-center mb-2">
                    <strong>Review routines</strong>
                    <div class="d-flex align-items-center flex-wrap review-controls">
                        <select v-model="routineStatus" @change="fetchRoutines" class="form-select form-select-sm control">
                            <option value="">All</option>
                            <option value="pending_review">Pending Review</option>
                            <option value="revision">Revision</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button class="tiny-btn approve" @click="bulkReviewRoutines('approved')">Approve Page</button>
                        <button class="tiny-btn reject" @click="bulkReviewRoutines('rejected')">Reject Page</button>
                    </div>
                </div>
                <div class="routine-table">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Equipment</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th class="text-end">Exercises</th>
                                <th class="text-end">Review</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="routines.length === 0">
                                <td colspan="7">No routines</td>
                            </tr>
                            <tr v-for="routine in routines" :key="routine.id">
                                <td>{{ routine.content_code }}</td>
                                <td>{{ routine.title }}</td>
                                <td>{{ readableEquipment(routine.equipment_category) }}</td>
                                <td>{{ routine.fitness_level }}</td>
                                <td>{{ routine.routine_status }}</td>
                                <td class="text-end">{{ routine.workout_exercises_count }}</td>
                                <td class="text-end">
                                    <button class="tiny-btn approve" @click="reviewRoutine(routine.id, 'approved')">Approve</button>
                                    <button class="tiny-btn revise" @click="reviewRoutine(routine.id, 'revision')">Revision</button>
                                    <button class="tiny-btn reject" @click="reviewRoutine(routine.id, 'rejected')">Reject</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
    emits: ['adminCheckEvent'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            loading: false,
            loadingText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            filters: {
                language: '',
                equipment_category: '',
                fitness_level: ''
            },
            generate: {
                target_minutes: 30,
                limit: 10,
                variations_per_type: 1
            },
            syncOptions: {
                replace: false,
                approve: false
            },
            launchDashboard: {},
            launchBuild: {
                weeks: 12,
                replace: false
            },
            auditReport: {},
            batches: [],
            routines: [],
            routineStatus: 'pending_review',
            exerciseTags: [],
            selectedTag: null,
            tagSummary: {},
            tagFilters: {
                search: '',
                review_status: 'pending_review',
                per_page: 25
            },
            taxonomyOptions: {
                primary_categories: [],
                training_adaptations: [],
                program_roles: [],
                body_regions: []
            },
            usageOptions: [
                'cardio_warm_up',
                'warm_up',
                'mobility',
                'lower_back_activation',
                'main_workout',
                'abs',
                'obliques',
                'lower_back_strength',
                'stretching'
            ]
        };
    },
    computed: {
        missingContent() {
            return this.auditReport.missing_content || [];
        },
        launchMatrixReadiness() {
            return this.launchDashboard.programs || this.auditReport.launch_matrix_readiness || [];
        },
        launchSummary() {
            return this.launchDashboard.summary || {};
        },
        launchLanguages() {
            return this.launchDashboard.languages || ['en', 'ar'];
        },
        auditStatusClass() {
            return this.auditReport.status === 'ready' ? 'badge-ready' : 'badge-blocked';
        },
        secondaryMusclesText: {
            get() {
                return this.arrayFieldText('secondary_muscle_groups');
            },
            set(value) {
                this.setArrayField('secondary_muscle_groups', value);
            }
        },
        movementPatternsText: {
            get() {
                return this.arrayFieldText('movement_patterns');
            },
            set(value) {
                this.setArrayField('movement_patterns', value);
            }
        },
        secondaryCategoriesText: {
            get() {
                return this.arrayFieldText('secondary_categories');
            },
            set(value) {
                this.setArrayField('secondary_categories', value);
            }
        },
        bodyRegionsText: {
            get() {
                return this.arrayFieldText('body_regions');
            },
            set(value) {
                this.setArrayField('body_regions', value);
            }
        },
        trainingStylesText: {
            get() {
                return this.arrayFieldText('training_styles');
            },
            set(value) {
                this.setArrayField('training_styles', value);
            }
        },
        equipmentTagsText: {
            get() {
                return this.arrayFieldText('equipment_tags');
            },
            set(value) {
                this.setArrayField('equipment_tags', value);
            }
        },
        injuryText: {
            get() {
                return (this.selectedTag && Array.isArray(this.selectedTag.injury_cautions))
                    ? this.selectedTag.injury_cautions.join(', ')
                    : '';
            },
            set(value) {
                if (this.selectedTag) {
                    this.selectedTag.injury_cautions = this.csvToArray(value);
                }
            }
        },
        goalText: {
            get() {
                return (this.selectedTag && Array.isArray(this.selectedTag.goal_fit))
                    ? this.selectedTag.goal_fit.join(', ')
                    : '';
            },
            set(value) {
                if (this.selectedTag) {
                    this.selectedTag.goal_fit = this.csvToArray(value);
                }
            }
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.fetchAudit();
        this.fetchLaunchDashboard();
        this.fetchBatches();
        this.fetchRoutines();
        this.fetchExerciseTags();
    },
    methods: {
        selectedFilterParams(levelKey = null) {
            const params = {};
            if (this.filters.language) {
                params.language = this.filters.language;
            }
            if (this.filters.equipment_category) {
                params.equipment_category = this.filters.equipment_category;
            }
            if (levelKey && this.filters.fitness_level) {
                params[levelKey] = this.filters.fitness_level;
            }
            return params;
        },
        reloadReviewScope() {
            this.fetchAudit();
            this.fetchRoutines();
            this.fetchExerciseTags();
        },
        fetchAudit() {
            this.loading = true;
            this.loadingText = 'Auditing';
            axios.get(config.baseApiUrl + 'routine-library/audit', {
                ...this.apiConfig,
                params: this.selectedFilterParams()
            }).then(res => {
                this.loading = false;
                this.auditReport = res.data.data || {};
            }).catch(er => this.showError(er.message));
        },
        fetchLaunchDashboard() {
            axios.get(config.baseApiUrl + 'routine-library/launch-matrix', {
                ...this.apiConfig,
                params: {
                    weeks: this.launchBuild.weeks || 12
                }
            }).then(res => {
                this.launchDashboard = res.data.data || {};
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        fetchBatches() {
            axios.get(config.baseApiUrl + 'routine-library/batches', this.apiConfig).then(res => {
                this.batches = (res.data.data && res.data.data.data) ? res.data.data.data : [];
            }).catch(() => {});
        },
        fetchRoutines() {
            const params = this.selectedFilterParams('fitness_level');
            if (this.routineStatus) {
                params.status = this.routineStatus;
            }
            axios.get(config.baseApiUrl + 'routine-library/routines', {
                ...this.apiConfig,
                params
            }).then(res => {
                this.routines = (res.data.data && res.data.data.data) ? res.data.data.data : [];
            }).catch(() => {});
        },
        fetchExerciseTags() {
            const params = {
                ...this.selectedFilterParams('difficulty'),
                per_page: this.tagFilters.per_page
            };
            if (this.tagFilters.search) {
                params.search = this.tagFilters.search;
            }
            if (this.tagFilters.review_status) {
                params.review_status = this.tagFilters.review_status;
            }

            axios.get(config.baseApiUrl + 'routine-library/exercise-tags', {
                ...this.apiConfig,
                params
            }).then(res => {
                this.exerciseTags = (res.data.data && res.data.data.data) ? res.data.data.data : [];
                this.tagSummary = res.data.summary || {};
                if (res.data.options && res.data.options.usage_flags) {
                    this.usageOptions = res.data.options.usage_flags;
                }
                if (res.data.options) {
                    this.taxonomyOptions.primary_categories = res.data.options.primary_categories || [];
                    this.taxonomyOptions.training_adaptations = res.data.options.training_adaptations || [];
                    this.taxonomyOptions.program_roles = res.data.options.program_roles || [];
                    this.taxonomyOptions.body_regions = res.data.options.body_regions || [];
                }
                this.selectedTag = this.exerciseTags.length ? this.normalizedTag(this.exerciseTags[0]) : null;
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        selectExerciseTag(tag) {
            this.selectedTag = this.normalizedTag(tag);
        },
        normalizedTag(tag) {
            const copy = JSON.parse(JSON.stringify(tag));
            copy.equipment_tags = Array.isArray(copy.equipment_tags) ? copy.equipment_tags : [];
            copy.secondary_muscle_groups = Array.isArray(copy.secondary_muscle_groups) ? copy.secondary_muscle_groups : [];
            copy.secondary_categories = Array.isArray(copy.secondary_categories) ? copy.secondary_categories : [];
            copy.body_regions = Array.isArray(copy.body_regions) ? copy.body_regions : [];
            copy.movement_patterns = Array.isArray(copy.movement_patterns) ? copy.movement_patterns : [];
            copy.training_styles = Array.isArray(copy.training_styles) ? copy.training_styles : [];
            copy.workout_sections = Array.isArray(copy.workout_sections) ? copy.workout_sections : [];
            copy.safety_notes = Array.isArray(copy.safety_notes) ? copy.safety_notes : [];
            copy.contraindications = Array.isArray(copy.contraindications) ? copy.contraindications : [];
            copy.injury_cautions = Array.isArray(copy.injury_cautions) ? copy.injury_cautions : [];
            copy.goal_fit = Array.isArray(copy.goal_fit) ? copy.goal_fit : [];
            copy.usage_flags = copy.usage_flags && typeof copy.usage_flags === 'object' ? copy.usage_flags : {};
            copy.safety_flags = copy.safety_flags && typeof copy.safety_flags === 'object' ? copy.safety_flags : {};
            this.usageOptions.forEach(key => {
                copy.usage_flags[key] = copy.usage_flags[key] === true || copy.usage_flags[key] === 1 || copy.usage_flags[key] === 'true';
            });
            ['safe_for_warmup', 'safe_for_cooldown', 'unsafe_as_warmup', 'high_impact', 'explosive'].forEach(key => {
                copy.safety_flags[key] = copy.safety_flags[key] === true || copy.safety_flags[key] === 1 || copy.safety_flags[key] === 'true';
            });
            copy.review_status = copy.review_status || (copy.approved_for_generation ? 'approved' : 'pending_review');
            return copy;
        },
        saveExerciseTag(status) {
            if (!this.selectedTag) {
                return;
            }
            this.selectedTag.review_status = status || this.selectedTag.review_status;
            axios.post(config.baseApiUrl + 'routine-library/exercise-tags/' + this.selectedTag.id + '/review', {
                language: this.selectedTag.language,
                equipment_category: this.selectedTag.equipment_category,
                equipment_tags: this.selectedTag.equipment_tags || [],
                primary_category: this.selectedTag.primary_category || null,
                secondary_categories: this.selectedTag.secondary_categories || [],
                training_adaptation: this.selectedTag.training_adaptation || null,
                program_role: this.selectedTag.program_role || null,
                muscle_group: this.selectedTag.muscle_group,
                secondary_muscle_groups: this.selectedTag.secondary_muscle_groups || [],
                body_regions: this.selectedTag.body_regions || [],
                exercise_type: this.selectedTag.exercise_type,
                movement_patterns: this.selectedTag.movement_patterns || [],
                training_styles: this.selectedTag.training_styles || [],
                workout_sections: this.selectedTag.workout_sections || [],
                impact_level: this.selectedTag.impact_level || null,
                intensity_level: this.selectedTag.intensity_level || null,
                video_variant: this.selectedTag.video_variant || 'explained',
                recommended_duration_seconds: this.selectedTag.recommended_duration_seconds || null,
                recommended_repetitions: this.selectedTag.recommended_repetitions || null,
                recommended_sets: this.selectedTag.recommended_sets || null,
                recommended_rest_seconds: this.selectedTag.recommended_rest_seconds || 0,
                safety_notes: this.selectedTag.safety_notes || [],
                contraindications: this.selectedTag.contraindications || [],
                difficulty: this.selectedTag.difficulty,
                injury_cautions: this.selectedTag.injury_cautions || [],
                goal_fit: this.selectedTag.goal_fit || [],
                usage_flags: this.selectedTag.usage_flags || {},
                safety_flags: this.selectedTag.safety_flags || {},
                review_status: this.selectedTag.review_status,
                notes: this.selectedTag.notes
            }, this.apiConfig).then(res => {
                this.modalTitle = 'Done';
                this.modalDetail = res.data.message || 'Exercise tag updated';
                this.informModal = true;
                this.fetchExerciseTags();
                this.fetchAudit();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        bulkReviewExerciseTags(status) {
            const ids = this.exerciseTags.map(tag => tag.id);
            if (ids.length === 0) {
                return;
            }
            axios.post(config.baseApiUrl + 'routine-library/exercise-tags/bulk-review', {
                ids,
                review_status: status
            }, this.apiConfig).then(res => {
                this.modalTitle = 'Done';
                this.modalDetail = res.data.message || 'Exercise tags updated';
                this.informModal = true;
                this.fetchExerciseTags();
                this.fetchAudit();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        syncExerciseTags() {
            if (this.syncOptions.replace && !window.confirm('Re-sync existing tags? Existing approvals will be kept unless Auto-approve is also checked.')) {
                return;
            }
            if (this.syncOptions.approve && !window.confirm('Auto-approve synced tags for generation? Use this only when the uploaded exercise metadata is already trusted.')) {
                return;
            }
            this.loading = true;
            this.loadingText = 'Syncing exercises';
            axios.post(config.baseApiUrl + 'routine-library/sync-exercise-tags', {
                replace: this.syncOptions.replace,
                approve: this.syncOptions.approve,
                preserve_review_status: true
            }, this.apiConfig).then(res => {
                this.loading = false;
                this.modalTitle = 'Done';
                this.modalDetail = this.syncMessage(res.data.data, res.data.message);
                this.informModal = true;
                this.fetchAudit();
                this.fetchExerciseTags();
                this.fetchLaunchDashboard();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        generateBatch() {
            if (! this.filters.language || ! this.filters.equipment_category || ! this.filters.fitness_level) {
                this.showError('Choose language, equipment, and level before generating a batch.');
                return;
            }
            this.loading = true;
            this.loadingText = 'Generating';
            axios.post(config.baseApiUrl + 'routine-library/generate-batch', {
                language: this.filters.language,
                equipment_category: this.filters.equipment_category,
                fitness_level: this.filters.fitness_level,
                target_minutes: this.generate.target_minutes,
                limit: this.generate.limit,
                variations_per_type: this.generate.variations_per_type
            }, this.apiConfig).then(res => {
                this.loading = false;
                this.modalTitle = res.data.status ? 'Done' : 'Blocked';
                this.modalDetail = res.data.message || 'Batch updated';
                this.informModal = true;
                this.auditReport = (res.data.data && res.data.data.missing_content_report) ? res.data.data.missing_content_report : this.auditReport;
                this.fetchBatches();
                this.fetchRoutines();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        generateLaunchRoutines(program, language) {
            const state = this.launchLanguage(program, language);
            const payload = state?.routine_readiness?.generate_payload;
            if (!payload) {
                this.showError('No routine generation payload is available for this launch program.');
                return;
            }
            this.loading = true;
            this.loadingText = 'Generating launch routines';
            axios.post(config.baseApiUrl + 'routine-library/generate-batch', payload, this.apiConfig).then(res => {
                this.loading = false;
                this.modalTitle = res.data.status ? 'Done' : 'Blocked';
                this.modalDetail = res.data.message || 'Routine batch generated for review.';
                this.informModal = true;
                this.filters.language = payload.language;
                this.filters.equipment_category = payload.equipment_category;
                this.filters.fitness_level = payload.fitness_level;
                this.routineStatus = 'pending_review';
                this.fetchBatches();
                this.fetchRoutines();
                this.fetchLaunchDashboard();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        showPendingLaunchRoutines(program, language) {
            const state = this.launchLanguage(program, language);
            const payload = state?.routine_readiness?.generate_payload || {};
            this.filters.language = payload.language || language;
            this.filters.equipment_category = payload.equipment_category || program.equipment_category;
            this.filters.fitness_level = payload.fitness_level || program.level;
            this.routineStatus = 'pending_review';
            this.fetchRoutines();
            this.modalTitle = 'Review routines';
            this.modalDetail = 'Approve the pending generated routines below, then refresh the launch matrix and build the program.';
            this.informModal = true;
        },
        buildFullLaunchMatrix() {
            this.loading = true;
            this.loadingText = 'Building launch matrix';
            axios.post(config.baseApiUrl + 'routine-library/launch-matrix/build', {
                weeks: this.launchBuild.weeks || 12,
                replace: this.launchBuild.replace
            }, this.apiConfig).then(res => {
                this.loading = false;
                this.modalTitle = res.data.status ? 'Done' : 'Blocked';
                this.modalDetail = this.launchBuildMessage(res.data.data, res.data.message);
                this.informModal = true;
                this.fetchLaunchDashboard();
                this.fetchAudit();
            }).catch(er => {
                this.loading = false;
                this.modalTitle = 'Blocked';
                this.modalDetail = er.response?.data?.message || er.message;
                this.informModal = true;
                this.fetchLaunchDashboard();
            });
        },
        buildLaunchProgram(number, language) {
            this.loading = true;
            this.loadingText = 'Building launch program';
            axios.post(config.baseApiUrl + 'routine-library/launch-matrix/build', {
                number,
                language,
                weeks: this.launchBuild.weeks || 12,
                replace: this.launchBuild.replace
            }, this.apiConfig).then(res => {
                this.loading = false;
                this.modalTitle = res.data.status ? 'Done' : 'Blocked';
                this.modalDetail = res.data.message || 'Launch program updated';
                this.informModal = true;
                this.fetchLaunchDashboard();
            }).catch(er => {
                this.loading = false;
                this.modalTitle = 'Blocked';
                this.modalDetail = er.response?.data?.message || er.message;
                this.informModal = true;
                this.fetchLaunchDashboard();
            });
        },
        reviewRoutine(id, status) {
            axios.post(config.baseApiUrl + 'routine-library/routines/' + id + '/review', {
                status
            }, this.apiConfig).then(res => {
                this.modalTitle = res.data.status ? 'Done' : 'Blocked';
                this.modalDetail = res.data.message || 'Routine updated';
                this.informModal = true;
                this.fetchRoutines();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        bulkReviewRoutines(status) {
            const ids = this.routines.map(routine => routine.id);
            if (ids.length === 0) {
                return;
            }
            axios.post(config.baseApiUrl + 'routine-library/routines/bulk-review', {
                ids,
                status
            }, this.apiConfig).then(res => {
                this.modalTitle = 'Done';
                this.modalDetail = res.data.message || 'Routines updated';
                this.informModal = true;
                this.fetchRoutines();
                this.fetchAudit();
            }).catch(er => this.showError(er.response?.data?.message || er.message));
        },
        readableEquipment(value) {
            return String(value || '').replace('_', ' ');
        },
        readableStatus(value) {
            return String(value || '').replaceAll('_', ' ');
        },
        readableLanguage(value) {
            const map = {
                en: 'English',
                ar: 'Arabic',
                no_audio: 'No audio'
            };
            return map[value] || value;
        },
        missingContentLabel(item) {
            const scope = [];
            if (item.language) {
                scope.push(this.readableLanguage(item.language));
            }
            if (item.equipment_category) {
                scope.push(this.readableEquipment(item.equipment_category));
            }
            return scope.length ? item.label + ' - ' + scope.join(' / ') : item.label;
        },
        launchLanguageStatus(program, language) {
            const state = this.launchLanguage(program, language);
            if (!state) {
                return 'unknown';
            }
            const labels = {
                built_valid: 'built',
                built_invalid: 'invalid',
                ready_to_build: 'ready',
                needs_review: 'review',
                needs_routines: 'routines needed',
                needs_routine_review: 'approve routines',
                blocked: 'blocked'
            };
            if (labels[state.status]) {
                return labels[state.status];
            }
            const missing = Array.isArray(state.readiness?.missing_content) ? state.readiness.missing_content.length : 0;
            return missing ? `missing ${missing}` : 'blocked';
        },
        launchLanguageClass(program, language) {
            const state = this.launchLanguage(program, language);
            if (state && ['built_valid', 'ready_to_build'].includes(state.status)) {
                return 'badge-ready';
            }
            if (state && ['needs_review', 'needs_routine_review'].includes(state.status)) {
                return 'badge-review';
            }
            return 'badge-blocked';
        },
        launchLanguage(program, language) {
            return program.languages && program.languages[language] ? program.languages[language] : null;
        },
        launchBuildMessage(data, fallback) {
            if (!data || !data.summary) {
                return fallback || 'Launch matrix updated';
            }
            const summary = data.summary;
            return `${fallback || 'Launch matrix updated'} Created ${summary.created || 0}, existing ${summary.existing || 0}, blocked ${summary.blocked || 0}, invalid ${summary.invalid || 0}.`;
        },
        syncMessage(data, fallback) {
            const summary = data?.summary || {};
            const reasons = summary.skipped_reasons || {};
            const reasonText = Object.keys(reasons).slice(0, 4).map(key => `${key}: ${reasons[key]}`).join(', ');
            return `${fallback || 'Exercise sync complete.'}${reasonText ? ' Skipped: ' + reasonText + '.' : ''}`;
        },
        showLaunchErrors(program, language) {
            const state = this.launchLanguage(program, language);
            const errors = state?.validation?.errors || [];
            this.modalTitle = 'Validation errors';
            this.modalDetail = this.launchErrorSummary(errors);
            this.informModal = true;
        },
        launchErrorSummary(errors) {
            if (!Array.isArray(errors) || errors.length === 0) {
                return 'No error details.';
            }

            const seen = new Set();
            const lines = [];
            errors.forEach(error => {
                const detail = error.routine_error || error;
                const location = [];
                if (error.workout_id) {
                    location.push(`routine ${error.workout_id}`);
                }
                if (error.week_no && error.day_no) {
                    location.push(`week ${error.week_no} day ${error.day_no}`);
                }
                if (detail.section) {
                    location.push(this.readableStatus(detail.section));
                }
                if (detail.exercise_id) {
                    location.push(`exercise ${detail.exercise_id}`);
                }

                const code = detail.code || error.code || 'validation_error';
                const message = detail.message || error.message || 'Validation failed.';
                const line = `${location.length ? location.join(' / ') + ': ' : ''}${code} - ${message}`;
                if (seen.has(line)) {
                    return;
                }
                seen.add(line);
                lines.push(line);
            });

            const visible = lines.slice(0, 10);
            if (lines.length > visible.length) {
                visible.push(`...and ${lines.length - visible.length} more.`);
            }

            return visible.join('\n');
        },
        openProgram(programId) {
            this.$router.push({ path: '/cms/program', query: { program_id: programId } });
        },
        arrayFieldText(field) {
            return (this.selectedTag && Array.isArray(this.selectedTag[field]))
                ? this.selectedTag[field].join(', ')
                : '';
        },
        setArrayField(field, value) {
            if (this.selectedTag) {
                this.selectedTag[field] = this.csvToArray(value);
            }
        },
        csvToArray(value) {
            return String(value || '')
                .split(',')
                .map(item => item.trim())
                .filter(Boolean);
        },
        showError(message) {
            this.loading = false;
            this.modalTitle = 'Error';
            this.modalDetail = message || 'Something went wrong';
            this.informModal = true;
        },
        acknowledged() {
            this.informModal = false;
        }
    }
};
</script>

<style scoped>
.routine-card {
    border: 1px solid rgb(226, 224, 224);
    border-radius: 1rem;
    overflow: hidden;
    height: calc(100vh - 125px);
}

.routine-head {
    background-color: rgb(226, 224, 224);
    padding: 8px 10px;
}

.routine-body {
    height: calc(100% - 54px);
    overflow-y: auto;
}

.panel {
    background: #fff;
    border: 1px solid #dedede;
    border-radius: 8px;
    padding: 14px;
}

.control {
    width: 160px;
}

.small-input {
    width: 86px;
}

.metric-row {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #eeeeee;
    padding: 7px 0;
}

.sync-actions {
    border: 1px solid #eeeeee;
    border-radius: 8px;
    padding: 10px;
}

.sync-actions small {
    color: #666;
    display: block;
    font-size: 11px;
    line-height: 1.35;
    margin-top: 6px;
}

.sync-option {
    align-items: center;
    display: flex;
    gap: 6px;
    margin: 8px 0 0;
}

.sync-option span {
    color: #555;
    font-size: 12px;
}

.missing-list,
.batch-list,
.launch-table,
.routine-table {
    overflow: auto;
}

.launch-panel {
    min-height: 220px;
}

.launch-table .badge {
    display: inline-block;
    min-width: 72px;
    text-align: center;
}

.routines-panel {
    min-height: 320px;
}

.exercise-review-panel {
    min-height: 430px;
}

.review-controls {
    gap: 8px;
}

.review-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.review-summary span,
.launch-summary span {
    background: #f5f5f5;
    border: 1px solid #e3e3e3;
    border-radius: 6px;
    color: #333;
    font-size: 12px;
    padding: 4px 7px;
}

.launch-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.launch-checkbox {
    align-items: center;
    display: flex;
    gap: 5px;
    margin: 0 4px;
}

.launch-checkbox span {
    color: #555;
    font-size: 12px;
}

.launch-language-cell {
    align-items: flex-start;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 132px;
}

.launch-language-cell small {
    color: #666;
    font-size: 11px;
    overflow-wrap: anywhere;
}

.tag-search {
    width: 210px;
}

.exercise-tag-table {
    border: 1px solid #eeeeee;
    max-height: 360px;
    overflow: auto;
}

.exercise-tag-table tr {
    cursor: pointer;
}

.exercise-tag-table tr.selected {
    background: #f1f7ff;
}

.exercise-title {
    font-weight: 600;
    max-width: 360px;
}

.tag-editor {
    border: 1px solid #eeeeee;
    padding: 10px;
}

.tag-editor label {
    color: #555;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 3px;
}

.media-preview {
    align-items: center;
    background: #f7f7f7;
    display: flex;
    height: 150px;
    justify-content: center;
    overflow: hidden;
}

.media-preview img,
.media-preview video {
    height: 100%;
    object-fit: contain;
    width: 100%;
}

.media-empty,
.empty-editor {
    align-items: center;
    color: #777;
    display: flex;
    min-height: 150px;
    justify-content: center;
}

.usage-grid {
    display: grid;
    gap: 6px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.usage-option {
    align-items: center;
    border: 1px solid #eeeeee;
    display: flex;
    gap: 6px;
    min-height: 30px;
    padding: 4px 6px;
}

.usage-option span {
    font-size: 12px;
    overflow-wrap: anywhere;
}

.badge {
    padding: 5px 8px;
    border-radius: 6px;
    text-transform: capitalize;
}

.badge-ready {
    background: #e5f8ec;
    color: #147333;
}

.badge-blocked {
    background: #fff1e5;
    color: #9a4b00;
}

.badge-review {
    background: #eef4ff;
    color: #2456a6;
}

.tiny-btn {
    border: 1px solid #cfcfcf;
    background: #fff;
    border-radius: 5px;
    font-size: 12px;
    margin-left: 4px;
    padding: 3px 7px;
}

.approve {
    color: #147333;
}

.revise {
    color: #9a4b00;
}

.reject {
    color: #a52a2a;
}

@media (max-width: 575.98px) {
    .tag-search,
    .control {
        width: 100%;
    }

    .review-controls {
        align-items: stretch !important;
        width: 100%;
    }

    .usage-grid {
        grid-template-columns: 1fr;
    }
}
</style>
