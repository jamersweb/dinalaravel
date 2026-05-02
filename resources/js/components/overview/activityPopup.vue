<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <div :class="{w60 :type=='habit'}" class="w-80 brds-3 position-relative" style="background-color:white;height:90vh;">
            <button class="trans_btn position-absolute" @click="quitComponent" style="right:10px;top:10px;font-size:25px;z-index:9;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div v-if="this.type!=='habit'" class="col-8 h-100 float-start">
                <div class="col-12 px-3 py-4 h-100 mx-auto"  style="overflow-y:auto;" v-if="activityDetail!==null">
                    <div v-if="activityDetail.workoutDetail" class="col-12 float-start">
                        <div class="col-12 float-start">
                            <h4 style="text-transform:capitalize;" class="mb-5" v-if="activityDetail.workoutDetail"><strong>Name : </strong>{{activityDetail.workoutDetail.title}}(<span v-if="activityDetail.workoutDetail.language=='en'">English</span><span v-else>Arabic</span>)</h4>
                        </div>
                        <img :src="activityDetail.workoutDetail.image" v-if="activityDetail.workoutDetail"  class="float-start" style="width:300px;" alt="">
                    </div>
                    <div v-if="activityDetail.workoutDetail" class="col-12 mt-2 mx-auto float-start">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="fw-bold mb-0">Instructions</p>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 border" style="height:100px;overflow-y:auto;">
                            <p v-if="activityDetail.workoutDetail.instructions"  class="mb-0 w-100" style="word-break:break-all;">{{activityDetail.workoutDetail.instructions}}</p>
                            <p v-else >No instructions added </p>
                        </div>
                    </div>
                    <div v-if="activityDetail.workoutDetail" class="col-12 mt-3 mx-auto float-start">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="fw-bold mb-0">Intensity Level : <span class="fw-light">{{rating}}</span></p>
                        </div>
                    </div>
                    <div v-if="activityDetail.photosDetail" class="col-12 d-flex flex-wrap justify-content-around">
                        <div v-if="activityDetail.photosDetail.front_picture" class="col-3">
                            <p class="col-12 fw-bold text-center">Front Picture</p>
                            <img :src="activityDetail.photosDetail.front_picture" class="col-12" alt="">
                        </div>
                        <div v-if="activityDetail.photosDetail.side_picture" class="col-3">
                            <p class="col-12 fw-bold text-center">Side Picture</p>
                            <img :src="activityDetail.photosDetail.side_picture" class="col-12" alt="">
                        </div>
                        <div v-if="activityDetail.photosDetail.back_picture" class="col-3">
                            <p class="col-12 fw-bold text-center">Back Picture</p>
                            <img :src="activityDetail.photosDetail.back_picture" class="col-12" alt="">
                        </div>
                        <p v-else class="mt-5 fw-bold text-center">{{activityDetail.photosDetail}}</p>
                    </div>
                    <div v-if="activityDetail.mealDetail" class="col-12" style="border-bottom-style:solid;border-color: #c5c5c5;border-width:1px;height:40px">
                        <p class="mb-0 fw-bold">{{activityDetail.date}}</p>
                    </div>
                    <div v-if="activityDetail.mealDetail" class="col-12 mx-auto py-3" style="overflow-y:auto;height:calc(100% - 40px)">
                        <h5 class="mb-0 fw-bold ms-3">Individual Meal</h5>
                        <div v-for="(item, index) in activityDetail.mealDetail" :key="index" class="col-12 px-4 my-2 float-start" style="min-height:140px">
                            <div v-if="item.mealDetail!==null" style="height:100px;width:100%;">
                                <img :src="item.mealDetail.file" class="float-start" style="width:100px;height:100px;" alt="">
                                <div class="col-6 ps-3 pt-3 float-start">
                                    <p class="mb-0" style="text-transform:capitalize;">{{item.detail}}</p>
                                    <p class="mb-0" style="text-transform:capitalize;">{{item.time}}</p>
                                </div>
                            </div>
                            <div class="col-12 mt-3" style="height:1px;background-color:#c5c5c5;"></div>
                            <p class="my-1 ms-3" style="text-transform:capitalize;">{{item.mealDetail.name}}</p>
                            <div class="col-12" style="height:1px;background-color:#c5c5c5;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 h-100 mx-auto"  style="overflow-y:auto;" v-if="activityDetail!==null&&type=='habit'">
                <div v-if="activityDetail.habitDetail" class="col-12 h-100">
                    <div class="col-12 d-flex align-items-center" style="border-bottom-style:solid;border-color: #c5c5c5;border-width:1px;height:50px">
                       <p class="ms-3 mb-0">{{activityDetail.habitDate}}</p>
                    </div>
                    <div class="col-6 float-start d-flex align-items-center" style="height:calc(100% - 50px)">
                        <div class="col-11 mx-auto text-center">
                            <img src="/cms-assets/images/Overview/checked.png" style="height:100px;width:100px;" alt="">
                            <p class="my-2" style="font-size:20px;">{{activityDetail.habitDetail.title}}</p>
                            <p style="color:#c5c5c5;">Completed {{activityDetail.habitPerc}}%</p>
                        </div>
                    </div>
                    <div class="col-6 float-start" style="border-left-style:solid;border-color: #c5c5c5;border-width:1px;overflow-y:auto;height:calc(100% - 50px)">
                        <div class="col-11 py-3 mx-auto">
                            <p class="col-12" style="word-break:break-all;text-transform:capitalize;">{{activityDetail.habitDetail.content}}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="type!=='habit'" class="col-4 h-100 position-relative float-start gray_bg" style="border-top-right-radius:15px;border-bottom-right-radius:15px;overflow-y:auto">
                <div class="col-12 position-relative" style="height:70px;">
                    <h4 class="mb-0 position-absolute px-3 fw-bold" style="bottom:20px;">Comments</h4>
                    <div class="col-12 position-absolute" style="height:1px;background-color:#c5c5c5;bottom:0"></div>
                </div>
                <div class="col-12 px-2 py-1" style="height:calc(100% - 205px);overflow-y:auto;">
                    <div v-if="activityDetail" v-for="(item, index) in activityDetail.comments" :key="index" class="col-12 py-2 px-1">
                        <div class="col-12" style="height:30px">
                            <img class="rounded-circle float-start" style="height:30px;width:30px;" :src="item.user_image" alt="">
                            <p class="mb-0 float-start ms-3 mt-1" style="color:#797c80;">{{item.user_name}}</p>
                            <p v-if="activityDetail.comments!==null" class="mb-0 float-start ms-3 mt-1" style="color:#c2c7cc;">{{item.date_time}}</p>
                        </div>
                        <div class="col-11 mx-auto mt-2">
                            {{item.content}}
                        </div>
                    </div>
                </div>
                <div class="col-12 py-3 position-absolute" style="bottom:0">
                    <div class="col-12" style="height:1px;background-color:#c5c5c5;"></div>
                    <div class="col-12 px-3 mt-2 d-flex" style="min-height:40px;align-items:center;">
                        <img class="rounded-circle float-start" style="height:30px;width:30px" :src="logInDetails.image" alt="">
                        <p class="col-8 mb-0 ms-3 float-start" style="color:#797c80;word-break:break-all;">{{logInDetails.name}}</p>
                    </div>
                    <div class="col-12 px-3 mt-2">
                        <textarea v-model="postData.comments" class="col-9 float-start mx-auto brds-1 p-2" style="border:1px solid #c5c5c5;background-color:white;font-size:11px;height:60px;overflow-y:auto" placeholder="Add a comment...."></textarea>
                        <button @click="addComment" class="prim_bg brds-2 mt-3 col-2 border-0 float-end py-1 px-2">Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { Loader, Inform },
    props: ['id', 'type', 'logInDetails'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            activityDetail: null,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            postData: {
                comments: null,
                activity_id: null,
            },
            rating: null,
        }
    },
    mounted() {
        this.getDetail();
    },
    methods: {
        getDetail() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'activity-comments/' + this.id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.activityDetail = res.data.data;
                    if (this.activityDetail.comments.length > 0) {
                        if (this.activityDetail.comments[0].rating == 1) {
                            this.rating = 'Really Easy';
                        }
                        else if (this.activityDetail.comments[0].rating == 2) {
                            this.rating = 'Easy';
                        }
                        else if (this.activityDetail.comments[0].rating == 3) {
                            this.rating = 'Somewhat Easy';
                        }
                        else if (this.activityDetail.comments[0].rating == 4) {
                            this.rating = 'Moderate';
                        }
                        else if (this.activityDetail.comments[0].rating == 5) {
                            this.rating = 'Somewhat Hard';
                        }
                        else if (this.activityDetail.comments[0].rating == 6) {
                            this.rating = 'Hard';
                        }
                        else if (this.activityDetail.comments[0].rating == 7) {
                            this.rating = 'Really Hard';
                        }
                        else if (this.activityDetail.comments[0].rating == 8) {
                            this.rating = 'Extremely Hard';
                        }
                        else if (this.activityDetail.comments[0].rating == 9) {
                            this.rating = 'Beyond Hard';
                        }
                    }
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        addComment() {
            if (this.postData.comments == null || this.postData.comments == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Enter a comment first';
                this.informModal = true;
                return
            }
            this.postData.activity_id = this.id;
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.post(config.baseApiUrl + 'comment-on-activity', this.postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.postData.comments = null;
                    this.getDetail();
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        quitComponent() {
            this.$parent.showactivityPopup(null);
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.w60 {
    width: 60% !important;
}
</style>
