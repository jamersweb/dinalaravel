<template lang="">
    <div class="my-popup-component" @click.self="showConfirmModal">
        <Loader v-if="loading" :loadingText="loadingText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <editExer v-if="exerciseDetail.visible" :exerciseData="exerciseDetail.data"/>
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
        <AssignTags v-if="assignTag" tagType="workout" :prefilledTags="workoutDetail.tags"/>

        <div class="main-box position-relative px-2 px-md-4 py-4">
            <button class="trans_btn position-absolute" @click="showConfirmModal()"
                style="right:30px;top:33px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="gray_bg py-2 ps-4 pe-5">
                <div class="d-flex justify-content-between flex-wrap">
                    <div class="" style="max-width:100%">
                        <!-- <span class="fw-bold me-4">{{workoutDetail.type}}</span> -->
                        <input class="fake_textf" v-model="workoutDetail.title" style="max-width:100%">
                        <input class="fake_textf mt-2" v-model="workoutDetail.content_code" placeholder="Content code (e.g. WR-023)" style="max-width:280px">
                        <select v-model="workoutDetail.language" @change="languageChanged()" class="ms-5 bg-white brds-2 px-3 py-2" style="border:1px solid #cfcfcf">
                            <option value="en">English</option>
                            <option value="ar">Arabic</option>
                            <option value="no">No Audio</option>
                        </select>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <button @click="validate()" class="prim_btn px-5 text-white" style="border-radius:10px">Update</button>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-xl-6 exercises">
                    <div class="instruc">
                        <h2><strong>INSTRUCTIONS</strong></h2>
                        <textarea @change="this.instructionError=false" v-model="workoutDetail.instructions" class="w-100" rows="3" placeholder="say something about this workout"></textarea>
                    </div>
                    <div class="mt-3 ">
                        <h2><strong>EXERCISES</strong></h2>
                        <div class="d-flex justify-content-between">
                            <div class="w-80">
                                <button @click="catSelected('superset')" :disabled="!catBtns.superset" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">superset</button>
                                <button @click="catSelected('circuit')" :disabled="!catBtns.circuit" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">circuit</button>
                                <button @click="catSelected('strength_training')" :disabled="!catBtns.strength_training" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">strength-training</button>
                                <button @click="catSelected('warm_up')" :disabled="!catBtns.warm_up" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">warm-up</button>
                                <button @click="catSelected('cardio')" :disabled="!catBtns.cardio" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">cardio</button>
                                <button @click="catSelected('stretching')" :disabled="!catBtns.stretching" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">stretching</button>
                                <button @click="catSelected('high_intensity')" :disabled="!catBtns.high_intensity" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">high-intensity</button>
                                <button @click="catSelected('mobility')" :disabled="!catBtns.mobility" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">mobility</button>
                                <button @click="catSelected('abs')" :disabled="!catBtns.abs" class="prim_btn px-2 py-1 brds-2 me-2 mb-1">abs</button>
                            </div>
                            <div class="d-flex flex-column w-20">
                                <button class="scnd_btn brds-2 py-1" @click="addRest()">Add Rest</button>
                                <button class="scnd_btn brds-2 py-1 my-1" @click="removeFromWorkout()">Remove</button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 w-100" @dragenter.prevent @dragover.prevent>
                        <div v-if="workoutDetail.exs.length==0" class="mt-5 text-center">--Click on any Exercise to Add--</div>
                        <div class="position-relative" v-for="(item, index) in workoutDetail.exs" draggable="true"
                            @dragstart="startDrag1($event, item)" @drop="onDropSort1($event, item)">
                            <div class="position-absolute" style="width:20px;left:-10px;top:calc(50% - 25px);">
                                <button @click="moveUp(index)" v-if="workoutDetail.exs.length>1 && index!=0" class="move-up tsl" title="Move Up"><i class="fa-sharp fa-solid fa-arrow-up"></i></button>
                                <button @click="moveDown(index)" v-if="workoutDetail.exs.length>1 && index+1<workoutDetail.exs.length" class="move-down tsl" title="Move Down"><i class="fa-solid fa-arrow-down"></i></button>
                            </div>
                            <input v-if="item.type=='simple'" @change="catBtnsVisib()" type="checkbox" class="form-check-input ExCheck" :value="index" v-model="selectedExs">
                            <div v-if="item.type=='simple'" class="shd_card mt-4 exs p-2 wrk-ex">
                                <div class="gray_bg px-3 py-1 mb-1">
                                    <div class="d-flex justify-content-around">
                                        <span class="heading">Exercise</span>
                                        <span class="heading">Sets</span>
                                        <span class="heading">Repititions</span>
                                        <span class="heading">Rest Period</span>
                                    </div>
                                </div>
                                <div class="col-12 d-flex mt-2">
                                    <div class="singleBox">
                                        <div class="text-center" v-if="item.item.exercise_id!=null">
                                            <img :src="item.item.exercise_detail.image" alt="" class="img-fluid" style="max-height:75px;">
                                            <p class="mb-0"><strong>{{item.item.exercise_detail.title.substring(0,15)}}</strong></p>
                                        </div>
                                        <div v-else class="rest-box">Rest</div>
                                    </div>
                                    <div class="singleBox">
                                        <div class="text-center" v-if="item.item.exercise_id!=null">
                                            <input type="number"  min="1" style="width:70px" v-model="workoutDetail.exs[index].item.sets">
                                        </div>
                                    </div>
                                    <div class="singleBox">
                                        <div class="text-center position-relative" v-if="item.item.exercise_id!=null">
                                            <div>
                                                <select class="fawsm-unicode my-0" v-model="item.item.reps_type">
                                                    <option value="time">&#xf017;</option>
                                                    <option value="text">&#xf037;</option>
                                                </select>
                                            </div>
                                            <div class="kkkk" v-if="item.item.reps_type=='time'">
                                                <select v-model="workoutDetail.exs[index].item.time">
                                                    <option :value="timeOp" selected v-for="timeOp in timeOptions">{{timeOp}}</option>
                                                </select>
                                            </div>
                                            <div class="text-center">
                                                <input type="text" class="py-1 px-2 brds-1 tm-brdr w-100" v-model="workoutDetail.exs[index].item.reps">
                                            </div>
                                        </div>
                                    </div>
                                        <div class="singleBox">
                                        <div class="text-center w-100"  v-if="workoutDetail.type=='regular' || item.item.exercise_id==null">
                                            <select v-model.number="workoutDetail.exs[index].item.rest_period">
                                                <option v-for="opt in restPeriodOptions" :key="opt.value" :value="opt.value">{{opt.label}}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <textarea v-model="workoutDetail.exs[index].item.description" placeholder="Enter instructions for the exercise" class="w-100 mt-2" rows="1"></textarea>
                                </div>
                            </div>
                            <div v-else class="shd_card mt-4 wrk-ex">
                                <div class="ms-4 d-flex justify-content-between group-head">
                                    <!-- <span class="">{{item.type_name}} of: <input style="width:60px" type="number" min="1" v-model="workoutDetail.exs[index].sets_rounds"> Rounds</span> -->
                                    <span class="prim_btn brds-2" style="font-size:13px;">{{item.type_name}}</span>
                                    <button class="prim_btn brds-2 py-1" @click="ungroup(index)">Ungroup</button>
                                </div>
                                <div v-for="(item2, index2) in item.items" class="mt-4 exs p-2 tm-brdr brds-2">
                                    <div class="gray_bg px-3 py-1 mb-1">
                                        <div class="d-flex justify-content-around">
                                            <span class="heading">Exercise</span>
                                            <span class="heading">Sets</span>
                                            <span class="heading">Repititions</span>
                                            <span class="heading">Rest Period</span>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex mt-2">
                                        <div class="singleBox">
                                            <div class="text-center" v-if="item2.exercise_id!=null">
                                                <img :src="item2.exercise_detail.image" alt="" class="img-fluid" style="max-height:75px;">
                                                <p class="mb-0"><strong>{{item2.exercise_detail.title.substring(0,15)}}</strong></p>
                                            </div>
                                            <div v-else class="rest-box">Rest</div>
                                        </div>
                                        <div class="singleBox">
                                            <div class="text-center" v-if="item2.exercise_id!=null">
                                                <input type="number"  min="1" style="width:70px" v-model="workoutDetail.exs[index].items[index2].sets" >
                                            </div>
                                        </div>
                                        <div class="singleBox">
                                            <!-- <div class="text-center position-relative" v-if="item2.exercise_id!=null">
                                                <div class="text-center">
                                                    <input type="number" min="1" class="py-1 px-2 brds-1 tm-brdr w-50" v-model="workoutDetail.exs[index].items[index2].reps">
                                                </div>
                                                <div class="kkkk">
                                                    <select v-model="workoutDetail.exs[index].items[index2].time">
                                                        <option :value="timeOp" selected v-for="timeOp in timeOptions">{{timeOp}}</option>
                                                    </select>
                                                </div>
                                            </div> -->
                                            <div class="text-center position-relative" v-if="item2.exercise_id!=null">
                                                <div>
                                                    <select class="fawsm-unicode my-0" v-model="item2.reps_type">
                                                        <option value="time">&#xf017;</option>
                                                        <option value="text">&#xf037;</option>
                                                    </select>
                                                </div>
                                                <div class="kkkk" v-if="item2.reps_type=='time'">
                                                    <select v-model="workoutDetail.exs[index].items[index2].time">
                                                        <option :value="timeOp" selected v-for="timeOp in timeOptions">{{timeOp}}</option>
                                                    </select>
                                                </div>
                                                <div class="text-center">
                                                    <input type="text" class="py-1 px-2 brds-1 tm-brdr w-100" v-model="workoutDetail.exs[index].items[index2].reps">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="singleBox">
                                            <div class="text-center w-100"  v-if="workoutDetail.type=='regular' || item2.exercise_id==null">
                                                <select v-model.number="workoutDetail.exs[index].items[index2].rest_period">
                                                    <option v-for="opt in restPeriodOptions" :key="opt.value" :value="opt.value">{{opt.label}}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <textarea v-model="workoutDetail.exs[index].items[index2].description" placeholder="Enter instructions for the exercise" class="w-100 mt-2" rows="1"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6 mt-5 mt-xl-0">
                    <div class="shd_card p-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-muted">Tags:</h5>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 mt-2 border" style="max-height:100px;overflow-y:auto">
                            <span v-for="tag in workoutDetail.tagNames" class="px-2 py-1 prim_bg mx-2 brds-1 my-1">{{tag}}</span>
                            <button class="scnd_btn px-2 py-1 brds-2 mx-2 my-1" @click="assignTagsShow()">Add/Remove</button>
                        </div>
                    </div>
                    <div class="shd_card heavy_shd p-md-3 p-1">
                        <div class="d-flex justify-content-between gray_bg p-3">
                            <div class="position-relative w-100">
                                <input @input="applySearch()" type="text" class="w-100 exSearch" placeholder="Search for an Exercise" v-model="search">
                                <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
                            </div>
                            <div>
                                <button class="trans_btn py-1 ps-3" @click="filters=true">
                                    <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid">
                                </button>
                            </div>
                        </div>
                        <div class="mt-4 p-3 d-flex justify-content-between shd_card">
                            <!-- <button class="text-muted align-self-center trans_btn">+Add Exercise</button>
                            <select>
                                <option value="">Name</option>
                            </select> -->
                            <h5>Click on a Exercise to add</h5>
                        </div>
                        <div class="row">
                            <div v-for="exr in visibleExercisesArray" class="col-xl-3 col-md-4 col-sm-6 col-12 mt-3">
                                <div @click="addToWorkout(exr.id,exr.image,exr.title)" class="shd_card p-2 h-100" style="width:100%;cursor:pointer" draggable="true"
                                @dragstart="startDrag2($event, exr)">
                                    <div class="w-100 overflow-hidden" style="height:100px">
                                        <img :src="exr.image" alt="" style="width: 100%; height: 73px;">
                                        <div class="py-1" style="background:black;color:white;border-radius: 18px;width: 25px;height: 25px;padding-left: 5px;margin-top: 2px;font-size: 11px;">
                                        {{this.modifyLanguage(exr.language)}}</div>
                                    </div>
                                    <div class="w-100 text-center mt-2 mb-0 fw-bold" style="max-height:50px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer;" data-toggle="tooltip" :title="exr.title" @click="showExerDetailComponent(exr.id)" >{{exr.title}}</div>
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
import axios from "axios";
import config from "../../config";
import Loader from "../../components/loader.vue";
import Inform from "../../components/inform.vue";
import Confirm from "../../components/confirm.vue";
import Filters from '../../components/filters.vue';
import AssignTags from '../clients/assignTags.vue';
import editExer from '../../components/master-libraries/editExercise.vue';
export default {
    components: { Filters, Loader, Inform, Confirm, AssignTags, editExer },
    props: ["workout"],
    data() {
        return {
            filters: false,
            tags: [],
            selectedTagsForFilter: [],
            assignTag: false,
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            exercises: [],
            workoutDetail : JSON.parse(JSON.stringify(this.workout)),
            filteredExercisesArray: [],
            visibleExercisesArray: [],
            rounds: 1,
            loading: false,
            loadingText: 'Loading',
            instructions: null,
            instructionError: false,
            informModal: false,
            confirmModal: false,
            modalTitle: "",
            modalDetail: "",
            search: "",
            selectedExs: [],
            selectedExDetails: {
                valid : false,
                type : null,    // same,mixed
                selction: null // tha category
            },
            exerciseDetail: {
                visible: false,
                data: null
            },
            showSuperset: false,
            showCircuit: false,
            selectedExsCirc: [],
            timeOptions: config.timeOptions,
            restPeriodOptions: config.restPeriodOptions,
            catBtns: {
                superset : false,
                circuit : false,
                warm_up : false,
                cardio : false,
                strength_training : false,
                workout : false,
                high_intensity : false,
                stretching : false,
                mobility : false,
                abs : false
            }
        };
    },
    mounted() {
        this.getExercises();
        this.getAllTags();
    },
    methods: {
        modifyLanguage(language) {
            if(language == 'no'){
                return 'NA'
            }else{
                return language.toUpperCase()
            }
        },
        startDrag1(evt, item) {
            evt.dataTransfer.dropEffect = 'move'
            evt.dataTransfer.effectAllowed = 'move'
            evt.dataTransfer.setData('itemOrder', item.order)
        },
        startDrag2(evt, item) {
            evt.dataTransfer.dropEffect = 'move'
            evt.dataTransfer.effectAllowed = 'move'
            evt.dataTransfer.setData('itemTitle', item.title)
            evt.dataTransfer.setData('itemId', item.id)
            evt.dataTransfer.setData('itemImage', item.image)
        },
        getItemByOrder1(event){
            const itemOrder = event.dataTransfer.getData('itemOrder')
            const item = this.workoutDetail.exs.find((item) => item.order == itemOrder)
            return { item, itemOrder }
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
        onDropSort1(event, droppedItem){
            if(event.dataTransfer.getData('itemOrder')){
                const { item, itemOrder } = this.getItemByOrder1(event)
                const itemPosition = this.workoutDetail.exs.findIndex((item) => item.order == itemOrder)
                const droppedItemPosition = this.workoutDetail.exs.findIndex((item) => item.order == droppedItem.order)
                this.workoutDetail.exs.splice(itemPosition, 1)
                this.workoutDetail.exs.splice(droppedItemPosition, 0, item)
                this.updateOrder()
            }else{
                const itemId = event.dataTransfer.getData('itemId')
                const itemTitle = event.dataTransfer.getData('itemTitle')
                const itemImage = event.dataTransfer.getData('itemImage')
                let tempData = {
                    type: "simple",
                    item: {
                        exercise_id: itemId,
                        sets: 1,
                        time: "5 sec",
                        reps: 1,
                        reps_type : "text",
                        rest_period: 0,
                        exercise_detail : {
                            title: itemTitle,
                            image: itemImage,
                            id : itemId
                        },
                        description: null
                    },
                    type_name : null,
                    items : null,
                    order : droppedItem.order
                };
                this.workoutDetail.exs.splice(droppedItem.order, 0, tempData)
                this.updateOrder()
            }
        },
        updateOrder(){
            for (let index = 0; index < this.workoutDetail.exs.length; index++) { 
                this.workoutDetail.exs[index].order = index;
            }
        },
        assignTagsShow(){
            this.assignTag = !this.assignTag;
        },
        assignTags(tags){
            let tagIds = [];
            let tagNames = [];
            tags.forEach(tag => {
                tagIds.push(tag.tagId);
                tagNames.push(tag.tagName);
            });
            this.workoutDetail.tags = tagIds;
            this.workoutDetail.tagNames = tagNames;
            this.assignTag = false;
        },
        languageChanged(){
            if(this.workoutDetail.language!=='no'){
                this.workoutDetail.exs = [];
                this.selectedExs = [];
            }
            this.getExercises();
        },
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.filteredExercisesArray = [];
            for (let i = 0; i < this.exercises.length; i++) {
                const ex = this.exercises[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(ex.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(ex.tags.includes(tId)){
                        this.filteredExercisesArray.push(ex);
                        break;
                    }
                };
            }
            this.visibleExercisesArray = this.filteredExercisesArray;
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.filteredExercisesArray = this.exercises;
            this.visibleExercisesArray = this.exercises;
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if(searchValue==""){
                this.visibleExercisesArray = this.filteredExercisesArray;
                this.search = '';
                return;
            }
            let tempArray = [];
            this.filteredExercisesArray.forEach(ex => {
                if(ex.title.toLowerCase().includes(searchValue))
                tempArray.push(ex);
            });
            this.visibleExercisesArray = tempArray;
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
        showConfirmModal() {
            this.modalDetail = "This Cannot be Undone.";
            this.modalTitle = "Do you want to quit?";
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0) return;
            this.quitComponent();
        },
        quitComponent() {
            this.$parent.editPopup = false;
        },
        getExercises() {
            this.loadingText = 'Loading';
            (this.loading = true),
                axios
                    .get(config.baseApiUrl + "get-all-exercises?lang=" + this.workoutDetail.language, this.apiConfig)
                    .then((res) => {
                        this.loading = false;
                        if (res.data.status){
                            this.exercises = res.data.data;
                            this.filteredExercisesArray = res.data.data;
                            this.visibleExercisesArray = res.data.data;
                        } else {
                            this.modalTitle = "Error!";
                            this.modalDetail = res.data.message;
                            this.informModal = true;
                            console.log("Getting exercises error: ", res.data.message);
                        }
                    })
                    .catch((err) => {
                        (this.loading = false), (this.modalTitle = "Failed!");
                        this.modalDetail = "Something went wrong";
                        this.informModal = true;
                        console.log("Getting exercises error: ", err.error);
                    });
        },
        addToWorkout(exId, exImg, exTitle) {
            let tempData = {
                type: "simple",
                item: {
                    exercise_id: exId,
                    sets: 1,
                    time: "5 sec",
                    reps: 1,
                    reps_type : "text",
                    rest_period: 0,
                    exercise_detail : {
                        title: exTitle,
                        image: exImg,
                        id : exId
                    },
                    description: null
                },
                type_name : null,
                items : null,
                order : this.workoutDetail.exs.length
            };
            this.workoutDetail.exs.push(tempData);
            this.supCirBtns();
        },
        removeFromWorkout() {
            this.selectedExs = this.selectedExs.sort();
            for (let z = this.selectedExs.length-1; z >=0; z--) {
                this.workoutDetail.exs.splice(this.selectedExs[z], 1);
            }
            this.selectedExs = [];
            this.catBtnsVisib();
        },
        addRest() {
            let tempData = {
                type: "simple",
                order: this.workoutDetail.exs.length,
                item: {
                    exercise_id: null,
                    sets: null,
                    time: null,
                    reps: null,
                    reps_type : "text",
                    rest_period: 0,
                    rounds: null,
                    tempTitle: null,
                    tempUrl: null,
                },
            };
            if (this.workoutDetail.type !== "circuit") {
                this.workoutDetail.exs.push(tempData);
            }
            else {
                if (this.workoutDetail.exs.length == 0) {
                    let tempParent = {
                        type: "circuit",
                        rounds: 1,
                        items: [],
                    };
                    this.workoutDetail.exs.push(tempParent);
                }
                this.workoutDetail.exs[0].items.push(tempData.item)
            }
            this.supCirBtns();
        },
        validate() {
            let restCheck = false;
            if (this.workoutDetail.exs.length == 0) {    // check for no exercise added
                this.modalTitle = "Error!";
                this.modalDetail = "No exercise added please add first";
                this.informModal = true;
                return;
            }
            for (let index = 0; index < this.workoutDetail.exs.length; index++) {    // check if only rests added
                if (this.workoutDetail.exs[index].type !== 'simple') {
                    for (let index1 = 0; index1 < this.workoutDetail.exs[index].items.length; index1++) {
                        if (this.workoutDetail.exs[index].items[index1].exercise_id !== null) {
                            restCheck = true;
                            break
                        }
                    }
                }
                else {
                    if (this.workoutDetail.exs[index].item.exercise_id !== null) {
                        restCheck = true;
                        break
                    }
                }
            }
            if (restCheck == false) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Only rest cannot be added in a Workout';
                this.informModal = true;
                return
            }
            // if (this.workoutDetail.instructions == null || this.workoutDetail.instructions == "") {     // check empty instrructions
            //     this.modalTitle = "Error!";
            //     this.modalDetail = "No instructions added please add first";
            //     this.informModal = true;
            //     return;
            // }
            this.workoutDetail.exs.forEach((item1) => {
                if (item1.type !== "simple") {
                    if (item1.sets_rounds == null || item1.sets_rounds > 100 || item1.sets_rounds < 1) {
                        this.modalTitle = "Error!";
                        this.modalDetail = "No of sets/rounds cannot be less than 1 and more than 100 for any category";
                        this.informModal = true;
                        return;
                    }
                    item1.items.forEach((item4) => {
                        if (item4.exercise_id != null) {
                            if ((item4.sets == null || item4.sets > 100 || item4.sets < 1) &&
                                (item4.reps == null || item4.reps > 100 || item4.reps < 1)) {
                                this.modalTitle = "Error!";
                                this.modalDetail = "No of sets and reps cannot be less than 1 and more than 100 for any exercise";
                                this.informModal = true;
                                return;
                            }
                        }
                    })
                }
                if (item1.type === "simple" && 
                (item1.item.sets == null || item1.item.sets > 100 || item1.item.sets < 1) &&
                (item1.item.reps == null || item1.item.reps > 100 || item1.item.reps < 1)) {
                    if (item1.item.exercise_id != null) {
                        this.modalTitle = "Error!";
                        this.modalDetail = "No of sets and reps cannot be less than 1 and more than 100 for any exercise";
                        this.informModal = true;
                        return;
                    }
                }
                
            });
            this.upload();
        },
        upload(){
            const postData = this.workoutDetail;
            this.loadingText = 'Uploading';
            this.loading = true;
            axios.post(config.baseApiUrl + "update-workout", postData, this.apiConfig)
            .then((res) => {
                this.loading = false;
                if (res.data.status) {
                    this.modalTitle = "Done!";
                    this.modalDetail = "Workout Updated";
                    this.informModal = true;
                    this.$parent.editPopup = false;
                    this.$parent.$parent.showWrktDetail = false;
                    this.$parent.$parent.getAllWorkouts();
                } else {
                    this.modalTitle = "Error!";
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("editing workout error", res.data.message);
                }
            })
            .catch((er) => {
                this.loading = false;
                this.modalTitle = "Failed!";
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Posting workout error", er);
            });
        },
        ungroup(index) {
            let tempArray = this.workoutDetail.exs[index].items;
            this.workoutDetail.exs.splice(index, 1);
            for (let i = 0; i < tempArray.length; i++) {
                const element = {
                    type: "simple",
                    item: tempArray[i],
                    order: this.workoutDetail.exs.length
                };
                this.workoutDetail.exs.splice(index + i, 0, element);
            }
            this.updateOrder()
        },
        moveUp(m, n) {
            let index = m;
            let type = n;
            if (type == "circuit") {
                let temp = this.workoutDetail.exs[0].items[index];
                this.workoutDetail.exs[0].items[index] = this.workoutDetail.exs[0].items[index - 1];
                this.workoutDetail.exs[0].items[index - 1] = temp;
            } else {
                let temp = this.workoutDetail.exs[index];
                this.workoutDetail.exs[index] = this.workoutDetail.exs[index - 1];
                this.workoutDetail.exs[index - 1] = temp;
            }
        },
        moveDown(m, n) {
            let index = m;
            let type = n;
            if (type == "circuit") {
                let temp = this.workoutDetail.exs[0].items[index];
                this.workoutDetail.exs[0].items[index] = this.workoutDetail.exs[0].items[index + 1];
                this.workoutDetail.exs[0].items[index + 1] = temp;
            } else {
                let temp = this.workoutDetail.exs[index];
                this.workoutDetail.exs[index] = this.workoutDetail.exs[index + 1];
                this.workoutDetail.exs[index + 1] = temp;
            }
        },
        superSetSelection() {
            let selTypes = [];
            for (let index = 0; index < this.selectedExs.length; index++) {
                // push types of selected exs in seperate array
                selTypes.push(this.workoutDetail.exs[this.selectedExs[index]].type);
            }
            this.selectedExs = this.selectedExs.sort();
            if (!(selTypes.includes("superset") || selTypes.includes("circuit"))) {
                //all selected exs are simple
                let tempItems = [];
                for (let index = 0; index < this.selectedExs.length; index++) {
                    tempItems.push(this.workoutDetail.exs[this.selectedExs[index]].item); //adding all selected exercise to single array
                }
                let tempData = {
                    // super set containing exercises
                    type: "superset",
                    sets: 1,
                    items: tempItems,
                }; // index where superset will be added
                for (let index = 0; index < this.selectedExs.length; index++) {
                    this.workoutDetail.exs.splice(this.selectedExs[index] - index, 1); // deleting selected indexes
                }
                let tempIndex = this.selectedExs.sort()[0];
                this.workoutDetail.exs.splice(tempIndex, 0, tempData); // add grouped data to post data
            } else if (
                !(selTypes.includes("simple") || selTypes.includes("circuit"))
            ) {
                // all supersets selected
                this.selectedExs.sort(); //sort indexs array
                let tempIndex = this.selectedExs[0]; // pick first value
                for (let index = 1; index < this.selectedExs.length; index++) {
                    // loop values after first one
                    let itemsToPush = this.workoutDetail.exs[this.selectedExs[index]].items; //get exercises in the group
                    this.workoutDetail.exs[tempIndex].items.push(...itemsToPush); // push into first one
                    this.workoutDetail.exs.splice(this.selectedExs[index], 1); // delete group after adding in first
                }
            } else if (
                selTypes.includes("superset") &&
                selTypes.includes("simple") &&
                !selTypes.includes("circuit")
            ) {
                //super sets and simple selected
                // debugger;
                let tempIndex = null;
                for (let index = 0; index < this.selectedExs.length; index++) {
                    const element = this.selectedExs[index];
                    if (tempIndex == null && this.workoutDetail.exs[element].type == "superset") {
                        tempIndex = element; //getting index of first superset in the selection
                    }
                }
                let indexesToDel = [];
                for (let i = 0; i < this.selectedExs.length; i++) {
                    // adding all others selected to that above superset
                    // debugger;
                    const element = this.selectedExs[i];
                    if (element != tempIndex && this.workoutDetail.exs[element].type == "simple") {
                        // if current item is simple
                        let itemToadd = this.workoutDetail.exs[element].item;
                        if (element > tempIndex)
                            // if located after superset
                            this.workoutDetail.exs[tempIndex].items.push(itemToadd);
                        // add to superset at end
                        // else loacated before superset
                        else this.workoutDetail.exs[tempIndex].items.splice(0, 0, itemToadd); // add to superset at start
                        indexesToDel.push(element); // delete that item after adding
                    } else if (
                        element != tempIndex &&
                        this.workoutDetail.exs[element].type == "superset"
                    ) {
                        // if current item is a superset group
                        let tempItems = this.workoutDetail.exs[element].items;
                        this.workoutDetail.exs[tempIndex].items.push(...tempItems); // add to superset
                        indexesToDel.push(element); // delete that item after adding
                    }
                }
                for (let j = 0; j < indexesToDel.length; j++) {
                    const elem = indexesToDel[j];
                    this.workoutDetail.exs.splice(elem, 1);
                }
            }
            while (this.selectedExs.length > 0) {
                this.selectedExs.pop();
            } // empty selected indexes array
            this.supCirBtns();
        },
        circuitSelection() {
            let selTypes = [];
            for (let index = 0; index < this.selectedExs.length; index++) {
                // push types of selected exs in seperate array
                selTypes.push(this.workoutDetail.exs[this.selectedExs[index]].type);
            }
            this.selectedExs = this.selectedExs.sort();
            if (!(selTypes.includes("superset") || selTypes.includes("circuit"))) {
                //all selected exs are simple
                let tempItems = [];
                for (let index = 0; index < this.selectedExs.length; index++) {
                    tempItems.push(this.workoutDetail.exs[this.selectedExs[index]].item); //adding all selected exercise to single array
                }
                let tempData = {
                    // super set containing exercises
                    type: "circuit",
                    rounds: 1,
                    items: tempItems,
                }; // index where superset will be added
                for (let index = 0; index < this.selectedExs.length; index++) {
                    this.workoutDetail.exs.splice(this.selectedExs[index] - index, 1); // deleting selected indexes
                }
                let tempIndex = this.selectedExs.sort()[0]; // empty selected indexes array
                this.workoutDetail.exs.splice(tempIndex, 0, tempData); // add grouped data to post data
            } else if (
                !(selTypes.includes("simple") || selTypes.includes("superset"))
            ) {
                // all circuits selected
                this.selectedExs.sort(); //sort indexs array
                let tempIndex = this.selectedExs[0]; // pick first value
                for (let index = 1; index < this.selectedExs.length; index++) {
                    // loop values after first one
                    let itemsToPush = this.workoutDetail.exs[this.selectedExs[index]].items; //get exercises in the group
                    this.workoutDetail.exs[tempIndex].items.push(...itemsToPush); // push into first one
                    this.workoutDetail.exs.splice(this.selectedExs[index], 1); // delete group after adding in first
                }
            } else if (
                !selTypes.includes("superset") &&
                selTypes.includes("simple") &&
                selTypes.includes("circuit")
            ) {
                //circuits and simple selected
                // debugger;
                let tempIndex = null;
                for (let index = 0; index < this.selectedExs.length; index++) {
                    const element = this.selectedExs[index];
                    if (tempIndex == null && this.workoutDetail.exs[element].type == "circuit") {
                        tempIndex = element; //getting index of first superset in the selection
                    }
                }
                let indexesToDel = [];
                for (let i = 0; i < this.selectedExs.length; i++) {
                    // adding all others selected to that above superset
                    // debugger;
                    const element = this.selectedExs[i];
                    if (element != tempIndex && this.workoutDetail.exs[element].type == "simple") {
                        // if current item is simple
                        let itemToadd = this.workoutDetail.exs[element].item;
                        if (element > tempIndex)
                            // if located after superset
                            this.workoutDetail.exs[tempIndex].items.push(itemToadd);
                        // add to superset at end
                        // else loacated before superset
                        else this.workoutDetail.exs[tempIndex].items.splice(0, 0, itemToadd); // add to superset at start
                        indexesToDel.push(element); // delete that item after adding
                    } else if (
                        element != tempIndex &&
                        this.workoutDetail.exs[element].type == "circuit"
                    ) {
                        // if current item is a superset group
                        let tempItems = this.workoutDetail.exs[element].items;
                        this.workoutDetail.exs[tempIndex].items.push(...tempItems); // add to superset
                        indexesToDel.push(element); // delete that item after adding
                    }
                }
                for (let j = 0; j < indexesToDel.length; j++) {
                    const elem = indexesToDel[j];
                    this.workoutDetail.exs.splice(elem, 1);
                }
            }
            while (this.selectedExs.length > 0) {
                this.selectedExs.pop();
            }
            this.supCirBtns();
        },
        supCirBtns() {
            let exerciseIds = [];
            let error = false;
            if (this.workoutDetail.type == "regular") {
                // regular workout type
                if (this.workoutDetail.exs.length > 1) {
                    // atleast 2 exercises imported
                    if (this.selectedExs.length > 1) {
                        // atleast 2 exercises selected
                        let selTypes = [];
                        for (let index = 0; index < this.selectedExs.length; index++) {
                            // push types of selected exs in seperate array
                            selTypes.push(this.workoutDetail.exs[this.selectedExs[index]].type);
                        }
                        for (let index = 0; index < this.selectedExs.length; index++) {
                            if (selTypes[index] == 'simple') {
                                exerciseIds.push(this.workoutDetail.exs[this.selectedExs[index]].item.exercise_id);
                            }
                        }
                        for (let index = 0; index < exerciseIds.length; index++) {
                            if (exerciseIds[index] !== null) {
                                error = true;
                                break
                            }
                        }
                        if (error == true) {
                            if (
                                !(selTypes.includes("superset") || selTypes.includes("circuit"))
                            ) {
                                //all simple selected
                                this.showSuperset = true;
                                this.showCircuit = true;
                            } else if (
                                !(selTypes.includes("simple") || selTypes.includes("circuit"))
                            ) {
                                // all superset selected
                                this.showSuperset = true;
                                this.showCircuit = false;
                            } else if (
                                !(selTypes.includes("simple") || selTypes.includes("superset"))
                            ) {
                                // all circuits selected
                                this.showSuperset = false;
                                this.showCircuit = true;
                            } else if (
                                selTypes.includes("superset") &&
                                selTypes.includes("simple") &&
                                !selTypes.includes("circuit")
                            ) {
                                // simple and superset selected
                                this.showSuperset = true;
                                this.showCircuit = false;
                            } else if (
                                !selTypes.includes("superset") &&
                                selTypes.includes("simple") &&
                                selTypes.includes("circuit")
                            ) {
                                // simple and circuit selected
                                this.showSuperset = false;
                                this.showCircuit = true;
                            } else if (
                                selTypes.includes("superset") &&
                                !selTypes.includes("simple") &&
                                selTypes.includes("circuit")
                            ) {
                                // superset and circuit selected
                                this.showSuperset = false;
                                this.showCircuit = false;
                            } else {
                                this.showSuperset = false;
                                this.showCircuit = false;
                            }
                        }
                        if (error == false) {
                            this.showCircuit = false;
                            this.showSuperset = false;
                        }
                    } else {
                        this.showSuperset = false;
                        this.showCircuit = false;
                    }
                } else {
                    this.showSuperset = false;
                    this.showCircuit = false;
                }
            }
            if (this.workoutDetail.type == "interval") {
                if (this.workoutDetail.exs.length > 1) {
                    // atleast 2 exercises imported
                    if (this.selectedExs.length > 1) {
                        // atleast 2 exercises selected
                        this.showCircuit = true;
                    } else {
                        this.showCircuit = false;
                    }
                } else {
                    this.showCircuit = false;
                }
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        uuidv4() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },
        catSelected(cat){
            this.selectedExs = this.selectedExs.slice().sort((a, b) => a - b);     // indexes of selected exercises
            let tempItems = [];     // selected exercises
            for (let index = 0; index < this.selectedExs.length; index++) {
                tempItems.push(this.workoutDetail.exs[this.selectedExs[index]].item); //adding all selected exercise to single array
            }
            const groupId = this.uuidv4();
            let tempData = {
                order : this.workoutDetail.exs.length,
                type: cat,
                sets_rounds: 1,
                items: tempItems,
                type_name : this.snakeToCapitalize(cat),
                group_id: groupId
            }; 
            for (let index = 0; index < this.selectedExs.length; index++) {
                this.workoutDetail.exs.splice(this.selectedExs[index] - index, 1); // deleting selected indexes
            }

            for (let i = 0; i < this.workoutDetail.exs.length; i++) {
                this.workoutDetail.exs[i].order = i
            }
            
            let tempIndex = this.selectedExs.sort()[0];
            this.workoutDetail.exs.splice(tempIndex, 0, tempData); // add grouped data to main data

            this.selectedExs = [];
            this.catBtnsVisib(false);
        },
        catBtnsVisib(){
            if (this.selectedExs.length > 0){
                this.allCatState(true);
            } else {
                this.allCatState(false);
            }
        },
        allCatState(x){
            for (const prop in this.catBtns) {
                if (this.catBtns.hasOwnProperty(prop)) {
                    this.catBtns[prop] = x;
                }
            }
        },
        areAllElementsSame(arr){
            const firstElement = arr[0];
            for (let i = 1; i < arr.length; i++) {
                if (arr[i] !== firstElement) {
                    return false; // Found an element that is different.
                }
            }
            return true; // All elements are the same.
        },
        enableOneDisableOthers(keyToEnable){
            for (const prop in this.catBtns) {
                if (this.catBtns.hasOwnProperty(prop)) {
                    if (prop === keyToEnable)
                    this.catBtns[prop] = true;
                    else 
                    this.catBtns[prop] = false;
                }
            }
        },
        checkSimpleAndWhichOther(arr){
            if(arr.includes("simple")){     // check either there is 'simple'
                let other = null;   // to store type other than 'simple'
                for (let i = 0; i < arr.length; i++) {
                    const type = arr[i];
                    if(other===null){           // check if first entry other than 'simple' is not found yet
                        if(type!=="simple")        // other than 'simple' entry found
                        other = type;        
                        
                    } else {        // other than 'simple' found already
                        if(!(type==="simple" || type===other)){
                            other = null;
                            break;          // if not 'simple' or 'other'
                        }
                    }
                }
                return other;
            } else      // no simple, simply not valid selection
            return null;
        },
        snakeToCapitalize(value){
            return value.replace (/^[-_]*(.)/, (_, c) => c.toUpperCase()).replace (/[-_]+(.)/g, (_, c) => ' ' + c.toUpperCase());
        }
    },
};
</script>
<style scoped>
button:disabled{
    opacity: 0.3;
}
.main-box {
    background-color: white;
    border-radius: 30px;
    width: 90%;
    height: 90%;
    overflow-y: auto;
    overflow-x: hidden;
}

.fake_textf {
    color: rgb(192, 192, 192);
    background-color: white;
    border-radius: 10px;
    border: 1px solid rgb(197, 197, 197);
    padding: 8px;
}

.gray_bg {
    border-radius: 10px;
}
.group-head {
    padding-bottom: 5px;
    border-bottom: 4px solid #f2a18c;
}
.group-head input {
    border: none;
    background-color: #f9f9f9;
    border-radius: 5px;
    text-align: center;
    color: gray;
}

textarea {
    resize: none;
    border-radius: 10px;
    border: none;
    outline: none;
}
.instruc textarea{
    box-shadow: 0px 0px 5px 0px #f2a18c;
    padding: 10px 20px;
}
.wrk-ex textarea {
    background-color: #f1f1f1;
    padding: 7px 15px;
}

.restbtn {
    background-color: #fff;
    border: none;
    box-shadow: 0px 0px 5px 0px #f2a18c;
    padding: 5px 10px;
    align-self: center;
}

.heading {
    font-size: 12px;
}

.ExCheck {
    position: absolute;
    top: 9px;
    left: 15px;
}
.exercises button {
    font-size: 13px !important;
}

.singleBox select {
    border: 1px solid rgb(212, 212, 212);
    padding: 5px;
    background-color: #fff;
    color: rgb(150, 150, 150);
    margin-top: 3px;
    margin-bottom: 3px;
    border-radius: 5px;
    width: 50%;
}

.singleBox select, .singleBox input, .wrk-ex textarea {
    font-size: 12px !important;
}

.exs input[type="text"],
.exs input[type="number"] {
    color: rgb(192, 192, 192);
    background-color: white;
    border-radius: 5px;
    border: 1px solid rgb(197, 197, 197);
    padding: 3px 7px;
}

.singleBox {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 25%;
    min-width: 125px;
}

.exSearch {
    color: rgb(192, 192, 192);
    background-color: white;
    border-radius: 5px;
    border: 1px solid rgb(197, 197, 197);
    padding: 5px 10px 5px 30px;
}

.exSearch+img {
    top: 10px;
    left: 10px;
    max-width: 15px;
}

.trans_btn img {
    max-width: 30px;
}

.heavy_shd {
    box-shadow: 0px 0px 30px 0px #f2a08c7c;
    min-height: 100%;
}

.heavy_shd select {
    width: 200px;
    padding: 3px 5px;
    border: 1px solid #d5d5d5;
    border-radius: 5px;
    background-color: white;
    color: gray;
}

.rest-box {
    width: 100%;
    height: 100%;
    background-color: #f2a18c;
    display: flex;
    justify-content: center;
    align-items: center;
    color: white;
    font-weight: bold;
}

.fawsm-unicode,
.fawsm-unicode option {
    font-family: "FontAwesome", "sans-serif";
}

.move-up {
    border: none;
    background-color: white;
    border-radius: 15px;
    height: 20px;
    font-size: 11px;
}

.move-down {
    border: none;
    background-color: white;
    height: 20px;
    font-size: 11px;
    border-radius: 15px;
}
</style>
