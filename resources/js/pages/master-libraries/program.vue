<template>
    <NewProgram v-if="newProgVisible" />
    <NewWorkout v-if="newWrkPop" />
    <BuildWorkout v-if="workoutBuilder" :workout="newWorkout"/>
    <ImportWorkout v-if="importWorkComp" :phase_id="idForAction" :language_type="programLanguage" />
    <browseExerciseLibrary v-if="browselibrary" />
    <SubUsers v-if="userSubComp" :program_id="idForAction" />
    <GetInput v-if="getInputModal" :msgTitle="modalTitle" :msgDetail="modalDetail" :renameValue="modalValue" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <GetInput v-if="editNamePopup" :msgTitle="modalTitle" :msgDetail="modalDetail" :renameValue="modalValue" />
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
    <WorkoutDetail v-if="showWrktDetail" :wrkId="workoutId"/>
    <div v-if="removeSubsDiv" style="height: 100%;width: 100%;position: absolute;z-index: 999;top: 0;left: 0;"
        @click="removeSubsDiv = false"></div>
    <div v-if="sendMessageDiv" class="my-popup-component">
        <div class="position-relative brds-3"
            style="width: 50%;height: 220px;background-color: white;text-align: center;">
            <button class="trans_btn position-absolute" @click="sendMessageDiv = false"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mx-auto mt-4" style="font-size: 30px;width: 80%;">Message</p>
            <div class="mx-auto brds-3" style="width:80%;background-color: #EEEEEE;height: 70px;text-align: center;">
                <input class="brds-2"
                    style="border: 1px solid #C5C5C5;color: #C5C5C5;width: 90%;height: 40px;margin-top: 15px;"
                    type="text" placeholder="Enter Message here" v-model="textMessage">
            </div>
            <div class="w-100 mt-3">
                <button @click="sendMessage" style="width: 100px;"
                    class="prim_bg px-4 py-1 border-0 brds-2">Send</button>
            </div>
        </div>
    </div>
    <div
        style="display: flex; border: 1px solid #e7e7e7; border-radius: 1em;overflow: hidden;width: 100%;height: calc(100vh - 124px);">
        <div class="masterpanel">
            <div
                style="display: flex;align-items: baseline;margin: 10px 5px;position: relative;justify-content: space-between;">
                <p style="font-size: 12px;font-weight: 400;margin-bottom: 10px;margin-left: 10px;">Master Programs</p>
                <div class="dropdown">
                    <button class="prim_btn py-1 prim_btn_rnd dropdown-toggle" type="button" id="dropdownMenuButton1"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        New
                    </button>
                    <ul class="tsl dropdown-menu border-0" aria-labelledby="dropdownMenuButton1">
                        <li><button class="dropdown-item" style="text-align: center;" @click="toggleNewProgram()">Build
                                New</button></li>
                        <li><button class="dropdown-item" style="text-align: center;"
                                @click="toogleBrowseLibrary()">Browse Fitness
                                Library</button></li>
                    </ul>
                </div>
            </div>
            <div class="position-relative">
                <input @input="applySearch()" class="searchinput" type="search" placeholder="Search Programs" v-model="search" />
                <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" />
            </div>
            <div class="program-list mt-2" style="overflow-y: auto;height:calc(100% - 103px);">
                <p v-if="finalVisiblePrograms.length < 1" class="f-20 fw-bold col-12" style="text-align:center;">No Program to Display</p>
                <div style="border-bottom:1px solid black" v-for="(prog, index) in finalVisiblePrograms" :key="prog.id">
                    <div @click="toggleProgDetail(index, prog.id, prog.weeks)"
                        class="pointer d-flex justify-content-between py-3 ps-4 pe-2"
                        style="background-color: #e3e3e3;">
                        <div class="">
                            <h6 style="font-size:15px">{{ prog.title }}</h6>
                            <p class="h8 mb-0 prim_txt">{{ prog.weeks }} Weeks - {{ prog.language==='en'?'English':(prog.language==='ar'?'Arabic':'None') }}</p>
                        </div>
                        <div class="align-self-center d-flex">
                            <i v-if="detailOpen[index]" class="fa-solid fa-chevron-up align-self-center"></i>
                            <i v-else class="fa-solid fa-chevron-down align-self-center"></i>
                            <div class="ms-2 dropdown">
                                <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="tsl dropdown-menu border-0">
                                    <li><button class="dropdown-item"
                                            @click="showRenamePopup(prog.id, 'program', prog.title)">Rename</button>
                                    </li>
                                    <li><button class="dropdown-item" @click="showConfirmModal(prog.id)">Delete</button>
                                    </li>
                                    <li><button class="dropdown-item" @click="toggleSubsAdd(prog.id)">Add
                                            Subscribers</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white py-3 ps-4 pe-2" v-show="detailOpen[index]">
                        <p @click="programDetailVisible = true, phaseDetailVisible = false"
                            class="mb-1 prim_txt fw-bold h8 pointer">
                            Summary and Subscribers</p>
                        <p class="h8 mb-1">Calendar</p>
                        <p class="mb-1" style="font-size:13px;">TRAINING PHASES</p>
                        <div class="trans_btn py-1 px-2 pointer" v-for="phase in prog.program_phases" :key="phase.id">
                            <span class="h8 me-3" @click="fetchPhaseDetail(phase.id)">{{ phase.name }}</span>
                            <span class="h8 prim_txt" @click="fetchPhaseDetail(phase.id)">({{ phase.weeks }}
                                weeks)</span>
                            <div @click.stop="" class="float-end dropdown">
                                <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="tsl dropdown-menu border-0">
                                    <li><button class="dropdown-item"
                                            @click="showPhaseDelete(phase.id, index, prog.id, prog.weeks)">Delete</button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="masterscreen position-relative">
            <button v-if="!(programDetailVisible || phaseDetailVisible)" @click="filters = true"
                style="border:none;background-color:transparent;padding-top:12px;position: absolute;right: 10px;top: 5px;">
                <img src="/cms-assets/images/master-libraries/filter.png" alt="" style="height:15px;" class="img-fluid">
            </button>
            <div v-if="programDetailVisible" class="gray_bg" style="width:100%;height: 50px;">
                <h3 v-if="programDetailVisible" class="mb-0 float-start mt-2 ms-3" style="font-size: 26px;">
                    {{programDetail.title }} 
                    <span v-if="programDetail.language == 'en'">(English)</span>
                    <span v-if="programDetail.language == 'ar'">(Arabic)</span>
                    <span v-else>(None)</span>
                </h3>
                <div class="float-end mx-2">

                </div>
            </div>
            <div style="margin-top: 20%" v-if="!(programDetailVisible || phaseDetailVisible)">
                <p style="font-size: 36px;margin-bottom: 0px;font-weight: bold;text-align: center;">DELIVER A CONSISTENT
                    TRAINING</p>
                <p style="font-size: 24px;margin-bottom: 0px;font-weight: 500;color: #707070;text-align: center;">
                    experience with Shared Master Programs.</p>
                <p style="font-size: 18px;margin-bottom: 0px;font-weight: 400;color: #b1b0b0;text-align: center;">
                    Access a library of pre-built Programs available to all
                    <br />
                    company trainers and use them to save time and deliver a consistent
                    <br />
                    training experience business-wide.
                </p>
            </div>
            <div v-if="programDetailVisible">
                <div class="px-2">
                    <div class="d-flex justify-content-between p-3">
                        <h5 class="prim_txt mb-0">{{ tempWeeks }} weeks</h5>
                        <!-- <button class="mb-0 fw-bold h5 rounded-circle border-0 prim_bg"
                            style="height: 25px;width: 25px;">+</button> -->
                    </div>
                    <div class="position-relative px-2 pt-4 pb-1 mb-3"
                        style="border-radius:10px; border: 1px solid #b1b0b0;">
                        <div class="col-4 float-start pe-1 position-relative" style="height: 180px;">
                            <h4 class="mb-3" style="font-size: 26px;">Description</h4>
                            <button v-show="discrButton" class="position-absolute py-1 px-2 prim_btn brds-1"
                                style="top:0px;right:20px" @mousedown="updateProgramDiscription(programDetail.id)">
                                Update
                            </button>
                            <div class="position-relative">
                                <textarea @focusin="discrButton = true" @focusout="discrButton = false"
                                    v-model="programDiscr" style="height: 100px;"
                                    class="w-100 tsl border-0 px-3 py-2 prim_btn_rnd"
                                    placeholder="say something about this program">
                                </textarea>
                            </div>
                        </div>
                        <div class="col-4 float-start px-1" style="height: 180px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-3">Cover Image</h4>
                                <button v-if="programImageFile" class="py-1 px-2 prim_btn brds-1 border-0"
                                    @click="updateProgramImage(programDetail.id)">Update</button>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <img v-if="programImagePreview || programDetail.image" :src="programImagePreview || programDetail.image"
                                    alt="Program cover" class="brds-1" style="width:140px;height:100px;object-fit:cover;background:#111;">
                                <img v-else src="/images/download1.png" alt="Program cover placeholder" class="brds-1"
                                    style="width:140px;height:100px;object-fit:cover;background:#111;">
                                <label class="scnd_btn py-1 px-3 h7 rounded-1 pointer mb-0">
                                    Change
                                    <input type="file" accept="image/*" class="d-none" @change="selectProgramImage">
                                </label>
                            </div>
                        </div>
                        <div class="col-4 float-start ps-1" style="height: 180px;">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-3">Tags</h4>
                            </div>
                            <div class="d-flex flex-wrap brds-1 p-2 tsl"
                                style="height:100px;overflow-y:auto;margin-top: 2px;">
                                <span v-for="tag in programDetail.tagNames" :key="tag"
                                    class="px-2 py-1 prim_bg mx-2 brds-1 my-1" style="height:35px;">{{ tag }}</span>
                            </div>
                        </div>
                        <div class="mt-3" style="clear: both;">
                            <h4 class="mb-2" style="font-size: 26px;">Subscribers</h4>
                            <div>
                                <button @click="toggleSubsAdd(programDetail.id)"
                                    class="prim_btn_rnd prim_btn py-1 px-3 h7 me-2">Add Subscribers</button>
                                <button @click="showMessageDiv(), removeSubsDiv = false"
                                    class="scnd_btn py-1 px-3 h7 me-2 rounded-1">Message</button>
                                <button @click.self="removeSubsDiv = !removeSubsDiv"
                                    class="scnd_btn py-1 px-2 h7 me-2 rounded-1 position-relative" style="width: 25px;">
                                    <img @click.self="removeSubsDiv = !removeSubsDiv"
                                        src="/cms-assets/images/navbar-topbar/threeDots.png" alt="">
                                    <div v-if="removeSubsDiv" class="position-absolute tsl"
                                        style="width:100px;height: auto;left: 0;z-index: 999;background-color: white;">
                                        <p @click="removeSubscriber" class="w-100 m-0">Delete</p>
                                    </div>
                                </button>
                                <div class="float-end px-1 brds-2 "
                                    style="background-color:white;border:1px solid #C5C5C5;margin-top:0px;margin-right:10px;">
                                    <img src="/cms-assets/images/navbar-topbar/search.png" alt="" style="height:15px;">
                                    <input type="text" class="float-end border-0" style="font-size:12px;height:24px;"
                                        v-model="searchForSubscribers" placeholder="Search for a subscriber">
                                </div>
                            </div>
                            <div v-if="programDetail != {}" class="for_data_table mt-3"
                                style="height: 200px;overflow-y: auto;padding-bottom: 5px;">
                                <Vue3EasyDataTable v-if="programDetail.subscribers" :headers="headers" :items="items"
                                    :search-value="searchForSubscribers" :search-field="searchField"
                                    v-model:items-selected="itemsSelected">
                                </Vue3EasyDataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="phaseDetailVisible">
                <div class="gray_bg p-2">
                    <h3 @click="showRenamePopup(phaseDetail.id, 'phase', null)" class="mb-0 pointer"
                        title="click to rename">
                        {{ phaseDetail.name }}
                    </h3>
                </div>
                <div class="px-2">
                    <div class="d-flex justify-content-between p-3">
                        <h5 class="prim_txt mb-0">{{ phaseDetail.weeks }} weeks</h5>
                        <!-- <button class="mb-0 fw-bold h5 rounded-circle border-0 prim_bg">+</button> -->
                    </div>
                    <div class="px-2 pt-4 pb-1 mb-3 position-relative"
                        style="border-radius:10px; border: 1px solid #b1b0b0;">
                        <h4 class="mb-3">Summary</h4>
                        <button v-show="summaryButton" class="position-absolute py-1 px-2 prim_btn brds-1"
                            style="top:25px;right:20px" @mousedown="updatePhaseSummary(phaseDetail.id)">
                            Update
                        </button>
                        <div class="position-relative">
                            <textarea @focusin="summaryButton = true" @focusout="summaryButton = false"
                                v-model="phaseSummary" rows="3" class="w-100 tsl border-0 px-3 py-2 brds-1"
                                placeholder="say something about this phase">
                            </textarea>
                        </div>
                        <div class="mt-3">
                            <h4 class="mb-2">On-Demand Workouts</h4>
                            <div>
                                <button class="prim_btn_rnd prim_btn py-1 px-3 h7 me-2" @click="toggleNewWorkout()"> New</button>
                                <button class="scnd_btn py-1 px-3 h7 me-2 rounded-1"
                                    @click="showWrkImport(phaseDetail.id)">Import</button>
                                <button class="scnd_btn py-1 px-3 h7 me-2 rounded-1"
                                    @click="removeWorkout()">Delete</button>
                            </div>
                            <div class="d-flex justify-content-start flex-wrap mt-3">
                                <div class="shd_card p-2 m-2" v-for="phase in phaseDetail.phase_workouts">
                                    <div class="position-relative">
                                        <div v-if="phase.workout_detail != null">
                                            <img v-if="phase.workout_detail.image != null"
                                                :src="phase.workout_detail.image" alt="" class="img-fluid"
                                                style="width: 180px; object-fit: contain; background: black; height: 160px;">
                                            <img v-else src="/images/download1.png" alt="" class="img-fluid"
                                                style="width: 180px; height: 160px;">
                                        </div>
                                        <img v-else src="/images/download1.png" alt="" class="img-fluid"
                                            style="width: 180px; height: 160px;">
                                        <input type="checkbox" class="position-absolute form-check-input"
                                            style="top:5px;left:10px" v-model="toDeleteIds" :value="phase.id">
                                        <p class="mb-0 fw-bold h7" style="cursor: pointer;" @click="openDetail(phase.workout_id)">{{ phase.display_name }}</p>
                                        <div>
                                            <p v-if="phase.workout_detail != null" class="mb-0 h8 float-start">
                                                {{ phase.workout_detail.workout_exercises_count }} Exercises</p>
                                            <p v-else class="mb-0 h8 float-start">0 Exercises</p>
                                            <img @click="showEditName(phase.id, phase.display_name)"
                                                src="/cms-assets/images/master-libraries/edit.png"
                                                class="img-fluid float-end" title="Edit Display Name"
                                                style="height: 20px;" alt="">
                                        </div>
                                    </div>
                                </div>
                            </div>
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
import DataTable from 'datatables.net-vue3';
import NewProgram from '../../components/master-libraries/newProgram.vue';
import browseExerciseLibrary from '../../components/master-libraries/browseExerciseLibrary.vue';
import ImportWorkout from '../../components/master-libraries/importWorkout.vue';
import NewWorkout from '../../components/master-libraries/newWorkout.vue' ;
import BuildWorkout from '../../components/master-libraries/workoutBuilder.vue'
import SubUsers from '../../components/master-libraries/addSubscribers.vue';
import GetInput from '../../components/getInput.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import Filters from '../../components/filters.vue';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import WorkoutDetail from '../../components/master-libraries/workoutDetail.vue';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { Filters,DataTable,NewWorkout,BuildWorkout, NewProgram, browseExerciseLibrary, GetInput, Inform, Confirm, ImportWorkout, SubUsers, Loader, Vue3EasyDataTable, WorkoutDetail },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            newWrkPop : false,
            userSubComp: false,
            searchForSubscribers: '',
            programDiscr: null,
            discrButton: false,
            summaryButton: false,
            phaseSummary: null,
            detailOpen: [],
            programs: [],
            toDeleteIds: [],
            programDetail: {},
            programDetailVisible: false,
            tempWeeks: null,
            phaseDetail: {},
            phaseDetailVisible: false,
            newProgVisible: false,
            importWorkComp: false,
            idForAction: null,
            renameAction: '',
            browselibrary: false,
            getInputModal: false,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            programLanguage: null,
            programImageFile: null,
            programImagePreview: null,
            modalTitle: '',
            modalDetail: '',
            modalValue: null,
            loaderText: '',
            search: "",
            showWrktDetail: false,
            workoutId: null,
            removeSubsDiv: false,
            sendMessageDiv: false,
            textMessage: null,
            searchField: ref("username"),
            headers: [
                { text: "Name", value: "username", sortable: true },
                { text: "Start  Date", value: "start_date", sortable: true },
                { text: "Status", value: "status", sortable: true },
            ],
            items: [],
            itemsSelected: [],
            filters: false,
            tagsClose: false,
            finalVisiblePrograms: [],
            tagsFilteredPrograms: [],
            selectedTagsForFilter: [],
            programscopy: [],
            tags: [],
            editNamePopup: false,
            phaseWorkoutId: null,
            phaseId: null,
            progid: null,
            progweeks: null,
            newWorkout: null,
            workoutBuilder: false
        };
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getTags();
        this.getAllPrograms();
    },
    methods: {
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.tagsFilteredPrograms = [];
            for (let i = 0; i < this.programs.length; i++) {
                const prog = this.programs[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(prog.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(prog.tags.includes(tId)){
                        this.tagsFilteredPrograms.push(prog);
                        break;
                    }
                };
            }
            this.finalVisiblePrograms = this.tagsFilteredPrograms;
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.tagsFilteredPrograms = this.programs;
            this.finalVisiblePrograms = this.programs;
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if(searchValue==""){
                this.finalVisiblePrograms = this.tagsFilteredPrograms;
                this.search = '';
                return;
            }
            let tempArray = [];
            this.tagsFilteredPrograms.forEach(prog => {
                if(prog.title.toLowerCase().includes(searchValue))
                tempArray.push(prog);
            });
            this.finalVisiblePrograms = tempArray;
            this.detailOpenControl();
        },
        detailOpenControl(){
            this.detailOpen = [];
            for (let index = 0; index < this.finalVisiblePrograms.length; index++) {
                this.detailOpen.push(false);
            }
        },
        openDetail(id) {
            this.workoutId = id;
            this.toggleWorkDetComponent();
        },
        toggleWorkDetComponent() {
            this.showWrktDetail = !this.showWrktDetail;
        },
        showPhaseDelete(m, n, l, p) {
            this.pageLoading = true;
            this.loaderText = 'Removing Phase';
            axios.get(config.baseApiUrl + 'remove-program-phase/' + m, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.getAllPrograms();
                    this.programDetailVisible = true;
                    this.phaseDetailVisible = false;
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Phase removed from program';
                    this.informModal = true;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        showEditName(m, n) {
            this.modalTitle = 'Edit Name';
            this.modalValue = n;
            this.phaseWorkoutId = m;
            this.editNamePopup = !this.editNamePopup;
        },
        toNextStep(param) {
            this.newWrkPop = false;
            this.newWorkout = param;
            this.workoutBuilder = true;
        },
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-tags?category=program', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.tags = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        showMessageDiv() {
            if (this.itemsSelected.length == 0) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No user selected';
                this.informModal = true;
            }
            else {
                this.sendMessageDiv = true;
            }
        },
        sendMessage() {
            if (this.textMessage == null || this.textMessage == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Enter a message first';
                this.informModal = true;
            }
            else {
                this.sendMessageDiv = false;
                this.pageLoading = true;
                this.loaderText = 'Sending';
                let postData = {
                    user_ids: [],
                    content: this.textMessage,
                }
                for (let index = 0; index < this.itemsSelected.length; index++) {
                    postData.user_ids.push(this.itemsSelected[index].user_id)
                }
                this.pageLoading = true;
                this.loaderText = 'Sending';
                axios.post(config.baseApiUrl + 'multiple-users-message', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done!';
                        this.modalDetail = 'Message send successfull';
                        this.informModal = true;
                        this.textMessage = null;
                        this.itemsSelected = [];
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = 'Something Went Wrong';
                    this.informModal = true;
                    console.log(er.message);
                });
            }
        },
        removeSubscriber() {
            this.removeSubsDiv = false;
            if (this.itemsSelected.length == 0) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No user selected';
                this.informModal = true;
            }
            else {
                let users = [];
                console.log(this.itemsSelected);
                for (let index = 0; index < this.itemsSelected.length; index++) {
                    users.push(this.itemsSelected[index].user_id)
                }
                this.pageLoading = true;
                this.loaderText = 'Removing';
                let postData = {
                    program_id: this.programDetail.id,
                    user_ids: users,
                }
                axios.post(config.baseApiUrl + 'unsub-users-from-program', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done!';
                        this.modalDetail = 'User removed successfully from the Program';
                        this.informModal = true;
                        this.fetchProgramDetail(this.progid, this.progweeks);
                        this.itemsSelected = [];
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.message;
                    this.informModal = true;
                })
            }
        },
        toggleSubsAdd(id) {
            if (id !== null) {
                this.idForAction = id;
            }
            else {
                this.fetchProgramDetail(this.idForAction);
            }
            this.userSubComp = !this.userSubComp;
        },
        toggleNewWorkout(){
            this.newWrkPop = !this.newWrkPop;
        },
        closeWorkoutBuilder(wrkId) {
            this.workoutBuilder = false;
            if(wrkId!==null){
                const postData = {
                    'program_phase_id': this.phaseId,
                    'workout_ids': [wrkId]
                };
                this.pageLoading = true;
                this.loaderText = 'Loading Phase';
                axios.post(config.baseApiUrl + 'add-phase-workout', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                        this.fetchPhaseDetail(null);
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("Error in importing workouts", er.message);
                    this.informModal = true
                });
            }
        },
        selectProgramImage(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            this.programImageFile = file;
            this.programImagePreview = file ? URL.createObjectURL(file) : null;
        },
        updateProgramImage(id) {
            if (!this.programImageFile) {
                return;
            }
            const fd = new FormData();
            fd.append('program_id', id);
            fd.append('image', this.programImageFile);
            this.pageLoading = true;
            this.loaderText = 'Updating image';
            axios.post(config.baseApiUrl + 'update-program-image', fd, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.programDetail.image = res.data.data.image;
                    this.programImageFile = null;
                    this.programImagePreview = null;
                    this.getAllPrograms();
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er.message || 'Something Went Wrong';
                this.informModal = true;
            });
        },
        updateProgramDiscription(id) {
            if (this.programDiscr == null || this.programDiscr == '')
                return;
            const postData = {
                'program_id': id,
                'discription': this.programDiscr
            };
            this.pageLoading = true;
            this.loaderText = 'Updating'
            axios.post(config.baseApiUrl + 'add-program-discription', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in update program discription", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                console.log("Error in update program discription", er.error);
                this.informModal = true;
            });
        },
        updatePhaseSummary(id) {
            if (this.phaseSummary == null || this.phaseSummary == '')
                return;
            const postData = {
                'phase_id': id,
                'summary': this.phaseSummary
            };
            this.pageLoading = true;
            this.loaderText = 'Updating'
            axios.post(config.baseApiUrl + 'add-phase-summary', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in update phase summmary", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                console.log("Error in update phase summmary", er.error);
                this.informModal = true;
            });
        },
        showWrkImport(id) {
            this.idForAction = id;
            this.importWorkComp = true;
        },
        closeImportWorkout(m) {
            this.modalTitle = 'Done';
            this.modalDetail = m;
            this.informModal = true;
            this.importWorkComp = false;
        },
        getAllPrograms() {
            this.pageLoading = true;
            this.loaderText = 'Fetching ';
            axios.get(config.baseApiUrl + 'get-all-programs', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.programs = res.data.data;
                    this.finalVisiblePrograms = this.programs;
                    this.tagsFilteredPrograms = this.programs;
                    this.detailOpenControl();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in get all programs", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er.message;
                this.informModal = true;
                console.log("Error in get all program", er.error);
            });
        },
        toggleProgDetail(index, id, weeks,) {
            this.programDetailVisible = false;
            this.phaseDetailVisible = false;
            this.progid = id;
            this.progweeks = weeks;
            this.detailOpen[index] = !this.detailOpen[index];
            for (let i = 0; i < this.detailOpen.length; i++) {
                if (i != index)
                    this.detailOpen[i] = false;
            }
            if (this.detailOpen[index] == true)
                this.fetchProgramDetail(this.progid, this.progweeks);
        },
        fetchProgramDetail(id, weeks) {
            this.tempWeeks = weeks;
            this.programDetailVisible = true;
            this.phaseDetailVisible = false;
            this.pageLoading = true;
            this.loaderText = 'Fetching'
            axios.get(config.baseApiUrl + 'get-program-detail/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.programDetail = res.data.data;
                    this.programLanguage = res.data.data.language;
                    this.items = this.programDetail.subscribers;
                    this.programDiscr = this.programDetail.discription;
                    this.programImageFile = null;
                    this.programImagePreview = null;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in fetch program detail", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er.message;
                this.informModal = true;
                console.log("Error in fetch program detail", er.error);
            });
        },
        fetchPhaseDetail(pId) {
            if (pId !== null) {
                this.phaseId = pId;
            }
            this.programDetailVisible = false;
            this.phaseDetailVisible = true;
            this.pageLoading = true;
            this.loaderText = 'Fetching'
            axios.get(config.baseApiUrl + 'get-phase-detail/' + this.phaseId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.phaseDetail = res.data.data;
                    this.phaseSummary = this.phaseDetail.summary;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in fetch phase detail", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = res.data.message;
                this.informModal = true;
                console.log("Error in fetch program detail", er.error);
            });
        },
        toggleNewProgram() {
            this.newProgVisible = !this.newProgVisible;
        },
        toogleBrowseLibrary() {
            this.browselibrary = !this.browselibrary;
        },
        renameProgram(name) {
            const renamePost = {
                id: this.idForAction,
                name: name
            }
            this.pageLoading = true;
            this.loaderText = 'Updating'
            axios.post(config.baseApiUrl + 'rename-program', renamePost, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    this.getAllPrograms();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in rename program", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
                console.log("Error in rename program", er.error);
            })
        },
        deleteProgram() {
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.get(config.baseApiUrl + 'delete-program/' + this.idForAction, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.getAllPrograms();
                    this.programDetailVisible = false;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in deleting program", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                console.log(er.error);
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            })
        },
        renamePhase(name) {
            const postData = {
                'id': this.idForAction,
                'name': name
            }
            this.pageLoading = true;
            this.loaderText = 'Updating';
            axios.post(config.baseApiUrl + 'rename-phase', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
                console.log(er);
            });
        },
        showRenamePopup(id, type, value) {
            this.modalTitle = 'Rename';
            this.modalValue = value;
            this.idForAction = id;
            this.renameAction = type;
            this.getInputModal = true;
        },
        showConfirmModal(id) {
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Are You Sure?'
            this.idForAction = id;
            this.confirmModal = true;
        },
        inputResponse(value) {
            if (this.getInputModal == true) {
                this.getInputModal = false;
                if (value != null) {
                    if (this.renameAction == 'program')
                        this.renameProgram(value);
                    if (this.renameAction == 'phase')
                        this.renamePhase(value);
                }
            }
            else {
                if (value != null) {
                    let postData = {
                        phase_workout_id: this.phaseWorkoutId,
                        display_name: value
                    }
                    this.pageLoading = true;
                    this.loaderText = 'Renaming';
                    axios.post(config.baseApiUrl + 'update-phase-workout-display-name', postData, this.apiConfig).then(res => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            this.fetchPhaseDetail(this.phaseId);
                            this.showEditName();
                        }
                        else {
                            this.modalTitle = 'Error';
                            this.modalDetail = res.data.message;
                            this.informModal = true;
                        }
                    }).catch(er => {
                        this.pageLoading = false;
                        this.modalTitle = 'Error';
                        this.modalDetail = er.message;
                        this.informModal = true;
                    })
                }
                if (value == null) {
                    this.showEditName(null);
                }
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.deleteProgram();
        },
        removeWorkout() {
            if (this.toDeleteIds.length == 0) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select Workouts to remove';
                this.informModal = true;
                return
            }
            const postData = {
                "ids": this.toDeleteIds
            };
            this.pageLoading = true;
            this.loaderText = 'Removing';
            axios.post(config.baseApiUrl + 'remove-workout', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    this.fetchPhaseDetail(null);
                    this.toDeleteIds = [];

                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
                console.log(er);
            });
        },
        closeAll() {
            this.programDetailVisible = false;
            this.phaseDetailVisible = false;
        }
    },
};
</script>
<style scoped>
.masterpanel {
    background-color: #eeeeee;
    border-top-left-radius: 1rem;
    border-bottom-left-radius: 1rem;
    border-right: 1px solid rgb(228, 228, 228);
    width: 270px;
    height: 100%;
}

.masterscreen {
    width: calc(100% - 270px);
    overflow-y: auto;
}

.searchab {
    position: absolute;
    width: 15px;
    top: 12px;
    left: 20px;
}

.searchinput {
    border: 1px solid #c5c5c5;
    border-radius: 11px;
    padding: 8px;
    width: 93%;
    padding-left: 25px;
    margin-left: 14px;
    font-size: 14px;
}

input:focus-visible {
    outline: 0 !important;
    box-shadow: none !important;
}

.filterBtn {
    height: 40px;
    width: 260px;
    font-size: 16px;
    color: #F2A18C;
    text-align: center;
    border: 1px solid #B1B0B0;
    background-color: white;
    position: relative;
}

.filterimg {
    position: absolute;
    right: 20px;
    top: 15px;
    height: 8px;
}
</style>
