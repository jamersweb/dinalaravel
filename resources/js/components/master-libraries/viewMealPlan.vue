<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Loader v-if="pageLoading" loadingText="Fetching" />
        <div class="main-box position-relative px-2 px-md-4 py-4">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:25px;top:15px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="w-100" v-if="mealDetail!==null">
                <div class="col-12 float-start">
                    <h3 class="cptl"><strong>{{mealDetail.name}}</strong></h3>
                </div>
                <div class="col-6 pe-3 float-start">
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="cptl fw-bold mb-0">Tags</p>
                    </div>
                    <div class="d-flex flex-wrap brds-1 p-2 border" style="height:100px;overflow-y:auto;">
                        <span v-if="mealDetail.tagNames.length>1" v-for="(item, index) in mealDetail.tagNames" :key="index" class="px-2 py-1 prim_bg mx-2 brds-1 my-1" style="height:35px;">{{item}}</span>
                        <p v-else>No tags added</p>
                    </div>
                </div>
                <div class="col-6 ps-3 float-start">
                    <div class="d-flex justify-content-between align-items-center">
                        <p class="cptl fw-bold mb-0">Description</p>
                    </div>
                    <div class="d-flex flex-wrap brds-1 p-2 border" style="height:100px;overflow-y:auto;">
                        <p v-if="mealDetail.description" class="mb-0 w-100" style="word-break:break-all;">{{mealDetail.description}}</p>
                        <p v-else>No description added </p>
                    </div>
                </div>
                <div v-if="type=='plan'" class="col-12  float-start mt-2">
                    <div v-if="mealDetail.attatchment!==null">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="cptl fw-bold mb-0">File 1</p>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 border justify-content-between" style="height:50px;overflow-y:auto;">
                            <p class="mb-0 float-start">{{mealDetail.attatchment_name}}</p>
                            <div v-if="mealDetail.attatchment!==null" class="float-end">
                                <a :href="mealDetail.file_view" target="blank"><button class="prim_bg px-3 py-1 me-1 border-0 brds-1">View PDF</button></a>
                                <a :href="mealDetail.file_downoad" :download="mealDetail.attatchment_name"><button class="prim_bg ms-1 px-3 py-1 border-0 brds-1">Download PDF</button></a>
                            </div>
                            <p v-else >No PDF added </p>
                        </div>
                    </div>
                    <div v-if="mealDetail.attatchment2!==null">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="cptl fw-bold mb-0 mt-2">File 2</p>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 border justify-content-between" style="height:50px;overflow-y:auto;">
                            <p class="mb-0 float-start">{{mealDetail.attatchment2_name}}</p>
                            <div v-if="mealDetail.attatchment2!==null" class="float-end">
                                <a :href="mealDetail.file_view2" target="blank"><button class="prim_bg px-3 py-1 me-1 border-0 brds-1">View PDF</button></a>
                                <a :href="mealDetail.file_downoad2" :download="mealDetail.attatchment2_name"><button class="prim_bg ms-1 px-3 py-1 border-0 brds-1">Download PDF</button></a>
                            </div>
                            <p v-else >No PDF added </p>
                        </div>
                    </div>
                    <div v-if="mealDetail.attatchment3!==null">
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="cptl fw-bold mb-0 mt-2">File 3</p>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 border justify-content-between" style="height:50px;overflow-y:auto;">
                            <p class="mb-0 float-start">{{mealDetail.attatchment3_name}}</p>
                            <div v-if="mealDetail.attatchment3!==null" class="float-end">
                                <a :href="mealDetail.file_view3" target="blank"><button class="prim_bg px-3 py-1 me-1 border-0 brds-1">View PDF</button></a>
                                <a :href="mealDetail.file_downoad3" :download="mealDetail.attatchment3_name"><button class="prim_bg ms-1 px-3 py-1 border-0 brds-1">Download PDF</button></a>
                            </div>
                            <p v-else >No PDF added </p>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="mealDetail!==null&&type=='days'" class="d-flex flex-wrap w-100 mt-0 mb-0" >
                <p v-if="mealDetail.breakfast!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Breakfast:</p>
                <div v-if="mealDetail.breakfast!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img v-if="mealDetail.breakfast_detail.file_type=='image'" :src="mealDetail.breakfast_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.breakfast_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.breakfast_detail.name}}</p>
                </div>
                <p v-if="mealDetail.lunch!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Lunch:</p>
                <div v-if="mealDetail.lunch!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img v-if="mealDetail.lunch_detail.file_type=='image'" :src="mealDetail.lunch_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.lunch_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.lunch_detail.name}}</p>
                </div>
                <p v-if="mealDetail.dinner!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Dinner:</p>
                <div v-if="mealDetail.dinner!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img v-if="mealDetail.dinner_detail.file_type=='image'" :src="mealDetail.dinner_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.dinner_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.dinner_detail.name}}</p>
                </div>
                <p v-if="mealDetail.snacks!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Snacks:</p>
                <div v-if="mealDetail.snacks!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img v-if="mealDetail.snacks_detail.file_type=='image'" :src="mealDetail.snacks_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.snacks_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.snacks_detail.name}}</p>
                </div>
                <p v-if="mealDetail.drinks!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Drink:</p>
                <div v-if="mealDetail.drinks!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img v-if="mealDetail.drinks_detail.file_type=='image'" :src="mealDetail.drinks_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.drinks_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.drinks_detail.name}}</p>
                </div>
            </div>
            <div v-if="mealDetail!==null&&type=='weeks'" class="d-flex flex-wrap w-100 mt-0 mb-0" >
                <p v-if="mealDetail.meal_day1!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day1:</p>
                <div v-if="mealDetail.meal_day1!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day1_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day1_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day2!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day2:</p>
                <div v-if="mealDetail.meal_day2!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day2_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day2_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day3!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day3:</p>
                <div v-if="mealDetail.meal_day3!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day3_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day3_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day4!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day4:</p>
                <div v-if="mealDetail.meal_day4!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day4_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day4_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day5!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day5:</p>
                <div v-if="mealDetail.meal_day5!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day5_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day5_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day6!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day6:</p>
                <div v-if="mealDetail.meal_day6!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day6_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day6_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day7!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day7:</p>
                <div v-if="mealDetail.meal_day7!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                    <img :src="mealDetail.meal_day7_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day7_detail.name}}</p>
                </div>
            </div>
            <div v-if="mealDetail!==null&&type=='plan'" class="d-flex flex-wrap w-100 mt-0 mb-0" >
                <div v-for="(item, index) in mealDetail.week_detail" :key="index" class="w-100">
                    <p class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Week {{index+1}} :</p>
                    <div v-if="item!==null" class=" float-start d-flex shd_card w-100 mt-1 mb-0 py-2">
                        <img :src="item.image" alt="" class="img-fluid" style="max-width:100px">
                        <p class="ms-3 mb-0" style="align-self: center;">{{item.name}}</p>
                    </div>
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
    props: ['data', 'type'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            mealDetail: null,
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    mounted() {
        if (this.type == 'days') {
            this.pageLoading = true;
            this.loaderText = 'Getting Meal days detail';
            axios.get(config.baseApiUrl + 'get-meal-day-detail/' + this.data, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.mealDetail = res.data.data;
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
        else if (this.type == 'weeks') {
            this.pageLoading = true;
            this.loaderText = 'Getting Meal week detail';
            axios.get(config.baseApiUrl + 'get-meal-week-detail/' + this.data, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.mealDetail = res.data.data;
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
        else if (this.type == 'plan') {
            this.pageLoading = true;
            this.loaderText = 'Getting Meal plan detail';
            axios.get(config.baseApiUrl + 'get-meal-plan-detail/' + this.data, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.mealDetail = res.data.data;
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
    methods: {
        quitComponent() {
            this.$parent.showViewMealPlanPopup(null);
        }
    }
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
