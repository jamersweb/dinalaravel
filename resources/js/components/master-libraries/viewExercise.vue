<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <div class="main-box w-80 p-4 position-relative">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:25px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="overflow-y:auto;height:95%;margin-top:20px;" class="w-100">
                <div class="shd_card drag-drop-area w-60 mx-auto my-3" style="min-width:280px;">
                    <iframe height="250" width="100%" :src="exercise.video_url">
                    </iframe>
                    <div class="text-center mt-3 fw-bold" style="font-size:28px;">
                        {{exercise.title}}
                        <!-- <input readonly class="title-field border-0" type="text" style="text-align:center;font-size:31px;font-weight:bold;" placeholder="Enter Title" :value="exercise.title"> -->
                    </div>
                    <div class="mx-auto my-2" style="text-align:center">
                        <button @click="editExercise=true" class="prim_bg border-0 brds-2" style="color:white;width:180px;height:40px;font-size:16px;">Edit</button>
                    </div>
                </div>
                <div class="w-50 mx-auto">
                    <div class="d-flex justify-content-between">
                        <div class="celll" style="overflow:hidden">
                            <span class="prim_txt">Type:</span> <span>{{exercise.type}}</span>
                        </div>
                        <div class="celll" style="cursor:pointer;overflow:hidden">
                            <span class="prim_txt">Tags:</span>
                            <span class="d-flex flex-wrap">
                                <span v-for="tagN in exercise.tagNames" class="tag-box">{{tagN}}</span>
                                <!-- {{exercise.tags}} -->
                            </span>
                        </div>
                    </div>
                </div>
                <div class="w-50 mx-auto mt-2">
                    <div class="d-flex justify-content-between">
                        <div class="celll w-100" style="cursor:pointer;overflow:hidden">
                            <span class="prim_txt">Alternates:</span>
                            <span class="d-flex flex-wrap">
                                <span v-if="exercise.altNames.length<1">No alternates added</span>
                                <span v-else v-for="altN in exercise.altNames" class="tag-box">{{altN}}</span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="w-50 mx-auto mt-2">
                    <textarea readonly :value="exercise.instructions" class="w-100 py-2 px-3" style="border-radius:10px;resize:none;border-color:#B1B0B0;" rows="5" placeholder="Instructions:"></textarea>
                </div>
                <div class="w-50 mx-auto mb-5">
                    <p class="brds-2 w-100 py-2 px-3 mb-2" style="border:1px solid #B1B0B0">
                        <span class="prim_txt text-start">Language:</span>
                        <span class="float-end">{{exercise.language=='en'?'English':(exercise.language=='ar'?'Arabic':'No Audio')}}</span>
                    </p>
                    <div style="border:1px solid #B1B0B0;word-break: break-all;" class="brds-2 w-100 py-2 px-3">
                        <p style="white-space: preline" class="mb-0">
                            <span class="prim_txt text-start me-5">Weights:</span>{{exercise.weights}}
                        </p>
                    </div>
                </div>
                <!-- <div class="w-50 mx-auto mt-2">
                    <div class="d-flex justify-content-between">
                    </div>
                </div> -->
                <!-- <div class="w-50 mx-auto mt-2 mb-3">
                    <div class="text-center">
                        <button class="prim_btn_rnd_lg prim_btn px-5">Save</button>
                    </div>
                </div> -->
            </div>
        </div>
        <EditExercise v-if="editExercise" :exerciseData="exercise" :tags="preFillTags"/>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import listTags from './listTags.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import EditExercise from '../../components/master-libraries/editExercise.vue';
export default {
    components: { listTags, Loader, Inform, Confirm, EditExercise },
    props: ['exId'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            exercise: {
                title: '',
                image: '',
                instructions: '',
                type: '',
                video_type: '',
                video_url: '',
                tags: '',
                id: this.exId,
                duration: null,
                language: null,
                weights: null,
                tagNames: [],
                altNames: [],
            },
            preFillTags: null,
            editExercise: false,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    methods: {
        showConfirmModal() {
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Do you want to quit?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.quitComponent();
        },
        quitComponent() {
            this.$parent.hideExerDetailComponent();
        },
        quitEditExercise(m) {
            if (m == 0) {
                this.editExercise = false;
            }
            else {
                this.editExercise = false;
                this.$parent.hideExerDetailComponent(1);
            }
        },
        acknowledged() {
            this.informModal = false;
        },
    },
    mounted() {
        this.pageLoading = true;
        this.loaderText = 'Fetching';
        axios.get(config.baseApiUrl + 'get-exercise-detail/' + this.exId, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.exercise = res.data.data;
            } else {
                this.modalTitle = 'Error!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: fetching exercise details ", res.data.message);
                // window.loacation.reload();
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Failed!';
            this.modalDetail = "Something went wrong";
            this.informModal = true;
            console.log("Error: fetching exercise details ", er);
        });
    },
}
</script>
<style scoped>
.main-box {
    background-color: white;
    border-radius: 30px;
    height: 90%;
}

.shd_card input[type="text"] {
    padding: 10px;
    border-radius: 10px;
}

.celll {
    width: 49%;
    border: 1px solid #B1B0B0;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    justify-content: space-between;
}

button.celll {
    background-color: transparent;
    justify-content: center !important;
    color: #F2A18C;
}

.selec {
    background-color: #F2A18C !important;
    color: black !important;
}

.tag-box {
    background-color: #F2A18C;
    border-radius: 5px;
    font-size: 10px;
    padding: 5px;
    margin: 2px;
    height: 27px;
}
</style>
