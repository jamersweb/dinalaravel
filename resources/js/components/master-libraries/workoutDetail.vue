<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Loader v-if="pageLoading" loadingText="Fetching" />
        <EditWorkout v-if="editPopup" :workout="workout" />
        <div class="main-box position-relative px-2 px-md-4 py-4">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:25px;top:15px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="w-100 row">
                <div class="col-6">
                    <h4 class="cptl"><strong>{{workout.title}}</strong></h4>
                    <div class="mt-3 headings">
                        <!-- <p class="mb-0 cptl">Type: <strong>{{workout.type}}</strong></p> -->
                        <p v-if="workout.exs[0]&&workout.type=='circuit'" class="cptl mb-0">Exercises: <strong>{{workout.exs[0].items.length}}</strong></p>
                        <p v-else class="cptl mb-0">Exercises: <strong>{{workout.exs.length}}</strong></p>
                        <p class="cptl">Language: <strong>{{workout.language=='en'?'English':(workout.language=='ar'?'Arabic':'No Audio')}}</strong></p>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="cptl"><strong>Tags</strong></h4>
                    </div>
                    <div class="d-flex flex-wrap brds-1 p-2 border" style="height:100px;overflow-y:auto;">
                        <span v-for="tag in workout.tagNames" class="px-2 py-1 prim_bg mx-2 brds-1 my-1" style="height:35px;">{{tag}}</span>
                    </div>
                </div>
            </div>
            <div class="w-100 mt-3">
                <h4><strong>Instructions</strong></h4>
                <p style="white-space: pre-line">{{workout.instructions}}</p>
            </div>
            <h4 class="mt-3 mb-0"><strong>Exercises Details</strong></h4>
            <div class="d-flex flex-wrap w-100 mt-0 mb-0" v-for="items in workout.exs">
                <div v-if="items.type=='simple'" class="d-flex shd_card w-100 mt-3 mb-0 py-2 align-items-center">
                    <img v-if="items.item.exercise_detail!=null" :src="items.item.exercise_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p v-else style="background-color:#f2a18c;padding:5px 30px;margin:3px 15px 3px 0px;">Rest</p>
                    <p v-if="items.item.exercise_detail!=null" class="ms-3 mb-0" style="align-self: center;">{{items.item.exercise_detail.title}}</p>
                    <p v-else style="margin-top:7px;margin-bottom:0px;">Rest :  {{formatRestPeriod(items.item.rest_period)}}</p>
                    <div v-if="items.item.exercise_id!=null" class="ms-5" style="padding-top:13px">
                        <p v-if="items.item.reps_type=='time'">Sets: {{items.item.sets}} | Time: {{items.item.time}}</p>
                        <p v-else>Sets: {{items.item.sets}} | Reps: {{items.item.reps}}</p>
                        <p v-if="items.item.rest_period!=null && items.item.rest_period>0" class="mb-0">Rest: {{formatRestPeriod(items.item.rest_period)}}</p>
                    </div>
                </div>
                <div v-else class=" d-flex flex-wrap shd_card w-100 mt-3 mb-0 py-2">
                    <p class="mb-0" style="font-weight:bold;">{{items.type_name}}</p>
                    <div  class="d-flex brds-1 w-100 mt-3 mb-0 py-2 px-2 align-items-center" style="border:1px solid #EEEEEE" v-for="(item, index) in items.items" :key="index">
                        <img v-if="item.exercise_id!=null" :src="item.exercise_detail.image" alt="" class="img-fluid" style="max-width:100px">
                        <p v-else style="background-color:#f2a18c;padding:5px 30px;margin:3px 15px 3px 0px;background-color:#f2a18c">Rest</p>
                        <p v-if="item.exercise_detail!=null" class="ms-3 mb-0" style="align-self: center;">{{item.exercise_detail.title}}</p>
                        <p v-else style="margin-top:7px;margin-bottom:0px;">Rest :  {{formatRestPeriod(item.rest_period)}}</p>
                        <div v-if="item.exercise_id!=null" class="ms-5" style="padding-top:13px">
                            <p v-if="item.reps_type=='time'">Sets: {{item.sets}} | Time: {{item.time}}</p>
                            <p v-else>Sets: {{item.sets}} | Reps: {{item.reps}}</p>
                            <p v-if="item.rest_period!=null && item.rest_period>0" class="mb-0">Rest: {{formatRestPeriod(item.rest_period)}}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-center">
                <button class="prim_btn px-3 py-2 brds-1" @click="editWorkout()">Edit</button>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import EditWorkout from '../master-libraries/editWorkout.vue';
export default {
    components: { Loader, Inform, EditWorkout },
    props: ['wrkId'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            workout: {
                exs: []
            },
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            editPopup: false
        }
    },
    mounted() {
        this.getWorkoutDetail();
    },
    methods: {
        formatRestPeriod(seconds) {
            if (seconds == null || seconds <= 0) return '0 sec';
            if (seconds < 60) return seconds + ' sec';
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            if (secs === 0) return mins + ' min';
            return mins + ' min ' + secs + ' sec';
        },
        getWorkoutDetail(){
            this.pageLoading = true;
            axios.get(config.baseApiUrl + 'get-workout-detail/' + this.wrkId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.workout = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Cannot get details";
                    this.informModal = true;
                    console.log("Workout Detail error", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Cannot get details";
                this.informModal = true;
                console.log("Workout detail error: ", er);
            });
        },
        quitComponent() {
            this.$parent.toggleWorkDetComponent();
        },
        acknowledged() {
            this.informModal = false;
        },
        editWorkout(){
            // this.pageLoading = true;
            // axios.get(config.baseApiUrl+'editable-workout/'+this.wrkId,this.apiConfig).then(res => {
                // this.pageLoading = false;
                // if(res.data)
                this.editPopup = true;
            //     else{
            //         this.modalTitle = 'Cannot Edit!';
            //         this.modalDetail = "Workout is being used in a program";
            //         this.informModal = true;
            //     }
            // }).catch(er => {
            //     this.pageLoading = false;
            //     this.modalTitle = 'Failed!';
            //     this.modalDetail = "Something Went Wrong";
            //     this.informModal = true;
            //     console.log("Workout editable error: ", er.message);
            // });
        }
    },
}
</script>
<style scoped>
.main-box {
    background-color: white;
    border-radius: 30px;
    height: 90%;
    width: 60%;
    overflow-y: auto;
}

.cptl {
    text-transform: capitalize;
}
</style>
