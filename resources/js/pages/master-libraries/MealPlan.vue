<template lang="">
    <div class="w-100 brds-2" style="height:calc(100vh - 127px);border:1px solid #e7e7e7;">
        <div class="exer-head" style="border-top-left-radius:10px;border-top-right-radius:10px;">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <button class="top-btn" :class="{ active: mealType=='days'}" @click="mealType='days'" style="border-top-left-radius:10px;border-bottom-left-radius:10px;border-left:1px solid #C5C5C5">Meal Days</button>
                    <button class="top-btn" :class="{ active: mealType=='weeks'}" @click="mealType='weeks'" >Meal Weeks</button>
                    <button class="top-btn" :class="{ active: mealType=='plan'}" @click="mealType='plan'" style="border-top-right-radius:10px;border-bottom-right-radius:10px;">Meal Plan</button>
                </div>
                <div class="d-flex align-items-center">
                    <div class="position-relative" style="width:250px">
                        <input @input="applySearch()" type="text" placeholder="search for a meal plan" v-model="search" class="py-2 pe-2 ps-4 w-100">
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
        <div style="height:60px;" class="w-100 pt-3 ps-2">
            <button @click="showCreateMealPlanPopup" class="prim_bg border-0 py-1 px-3 mx-2">New</button>
            <button @click="deleteMeal" class="tsl border-0 py-1 px-3 mx-2" style="background-color:transparent;">Delete</button>
            <button @click="editDWPFunc" class="tsl border-0 py-1 px-3 mx-2" style="background-color:transparent;">Edit</button>
        </div>
        <div class="w-100" style="height:calc(100% - 103px);overflow-y:auto;">
            <div class="row col-12 py-2 px-3 m-0">
                <h6 class="text-center" v-if="allMealsData.length==0"> No Data to Display</h6>
                <h6 class="text-center" v-else-if="finalVisibleData.length==0"> No Data Matches Your Search and Filters</h6>
                <div v-for="(item, index) in finalVisibleData" :key="index" class="col-3 text-center py-2 position-relative" style="cursor:pointer;">
                    <div class="col-12 tsl brds-3 p-2 text center position-relative">
                        <input type="checkbox" class="form-check-input position-absolute" :value="item.id" v-model="selectedItems" style="z-index:9;top:5px;left:10px;">
                        <img v-if="mealType=='days'" :src="item.image"  @click="showViewMealPlanPopup(item.id)" class="img-fluid w-100" style="height:110px; object-fit: contain; background: white;" alt="">
                        <img v-else-if="mealType=='weeks'" :src="item.image"  @click="showViewMealPlanPopup(item.id)" class="img-fluid w-100" style="height:110px; object-fit: contain; background: white;" alt="">
                        <img v-else-if="mealType=='plan'" :src="item.image"  @click="showViewMealPlanPopup(item.id)" class="img-fluid w-100" style="height:110px; object-fit: contain; background: white;" alt="">
                        <h6 class="mb-1 mt-2"  @click="showViewMealPlanPopup(item.id)" data-toggle="tooltip" :title="item.name">{{this.truncatedString(item.name)}}</h6>
                        <p style="font-size:12px" class="text-muted">{{item.language==='en'?'English':'Arabic'}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p style="opacity:0;">{{getmealType}}</p>
    <createMealPlan v-if="newMealPlan" :type="mealType" />
    <viewMealPlan v-if="mealplanDetail" :type="mealType" :data="idforAction"/>
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
    <MealDWPEdit v-if="editDWP" :type="mealType" :DWPdetails="mealDWP"/>
</template>
<script>
import config from "../../config";
import axios from "axios";
import createMealPlan from '../../components/master-libraries/createMealPlan.vue';
import viewMealPlan from '../../components/master-libraries/viewMealPlan.vue';
import MealDWPEdit from '../../components/master-libraries/mealDWPedit.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Filters from '../../components/filters.vue';
export default {
    components: { createMealPlan, viewMealPlan, Loader, Inform, Filters, MealDWPEdit },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            editDWP: false,
            mealDWP: null,
            newMealPlan: false,
            mealplanDetail: false,
            mealType: 'days',
            mealDetail: null,
            selectedItems: [],
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            idforAction: null,
            search: '',
            filters: false,
            allMealsData: [],
            selectedTagsForFilter: [],
            tagsFilteredData: [],
            finalVisibleData: []
        }
    },
    mounted() {
        this.getTags();
        this.getAllMealDays();
        this.$emit('adminCheckEvent');
    },
    computed: {
        getmealType() {
            this.selectedTagsForFilter = [];
            if (this.mealType == 'days') {
                this.getAllMealDays();
            }
            else if (this.mealType == 'weeks') {
                this.getAllMealWeeks();
            }
            else {
                this.getAllMealPlans();
            }
        },
    },
    methods: {
        truncatedString(title) {
        const maxLength = 20;
            if (title.length > maxLength) {
                return title.substring(0, maxLength) + '...';
            } else {
                return title;
            }
        },
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-tags?category=meal', this.apiConfig).then(res => {
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
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.tagsFilteredData = [];
            for (let i = 0; i < this.allMealsData.length; i++) {
                const wrk = this.allMealsData[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(wrk.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(wrk.tags.includes(tId)){
                        this.tagsFilteredData.push(wrk);
                        break;
                    }
                };
            }
            this.finalVisibleData = this.tagsFilteredData;
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.tagsFilteredData = this.allMealsData;
            this.finalVisibleData = this.allMealsData;
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if(searchValue==""){
                this.finalVisibleData = this.tagsFilteredData;
                this.search = '';
                return;
            }
            let tempArray = [];
            this.tagsFilteredData.forEach(mp => {
                if(mp.name.toLowerCase().includes(searchValue))
                tempArray.push(mp);
            });
            this.finalVisibleData = tempArray;
        },
        getAllMealPlans() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-meal-plans', this.apiConfig).then((res) => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allMealsData = res.data.data;
                    this.finalVisibleData = this.allMealsData;
                    this.tagsFilteredData = this.allMealsData;
                    this.selectedItems = [];
                } else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            });
        },
        getAllMealWeeks() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-meal-weeks', this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.allMealsData = res.data.data;
                        this.finalVisibleData = this.allMealsData;
                        this.tagsFilteredData = this.allMealsData;
                        this.selectedItems = [];
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
        getAllMealDays() {
            this.pageLoading = true;
            this.loaderText = 'Fetchnig';
            axios.get(config.baseApiUrl + 'get-meal-days', this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.allMealsData = res.data.data;
                        this.finalVisibleData = this.allMealsData;
                        this.tagsFilteredData = this.allMealsData;
                        this.selectedItems = [];
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
        deleteMeal() {
            if (this.selectedItems.length < 1) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Select to delete';
                this.informModal = true;
                return
            }
            else {
                let postData = {
                    ids: this.selectedItems,
                    delete_type: this.mealType
                };
                this.pageLoading = true;
                this.loaderText = 'Deleting';
                axios.post(config.baseApiUrl + 'delete-meal-day', postData, this.apiConfig)
                    .then((res) => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            if (this.mealType == 'days') {
                                this.getAllMealDays();
                            }
                            else if (this.mealType == 'weeks') {
                                this.getAllMealWeeks();
                            }
                            else if (this.mealType == 'plan') {
                                this.getAllMealPlans();
                            }
                            this.selectedItems = [];
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
        acknowledged() {
            this.informModal = false;
        },
        showCreateMealPlanPopup() {
            this.newMealPlan = !this.newMealPlan;
        },
        showViewMealPlanPopup(id) {
            this.mealplanDetail = !this.mealplanDetail;
            if (id !== null) {
                this.idforAction = id;
            }
        },
        editDWPFunc(){
            if(this.selectedItems.length!==1){
                this.modalTitle = 'Error!';
                this.modalDetail = 'Select only 1 to edit';
                this.informModal = true;
                return;
            }
            let endpoint;
            if(this.mealType==="days")
            endpoint = 'get-meal-day-detail/';
            else if(this.mealType==="weeks")
            endpoint = 'get-meal-week-detail/';
            else
            endpoint = 'get-meal-plan-detail/';

            axios.get(config.baseApiUrl+endpoint+this.selectedItems[0],this.apiConfig).then(res => {
                if(res.data.status){
                    this.mealDWP = res.data.data;
                    console.log("dwp: ",this.mealDWP);
                    this.editDWP = true;
                } else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("dwp get edit get error: ",res.data.error);
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
                console.log("dwp get edit get error: ",er.message);
            });
        }
    }
}
</script>
<style scoped>
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

.top-btn {
    height: 30px;
    font-size: 13px;
    color: #343434ee;
    border: 1px solid #C5C5C5;
    border-left: none;
    background-color: #FFFFFF;
}

.active {
    background-color: #F2A18C;
    border: none;
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
