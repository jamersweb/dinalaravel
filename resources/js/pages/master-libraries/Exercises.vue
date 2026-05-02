<template lang="">
    <div>
        <listTags v-if="showTagsModal"/>
        <createExer v-if="showExerciseModal"/>
        <editExer v-if="exerciseDetail.visible" :exerciseData="exerciseDetail.data"/>
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
        <div class="exer-card" style="height:calc(100vh - 125px);">
            <div class="exer-head">
                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <h2 class="mb-0"><strong>Exercises</strong></h2>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="position-relative" style="width:300px">
                            <input @input="applySearch()" type="text" placeholder="search for an exercise" class="py-2 pe-2 ps-4 w-100" v-model="search">
                            <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
                        </div>
                        <div class="filter-btn ms-2">
                            <button @click="filters=true" style="border:none;background-color:transparent">
                                <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="exer-body p-3" style="height:100%;">
                <div class="d-flex justify-content-between flex-wrap">
                    <div class="bar-btns">
                        <button @click="toggleExerciseComponent" class="prim_btn mx-1 py-1"><!--<input type="checkbox" class="me-2 form-check-input">--> New</button>
                        <button class="scnd_btn mx-1 py-1" style="font-size:15px" @click="showConfirmModal">Delete</button>
                        <!-- <select class="scnd_btn mx-1 py-1">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select> -->
                        <button class="scnd_btn mx-1 py-1" @click="toggleTagsComponent">Add Tag</button>
                    </div>
                    <!-- <div>
                        <select class="name_select">
                            <option value="name">Name</option>
                        </select>
                    </div> -->
                </div>
                <div class="shd_card px-2 mt-4" style="height:calc(100% - 100px);overflow-y:auto;">
                    <div class="row col-12">
                        <div class="col-xl-3 col-md-4 col-sm-6 col-12 mt-3 px-2"  v-for="item in visibleExercisesArray">
                            <div class="shd_card exer-single my-2 float-start" style="width:100%;">
                                <div class="position-relative">
                                    <img :src="item.image" alt="" class="img-fluid" style="width:100%;height:160px;object-fit:contain;background:black;">
                                    <span v-if="item.content_code" class="badge bg-dark position-absolute top-0 start-0 m-1 text-white" style="font-size:11px;font-weight:600;">{{ item.content_code }}</span>
                                </div>
                                <input :value="item.id" type="checkbox" class="form-check-input" v-model="idsToDelete">
                                <div class="w-100 text-center mt-2 mb-0 fw-bold"  @click="showExerDetailComponent(item.id)"
                                style="max-height:50px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;">
                                    {{item.title}}
                                </div>
                                <p class="text-center mt-1 mb-0 small px-1">
                                    <span class="text-muted">Exercise code:</span>
                                    <span class="fw-semibold" :class="item.content_code ? '' : 'text-muted'">{{ item.content_code || '—' }}</span>
                                </p>
                                <p class="text-center mt-0 mb-0" v-if="item.language=='en'">English</p>
                                <p class="text-center mt-0 mb-0" v-else-if="item.language=='ar'" >Arabic</p>
                                <p class="text-center mt-0 mb-0" v-else >No Audio</p>
                            </div>
                        </div>
                        <p v-if="exercises.length==0" style="text-align:center;font-size:20px;font-weight:bold;">No exercises</p>
                        <p v-else-if="visibleExercisesArray.length==0" style="text-align:center;font-size:20px;font-weight:bold;">No exercises based on the filters</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import listTags from '../../components/master-libraries/listTags.vue';
import createExer from '../../components/master-libraries/createExercise.vue';
import editExer from '../../components/master-libraries/editExercise.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import Filters from '../../components/filters.vue';
export default {
    components: { listTags, createExer, editExer, Loader, Inform, Confirm, Filters },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            exercises: [],
            filteredExercisesArray: [],
            visibleExercisesArray: [],
            showTagsModal: false,
            selectedTagList: null,
            showExerciseModal: false,
            exerciseDetail: {
                visible: false,
                data: null
            },
            detailExId: null,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            selectedTagsForFilter: [],
            loaderText: '',
            idsToDelete: [],
            modalTitle: '',
            modalDetail: '',
            search: "",
            filters: false,
            tags: [],
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getAllExercises();
        this.getAllTags();
    },
    methods: {
        exerciseHasAnyTag(ex, tagIds) {
            if (!tagIds || tagIds.length === 0) {
                return true;
            }
            if (ex.tags == null) {
                return false;
            }
            const ids = Array.isArray(ex.tags) ? ex.tags : [];
            return tagIds.some(tId => ids.some(id => Number(id) === Number(tId)));
        },
        matchesSearchQuery(ex, searchValue) {
            if (!searchValue) {
                return true;
            }
            if (ex.title.toLowerCase().includes(searchValue)) {
                return true;
            }
            if (searchValue === 'arabic' && ex.language === 'ar') {
                return true;
            }
            if (searchValue === 'english' && ex.language === 'en') {
                return true;
            }
            if ((searchValue === 'no audio' || searchValue === 'no-audio') && ex.language === 'no') {
                return true;
            }
            const tagNames = ex.tagNames;
            if (Array.isArray(tagNames)) {
                for (let i = 0; i < tagNames.length; i++) {
                    if (String(tagNames[i]).toLowerCase().includes(searchValue)) {
                        return true;
                    }
                }
            }
            return false;
        },
        applyFilters(tagIds){
            if (!tagIds || tagIds.length === 0) {
                this.clearFilters();
                return;
            }
            this.selectedTagsForFilter = tagIds;
            this.filteredExercisesArray = this.exercises.filter(ex => this.exerciseHasAnyTag(ex, tagIds));
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.filteredExercisesArray = this.exercises.slice();
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if (searchValue === "") {
                this.visibleExercisesArray = this.filteredExercisesArray.slice();
                return;
            }
            this.visibleExercisesArray = this.filteredExercisesArray.filter(ex => this.matchesSearchQuery(ex, searchValue));
        },
        getAllTags() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-tags?category=exercise', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.tags = res.data.data;
                }
                else {
                    this.modalTitle = 'Error';
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
        getAllExercises() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-all-exercises', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.exercises = res.data.data;
                    this.filteredExercisesArray = res.data.data.slice();
                    this.visibleExercisesArray = res.data.data.slice();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Fetching Exercises";
                    this.informModal = true;
                    console.log("Error", res.data.message);
                }
            }).catch(err => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = "Fetching exercises";
                this.informModal = true;
                console.log("Error", err);
            });
        },
        deleteExercises() {
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            const postData = {
                'ids': this.idsToDelete
            }
            axios.post(config.baseApiUrl + 'delete-exercises', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                this.idsToDelete = [];
                postData.ids = [];
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    this.getAllExercises();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in deleting exercises", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                console.log(er);
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            })
        },
        showConfirmModal(i) {
            if (this.idsToDelete.length == 0) {
                this.modalTitle = 'No exercise selected!';
                this.modalDetail = 'Please select a exercise first';
                this.informModal = true;
                return;
            }
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Are You Sure?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.deleteExercises();
        },
        toggleTagsComponent() {
            if (this.idsToDelete.length > 0) {
                this.showTagsModal = !this.showTagsModal;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No exercise selected to add tag';
                this.informModal = true;
            }
        },
        selectedTags(tags) {
            this.selectedTagList = tags;
            this.toggleTagsComponent();
            this.pageLoading = true;
            this.loaderText = 'Uploading';
            let postData = {
                exercise_ids: this.idsToDelete,
                tag_ids: this.selectedTagList,
            }
            axios.post(config.baseApiUrl + 'assign-tags-to-exercises', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = 'Tags added to selected Exercises';
                    this.informModal = true;
                    this.idsToDelete = [];
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        toggleExerciseComponent() {
            this.showExerciseModal = !this.showExerciseModal;
        },
        hideExerDetailComponent(m) {
            if (m == 1) {
                this.exDetailModal = false;
                this.getAllExercises();
            }
            this.exDetailModal = false;
        },
        showExerDetailComponent(id) {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-exercise-detail/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.exerciseDetail.data = res.data.data;
                    this.exerciseDetail.visible = true;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: fetching exercise details ", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: fetching exercise details ", er);
            });
        },
        acknowledged() {
            this.informModal = false;
        },
    },
}
</script>
<style scoped>
.exer-card {
    border: 1px solid rgb(226, 224, 224);
    border-radius: 1rem;
    overflow: hidden;
}

.exer-head {
    background-color: rgb(226, 224, 224);
    padding: 5px 10px;
}

.exer-head img {
    max-height: 12px;
    left: 7px;
    top: 10px;
}

.exer-head input {
    background-color: white;
    border: 1px solid rgb(167, 166, 166);
    border-radius: 10px;
    font-size: 10px;
}

.exer-single {
    position: relative;
    padding: 10px !important;
    margin: 10px;
}

.exer-single p {
    cursor: pointer;
}

.exer-single input {
    position: absolute;
    top: 15px;
    left: 20px;
    z-index: 10;
}

.exer-body .name_select {
    background-color: white;
    border: 1px solid rgb(216, 214, 214);
    border-radius: 10px;
    font-size: 12px;
    width: 250px;
    padding: 7px 10px;
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
