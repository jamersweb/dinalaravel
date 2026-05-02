<template lang="">
    <div>
        <NewWorkout v-if="showNewWorkout"/>
        <BuildWorkout v-if="workoutBuilder" :workout="newWorkout"/>
        <WorkoutDetail v-if="showWrktDetail" :wrkId="workoutId"/>
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Loader v-if="workoutDeleting" loadingText="Deleting"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
        <div class="exer-card">
            <div class="exer-head">
                <div class="d-flex justify-content-between flex-wrap">
                    <div>
                        <h2 class="mb-0" style="font-size:26px;">Master workouts</h2>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="position-relative" style="width:250px">
                            <input @input="applySearch()" type="text" placeholder="search for a workout" class="py-2 pe-2 ps-4 w-100" v-model="search">
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
                        <button @click="toggleNewWorkout()" class="prim_btn mx-1 py-1"><!--<input type="checkbox" class="me-2 form-check-input">--> New</button>
                        <button @click="showConfirmModal()" class="scnd_btn mx-1 py-1" style="font-size:15px">Delete</button>
                    </div>
                </div>
                <div class="shd_card p-3 mt-4" style="height:calc(100% - 98px);overflow-y:auto;">
                    <div class="row">
                        <p v-if="finalVisibleWorkouts.length < 1" class="f-20 fw-bold col-12" style="text-align:center;">No Workout to
                    Display</p>
                        <div class="col-xl-3 col-md-4 col-sm-6 col-12 px-2" v-for="item in finalVisibleWorkouts">
                            <div class="shd_card exer-single float-start w-100">
                                <img :src="item.image" alt="" class="img-fluid w-100" style="object-fit:contain;background:black;">
                                <input type="checkbox" class="form-check-input" :value="item.id" v-model="deletionIds">
                                <p class="mt-2 mb-0" style="text-wrap: nowrap; overflow:hidden;" @click="openDetail(item.id)" data-toggle="tooltip" :title="item.title" ><strong>{{item.title}}</strong></p>
                                <p class="mb-0 small-text">Exercises: {{item.workout_exercises_count}}</p>
                                <p class="mb-0 small-text text-muted">Created</p>
                                <p class="mb-0 small-text">{{item.time}}</p>
                                <p class="mb-0 small-text" v-if="item.language=='en'">English</p>
                                <p class="mb-0 small-text" v-else-if="item.language=='ar'">Arabic</p>
                                <p class="mb-0 small-text" v-else >No Audio</p>
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
import NewWorkout from '../../components/master-libraries/newWorkout.vue';
import BuildWorkout from '../../components/master-libraries/workoutBuilder.vue';
import WorkoutDetail from '../../components/master-libraries/workoutDetail.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import Filters from '../../components/filters.vue';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { NewWorkout, BuildWorkout, WorkoutDetail, Loader, Inform, Confirm, Filters },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            workouts: [],           // to store workouts recieved from api
            showNewWorkout: false,
            newWorkout: {
                id: null,
                type: null,
                name: null,
                language: null,
            },
            workoutBuilder: false,
            showWrktDetail: false,
            workoutId: null,
            deletionIds: [],
            pageLoading: false,
            workoutDeleting: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            search: "",
            filters: false,
            tagsClose: false,
            finalVisibleWorkouts: [],   // to show filtered and searched 
            tagsFilteredWorkout: [],    // to store tags filtered
            selectedTagsForFilter: [],  // to prefil tags
            workoutcopy: [],
            tags: [],
            loaderText: null,
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getTags();
        this.getAllWorkouts();
    },
    methods: {
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.tagsFilteredWorkout = [];
            for (let i = 0; i < this.workouts.length; i++) {
                const wrk = this.workouts[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(wrk.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(wrk.tags.includes(tId)){
                        this.tagsFilteredWorkout.push(wrk);
                        break;
                    }
                };
            }
            this.finalVisibleWorkouts = this.tagsFilteredWorkout;
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.tagsFilteredWorkout = this.workouts;
            this.finalVisibleWorkouts = this.workouts;
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if(searchValue==""){
                this.finalVisibleWorkouts = this.tagsFilteredWorkout;
                this.search = '';
                return;
            }
            let tempArray = [];
            this.tagsFilteredWorkout.forEach(wrk => {
                if(wrk.title.toLowerCase().includes(searchValue))
                tempArray.push(wrk);
            });
            this.finalVisibleWorkouts = tempArray;
        },
        showConfirmModal() {
            if (this.deletionIds.length == 0) {
                this.modalTitle = 'Error!';
                this.modalDetail = "Please select a Workout First";
                this.informModal = true;
                return;
            }
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Do you want to delete?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.deleteWorkout();
        },
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-tags?category=workout', this.apiConfig).then(res => {
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
        getAllWorkouts() {
            this.pageLoading = true;
            this.loaderText = 'Fetching'
            axios.get(config.baseApiUrl + 'get-all-workouts', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.workouts = res.data.data;
                    this.finalVisibleWorkouts = this.workouts;
                    this.tagsFilteredWorkout = this.workouts;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error", res.data.message);
                }
            }).catch(err => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error fetching workouts: ", err.message);
            });

        },
        toggleNewWorkout() {
            this.showNewWorkout = !this.showNewWorkout;
        },
        toNextStep(param) {
            this.showNewWorkout = false;
            this.newWorkout = param;
            this.workoutBuilder = true;
        },
        closeWorkoutBuilder(param) {
            this.workoutBuilder = false;
            this.getAllWorkouts();
        },
        openDetail(id) {
            this.workoutId = id;
            this.toggleWorkDetComponent();
        },
        toggleWorkDetComponent() {
            this.showWrktDetail = !this.showWrktDetail;
        },
        deleteWorkout() {

            const postData = {
                'ids': this.deletionIds
            };
            this.workoutDeleting = true;
            axios.post(config.baseApiUrl + 'delete-detailed-workout', postData, this.apiConfig).then(res => {
                this.workoutDeleting = false;
                this.deletionIds = [];
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = "Workout Deleted Successfully(Other than workouts added in programs)";
                    this.informModal = true;
                    this.getAllWorkouts();
                    // location.reload();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in deleting exercise", res.data.message);
                }
            }).catch(er => {
                this.workoutDeleting = false;
                this.modalTitle = 'Error!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error deleting workout: ", er.error);
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
    height: calc(100vh - 125px);
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

.exer-single img {
    width: 200px;
    height: 150px;
    margin: auto;
}

.exer-single strong {
    cursor: pointer;
}

.exer-single input {
    position: absolute;
    top: 20px;
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

.small-text {
    font-size: 12px;
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
