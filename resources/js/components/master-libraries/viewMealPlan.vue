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
                <div v-if="mealDetail.breakfast!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(mealDetail.breakfast, mealDetail.breakfast_detail)">
                    <img v-if="mealDetail.breakfast_detail.file_type=='image'" :src="mealDetail.breakfast_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.breakfast_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.breakfast_detail.name}}</p>
                </div>
                <p v-if="mealDetail.lunch!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Lunch:</p>
                <div v-if="mealDetail.lunch!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(mealDetail.lunch, mealDetail.lunch_detail)">
                    <img v-if="mealDetail.lunch_detail.file_type=='image'" :src="mealDetail.lunch_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.lunch_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.lunch_detail.name}}</p>
                </div>
                <p v-if="mealDetail.dinner!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Dinner:</p>
                <div v-if="mealDetail.dinner!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(mealDetail.dinner, mealDetail.dinner_detail)">
                    <img v-if="mealDetail.dinner_detail.file_type=='image'" :src="mealDetail.dinner_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.dinner_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.dinner_detail.name}}</p>
                </div>
                <p v-if="mealDetail.snacks!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Snacks:</p>
                <div v-if="mealDetail.snacks!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(mealDetail.snacks, mealDetail.snacks_detail)">
                    <img v-if="mealDetail.snacks_detail.file_type=='image'" :src="mealDetail.snacks_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.snacks_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.snacks_detail.name}}</p>
                </div>
                <p v-if="mealDetail.drinks!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Drink:</p>
                <div v-if="mealDetail.drinks!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(mealDetail.drinks, mealDetail.drinks_detail)">
                    <img v-if="mealDetail.drinks_detail.file_type=='image'" :src="mealDetail.drinks_detail.file" alt="" class="img-fluid" style="max-width:100px">
                    <img v-else :src="mealDetail.drinks_detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.drinks_detail.name}}</p>
                </div>
            </div>
            <div v-if="mealDetail!==null&&type=='weeks'" class="d-flex flex-wrap w-100 mt-0 mb-0" >
                <p v-if="mealDetail.meal_day1!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day1:</p>
                <div v-if="mealDetail.meal_day1!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day1, mealDetail.meal_day1_detail)">
                    <img :src="mealDetail.meal_day1_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day1_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day2!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day2:</p>
                <div v-if="mealDetail.meal_day2!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day2, mealDetail.meal_day2_detail)">
                    <img :src="mealDetail.meal_day2_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day2_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day3!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day3:</p>
                <div v-if="mealDetail.meal_day3!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day3, mealDetail.meal_day3_detail)">
                    <img :src="mealDetail.meal_day3_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day3_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day4!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day4:</p>
                <div v-if="mealDetail.meal_day4!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day4, mealDetail.meal_day4_detail)">
                    <img :src="mealDetail.meal_day4_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day4_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day5!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day5:</p>
                <div v-if="mealDetail.meal_day5!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day5, mealDetail.meal_day5_detail)">
                    <img :src="mealDetail.meal_day5_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day5_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day6!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day6:</p>
                <div v-if="mealDetail.meal_day6!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day6, mealDetail.meal_day6_detail)">
                    <img :src="mealDetail.meal_day6_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day6_detail.name}}</p>
                </div>
                <p v-if="mealDetail.meal_day7!==null" class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Day7:</p>
                <div v-if="mealDetail.meal_day7!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(mealDetail.meal_day7, mealDetail.meal_day7_detail)">
                    <img :src="mealDetail.meal_day7_detail.image" alt="" class="img-fluid" style="max-width:100px">
                    <p class="ms-3 mb-0" style="align-self: center;">{{mealDetail.meal_day7_detail.name}}</p>
                </div>
            </div>
            <div v-if="mealDetail!==null&&type=='plan'" class="d-flex flex-wrap w-100 mt-0 mb-0" >
                <div v-for="(item, index) in mealDetail.week_detail" :key="index" class="w-100">
                    <p class="ms-3 mb-0 mt-3 fw-bold" style="align-self: center;">Week {{index+1}} :</p>
                    <div v-if="item!==null" class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealWeekDetails(item.id, item)">
                        <img :src="item.image" alt="" class="img-fluid" style="max-width:100px">
                        <p class="ms-3 mb-0" style="align-self: center;">{{item.name}}</p>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="selectedMealDetail" class="meal-detail-overlay meal-detail-overlay-meal" @click.self="closeMealDetails">
            <div class="meal-detail-box position-relative p-3">
                <button class="trans_btn position-absolute" @click="closeMealDetails" style="right:18px;top:12px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="row w-100 mx-0">
                    <div class="col-md-5 p-2">
                        <video v-if="selectedMealDetail.file_type=='video'" :src="selectedMealDetail.file" controls class="img-fluid brds-2 w-100"></video>
                        <img v-else :src="selectedMealDetail.file" alt="Meal" class="img-fluid brds-2 w-100">
                    </div>
                    <div class="col-md-7 p-2 pe-5">
                        <h2 class="fw-bold mb-3">{{selectedMealDetail.name}}</h2>
                        <p class="mb-1 fs-4">{{selectedMealDetail.calories_per_serving}} Cal / Serving</p>
                        <p class="mb-3 text-muted">{{selectedMealDetail.protein_per_serving}}g Protein, {{selectedMealDetail.carbs_per_serving}}g Carbs, {{selectedMealDetail.fat_per_serving}}g Fat, {{selectedMealDetail.fiber_per_serving}}g Fiber</p>
                        <p class="mb-1 fs-5">Recipe Makes</p>
                        <p class="mb-3 text-muted">{{selectedMealDetail.no_of_servings}} Servings</p>
                        <p class="mb-1 fs-5">Total prep time {{mealTotalPrepTime(selectedMealDetail)}} minutes</p>
                        <p class="mb-0 text-muted">Preparation: {{selectedMealDetail.prep_time}} minutes / Cooking: {{selectedMealDetail.cook_time}} minutes</p>
                    </div>
                </div>
                <div class="row w-100 mx-0 mt-3">
                    <div class="col-md-5 p-2">
                        <div class="tsh brds-2 p-3 h-100">
                            <h5 class="fw-bold">Ingredients</h5>
                            <p v-if="selectedMealIngredients.length < 1" class="mb-0">No ingredients added</p>
                            <p v-for="(item, index) in selectedMealIngredients" :key="index" class="mb-1">
                                <span v-if="selectedMealDetail.meal_type=='auto'">{{item.name}} - {{parseInt(item.quantity1) + parseFloat(item.quantity2)}}</span>
                                <span v-else>{{item}}</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-7 p-2">
                        <div class="tsh brds-2 p-3 h-100">
                            <h5 class="fw-bold">Directions</h5>
                            <p v-if="selectedMealDirections.length < 1" class="mb-0">No directions added</p>
                            <p v-for="(item, index) in selectedMealDirections" :key="index" class="mb-1 wb-all">{{index+1}} - {{item}}</p>
                        </div>
                    </div>
                </div>
                <div class="tsh brds-2 p-3 mt-3">
                    <h5 class="fw-bold">Tags</h5>
                    <span v-for="(item, index) in selectedMealTags" :key="index" class="px-2 py-1 prim_bg mx-1 brds-1 my-1 d-inline-block">{{item}}</span>
                    <p v-if="selectedMealTags.length < 1" class="mb-0">No tags added</p>
                </div>
            </div>
        </div>
        <div v-if="selectedMealDayDetail" class="meal-detail-overlay meal-detail-overlay-day" @click.self="closeMealDayDetails">
            <div class="meal-detail-box position-relative p-3">
                <button class="trans_btn position-absolute" @click="closeMealDayDetails" style="right:18px;top:12px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h3 class="fw-bold pe-5">{{selectedMealDayDetail.name}}</h3>
                <div class="row w-100 mx-0 mt-2">
                    <div class="col-md-6 p-2">
                        <p class="fw-bold mb-1">Tags</p>
                        <div class="d-flex flex-wrap brds-1 p-2 border detail-meta-box">
                            <span v-for="(item, index) in selectedMealDayTags" :key="index" class="px-2 py-1 prim_bg mx-1 brds-1 my-1">{{item}}</span>
                            <p v-if="selectedMealDayTags.length < 1" class="mb-0">No tags added</p>
                        </div>
                    </div>
                    <div class="col-md-6 p-2">
                        <p class="fw-bold mb-1">Description</p>
                        <div class="brds-1 p-2 border detail-meta-box">
                            <p class="mb-0 wb-all" v-if="selectedMealDayDetail.description">{{selectedMealDayDetail.description}}</p>
                            <p class="mb-0" v-else>No description added</p>
                        </div>
                    </div>
                </div>
                <div class="w-100 mt-2">
                    <template v-for="slot in dayMealSlots(selectedMealDayDetail)" :key="slot.label">
                        <p class="ms-3 mb-0 mt-3 fw-bold">{{slot.label}}:</p>
                        <div class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDetails(slot.id, slot.detail)">
                            <img v-if="slot.detail.file_type=='image'" :src="slot.detail.file" alt="" class="img-fluid" style="max-width:100px">
                            <img v-else :src="slot.detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                            <p class="ms-3 mb-0" style="align-self: center;">{{slot.detail.name}}</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div v-if="selectedMealWeekDetail" class="meal-detail-overlay meal-detail-overlay-week" @click.self="closeMealWeekDetails">
            <div class="meal-detail-box position-relative p-3">
                <button class="trans_btn position-absolute" @click="closeMealWeekDetails" style="right:18px;top:12px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h3 class="fw-bold pe-5">{{selectedMealWeekDetail.name}}</h3>
                <div class="row w-100 mx-0 mt-2">
                    <div class="col-md-6 p-2">
                        <p class="fw-bold mb-1">Tags</p>
                        <div class="d-flex flex-wrap brds-1 p-2 border detail-meta-box">
                            <span v-for="(item, index) in selectedMealWeekTags" :key="index" class="px-2 py-1 prim_bg mx-1 brds-1 my-1">{{item}}</span>
                            <p v-if="selectedMealWeekTags.length < 1" class="mb-0">No tags added</p>
                        </div>
                    </div>
                    <div class="col-md-6 p-2">
                        <p class="fw-bold mb-1">Description</p>
                        <div class="brds-1 p-2 border detail-meta-box">
                            <p class="mb-0 wb-all" v-if="selectedMealWeekDetail.description">{{selectedMealWeekDetail.description}}</p>
                            <p class="mb-0" v-else>No description added</p>
                        </div>
                    </div>
                </div>
                <div class="w-100 mt-2">
                    <template v-for="slot in weekDaySlots(selectedMealWeekDetail)" :key="slot.label">
                        <p class="ms-3 mb-0 mt-3 fw-bold">{{slot.label}}:</p>
                        <div class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-row" @click="showMealDayDetails(slot.id, slot.detail)">
                            <img :src="slot.detail.image" alt="" class="img-fluid" style="max-width:100px">
                            <p class="ms-3 mb-0" style="align-self: center;">{{slot.detail.name}}</p>
                        </div>
                    </template>
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
            selectedMealDetail: null,
            selectedMealIngredients: [],
            selectedMealDirections: [],
            selectedMealTags: [],
            selectedMealDayDetail: null,
            selectedMealDayTags: [],
            selectedMealWeekDetail: null,
            selectedMealWeekTags: [],
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
        parseJsonList(value) {
            if (Array.isArray(value)) {
                return value;
            }
            if (value === null || value === undefined || value === '') {
                return [];
            }
            try {
                return JSON.parse(value);
            } catch (e) {
                return [];
            }
        },
        mealTotalPrepTime(meal) {
            return (parseInt(meal.prep_time) || 0) + (parseInt(meal.cook_time) || 0);
        },
        dayMealSlots(dayDetail) {
            if (!dayDetail) {
                return [];
            }

            return [
                { label: 'Breakfast', id: dayDetail.breakfast, detail: dayDetail.breakfast_detail },
                { label: 'Lunch', id: dayDetail.lunch, detail: dayDetail.lunch_detail },
                { label: 'Dinner', id: dayDetail.dinner, detail: dayDetail.dinner_detail },
                { label: 'Snacks', id: dayDetail.snacks, detail: dayDetail.snacks_detail },
                { label: 'Drink', id: dayDetail.drinks, detail: dayDetail.drinks_detail },
            ].filter((slot) => slot.id !== null && slot.id !== undefined && slot.detail);
        },
        weekDaySlots(weekDetail) {
            if (!weekDetail) {
                return [];
            }

            return [
                { label: 'Day1', id: weekDetail.meal_day1, detail: weekDetail.meal_day1_detail },
                { label: 'Day2', id: weekDetail.meal_day2, detail: weekDetail.meal_day2_detail },
                { label: 'Day3', id: weekDetail.meal_day3, detail: weekDetail.meal_day3_detail },
                { label: 'Day4', id: weekDetail.meal_day4, detail: weekDetail.meal_day4_detail },
                { label: 'Day5', id: weekDetail.meal_day5, detail: weekDetail.meal_day5_detail },
                { label: 'Day6', id: weekDetail.meal_day6, detail: weekDetail.meal_day6_detail },
                { label: 'Day7', id: weekDetail.meal_day7, detail: weekDetail.meal_day7_detail },
            ].filter((slot) => slot.id !== null && slot.id !== undefined && slot.detail);
        },
        showMealDetails(mealId, mealSummary) {
            const id = mealId || (mealSummary ? mealSummary.id : null);
            if (!id) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal';
            axios.get(config.baseApiUrl + 'get-meal-detail/' + id, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.selectedMealDetail = res.data.data;
                        this.selectedMealIngredients = this.parseJsonList(this.selectedMealDetail.ingredients);
                        this.selectedMealDirections = this.parseJsonList(this.selectedMealDetail.directions);
                        this.selectedMealTags = this.selectedMealDetail.tagNames || [];
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
        showMealDayDetails(mealDayId, mealDaySummary) {
            const id = mealDayId || (mealDaySummary ? mealDaySummary.id : null);
            if (!id) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal Day';
            axios.get(config.baseApiUrl + 'get-meal-day-detail/' + id, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.selectedMealDayDetail = res.data.data;
                        this.selectedMealDayTags = this.selectedMealDayDetail.tagNames || [];
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
        showMealWeekDetails(mealWeekId, mealWeekSummary) {
            const id = mealWeekId || (mealWeekSummary ? mealWeekSummary.id : null);
            if (!id) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal Week';
            axios.get(config.baseApiUrl + 'get-meal-week-detail/' + id, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.selectedMealWeekDetail = res.data.data;
                        this.selectedMealWeekTags = this.selectedMealWeekDetail.tagNames || [];
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
        closeMealDetails() {
            this.selectedMealDetail = null;
            this.selectedMealIngredients = [];
            this.selectedMealDirections = [];
            this.selectedMealTags = [];
        },
        closeMealDayDetails() {
            this.selectedMealDayDetail = null;
            this.selectedMealDayTags = [];
        },
        closeMealWeekDetails() {
            this.selectedMealWeekDetail = null;
            this.selectedMealWeekTags = [];
        },
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

.meal-row {
    cursor: pointer;
}

.meal-row:hover {
    transform: translateY(-1px);
}

.meal-detail-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.meal-detail-overlay-week {
    z-index: 1050;
}

.meal-detail-overlay-day {
    z-index: 1060;
}

.meal-detail-overlay-meal {
    z-index: 1070;
}

.meal-detail-box {
    background: white;
    border-radius: 20px;
    width: min(1000px, 92vw);
    max-height: 88vh;
    overflow-y: auto;
}

.detail-meta-box {
    min-height: 90px;
    overflow-y: auto;
}
</style>
