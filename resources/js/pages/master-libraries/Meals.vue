<template lang="">
    <div class="main">
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
        <div class="col-12 topbar pt-2 ps-3">
            <h4 class="col-6 float-start pt-0" style="font-size:26px;">Meals</h4>
            <div class="col-6 ps-4 float-end">
                <img v-if="!showDetails" src="../../../../public/images/filter.png" @click="filters=true" class="col-1 float-end mx-2 mt-2 " style="height:16px;width:25px;cursor:pointer;" alt="Error">
                <div type="text" class="col-9 h-75 float-end mx-2 position-relative" style="border:1px solid;border-color:#C5C5C5;border-radius:10px;float:right;height:30px;background-color:#FFFFFF" >
                    <div class="col-1 float-start pt-1 ps-1">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="Error" style="height:13px;width:15px;z-index:99;">
                    </div>
                    <input @input="applySearch()" type="text" class="input1 col-9 h-75 mt-0 ms-2 float-start" style="border:none;background-color:transparent;" v-model="search" placeholder="Search for meal">
                    <div v-if="searchSuggestions.length > 0" class="meal-search-suggestions">
                        <button
                            v-for="item in searchSuggestions"
                            :key="item.id || item.name"
                            type="button"
                            class="meal-search-suggestion"
                            @mousedown.prevent="selectSearchSuggestion(item)"
                        >
                            <span>{{ item.name }}</span>
                            <small v-if="item.tagNames && item.tagNames.length">{{ item.tagNames.join(', ') }}</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 content" v-if="showContent">
            <div class="row w-100 mx-0 px-3 bar">
                <div class="col-6">
                    <button type="button" class="btn1" @click="createCustomMeal">New</button>
                    <button class="btn3 px-2 tsl" @click="deleteMeals" type="button">Delete</button>
                </div>
                <div class="col-6">
                    <div class="text-end mt-2">
                        <div class="d-inline-block" @click="changeBtnValue('all')">
                            <button class="col-4 bar-btn px-3 w-100">All</button>
                            <div :class="{btn_active : btnValue=='all'}" style="height:2px;width:100%;background-color:#707070;"></div>
                        </div>
                        <div class="d-inline-block" @click="changeBtnValue('breakfast')">
                            <button class="col-4 bar-btn px-3 w-100">Breakfast</button>
                            <div :class="{btn_active : btnValue=='breakfast'}" style="height:2px;width:100%;background-color:#707070;"></div>
                        </div>
                        <div class="d-inline-block" @click="changeBtnValue('lunch')">
                            <button class="col-4 bar-btn px-3 w-100">Lunch</button>
                            <div :class="{btn_active : btnValue=='lunch'}" style="height:2px;width:100%;background-color:#707070;"></div>
                        </div>
                        <div class="d-inline-block" @click="changeBtnValue('dinner')">
                            <button class="col-4 bar-btn px-3 w-100">Dinner</button>
                            <div :class="{btn_active : btnValue=='dinner'}" style="height:2px;width:100%;background-color:#707070;"></div>
                        </div>
                        <div class="d-inline-block" @click="changeBtnValue('snacks')">
                            <button class="col-4 bar-btn px-3 w-100">Snacks</button>
                            <div :class="{btn_active : btnValue=='snacks'}" style="height:2px;width:100%;background-color:#707070;"></div>
                        </div>
                    </div>
                    <!-- <div class="col-5 float-start mt-2 ps-2">
                        <select style="border:1px solid #EDEDED;width:95%;height:30px;font-size:12px;border-radius:5px;">
                            <option>Name</option>
                            <option>Last Modified</option>
                            <option>Calories</option>
                        </select>
                    </div> -->
                </div>
            </div>
            <div class="content-main tsh" v-if="allMeals">
                <p v-if="finalVisibleMeals.length < 1" class="f-20 fw-bold col-12" style="text-align:center;">No Meals to
                        Display</p>
                <div class="row col-12">
                    <div class="col-xl-3 col-md-4 col-sm-6 col-12" v-for="(items, index) in finalVisibleMeals" :key="index" >
                        <div class="content-card tsh position-relative">
                            <input type="checkbox" v-model="idsToDel" :value="items.id" class="form-check-input" style="position:absolute;top:5px;left:5px;">
                            <div v-if="items.file_type=='video'" class="col-5 h-100 float-start brds-2 img-as-bg" :style='{"background-image":"url("+items.video_thumbnail+")"}'>
                            </div>
                            <div v-else class="col-5 h-100 float-start brds-2 img-as-bg" :style='{"background-image":"url("+items.file+")"}'>
                            </div>
                            <div class="col-7 ps-3" style="float:left; height: 150px;">
                                <p class="mb-2 w-100" style="font-size:12px;font-weight:bold;cursor:pointer; height: 70px; overflow: hidden" @click="showDetailsDiv(items.id)" data-toggle="tooltip" :title="items.name">{{this.truncatedString(items.name)}}</p>
                                <p class="mb-2 w-100" style="font-size:10px;word-break:break-all;height: 27px;" ><span v-for="(item, index) in JSON.parse(items.suitable_for)" :key="index">{{item}},</span></p>
                                <img src="../../../../public/images/Group57965.png" alt="Error" style="height:15px;width:15px;float:left;">
                                <p style="float:left;font-size:8px;margin-top:3px;margin-left:3px;">{{items.calories_per_serving}} Cal / Serving</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDetails" class="w-100 p-3 position-relative" style="height:calc(100% - 60px);overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitDetails"
                style="right:20px;top:15px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <button class="trans_btn position-absolute" @click="editMeal = true;"
                style="right: 45px;top: 21px;font-size: 17px;">
                <i class="fa-solid fa-pen"></i>
            </button>
            <div class="tsh p-2 mx-0 w-100 brds-2 row">
                <div class="col-5 p-2 overflow-hidden">
                    <video v-if="mealDetails.file_type=='video'" :src="mealDetails.file" controls class="img-fluid brds-2 w-100"></video>
                    <img v-else :src="mealDetails.file" alt="Error" class="img-fluid brds-2 w-100">
                </div>
                <div class="col-7">
                    <p class="m-0 col-12" style="font-size:35px;font-weight:bold;">{{mealDetails.name}}</p>
                    <div class="col-12 m-1 mt-2 float-left" style="height:50px">
                        <div class="col-2" style="float:left;">
                            <img src="../../../../public/images/Group57965.png" alt="Error" style="height:52px;width:52px;">
                        </div>
                        <div class="col-9 ms-2 mb-0" style="float:left;">
                            <p class="m-0" style="font-size:20px;">{{mealDetails.calories_per_serving}} Cal / Serving</p>
                            <p class="m-0" style="font-size:10px;">{{mealDetails.protein_per_serving}}g Protein, {{mealDetails.carbs_per_serving}}g Carbs, {{mealDetails.fat_per_serving}}g Fat, {{mealDetails.fiber_per_serving}}g Fiber</p>
                        </div>
                    </div>
                    <div class="col-12 m-1 mt-2 float-left" style="height:50px">
                        <div class="col-2" style="float:left;">
                            <img src="../../../../public/images/Group57944.png" alt="Error" style="height:52px;width:52px;">
                        </div>
                        <div class="col-9 ms-2 mb-0" style="float:left;">
                            <p class="m-0" style="font-size:20px;">Recipe Makes</p>
                            <p class="m-0" style="font-size:10px;">{{mealDetails.no_of_servings}} Servings</p>
                        </div>
                    </div>
                    <div class="col-12 m-1 mt-2 float-left" style="height:50px">
                        <div class="col-2" style="float:left;">
                            <img src="../../../../public/images/Group57946.png" alt="Error" style="height:52px;width:52px;">
                        </div>
                        <div class="col-9 ms-2 mb-0" style="float:left;">
                            <p class="m-0" style="font-size:20px;">Total prep time {{parseInt(mealDetails.prep_time)+parseInt(mealDetails.cook_time)}} minutes</p>
                            <p class="m-0" style="font-size:10px;">Prepration: {{mealDetails.prep_time}} minutes /Cooking: {{mealDetails.cook_time}} minutes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mx-auto mt-3 mb-3 w-100 d-flex gap-4">
                <div class="tsh brds-2 p-3" style="float:left;width:35%;">
                    <table>
                        <h5 style="font-weight:bold;">Ingredients</h5>
                        <tr v-for="(items, index) in ingredients" :key="index">
                            <td v-if="mealDetails.meal_type=='auto'">{{items.name}} - {{parseInt(items.quantity1) + parseFloat(items.quantity2)}}</td>
                            <td v-else>{{items}}</td>
                        </tr>
                    </table>
                </div>
                <div class="tsh brds-2 p-3" style="float:right;width:63%;">
                    <table>
                        <h5 style="font-weight:bold;">Directions</h5>
                        <tr v-for="(items, index) in directions" :key="index">
                            <td class="wb-all">{{index+1}} - {{items}}</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="tsh mx-auto brds-2 p-2 w-100" style="min-height:50px;">
                <h5 class="fw-bold ms-2">Tags</h5>
                <div class="d-flex flex-wrap">
                    <span v-for="(items, index) in detailtagNames" :key="index" class="tg-pill mx-1 my-1">{{items}}</span>
                </div>
            </div>
        </div>
    </div>
    <CreateCustomMeal v-if="showCreate"/>
    <EditMeal v-if="editMeal" :mealDetail="mealDetails"/>
</template>
<script>
import CreateCustomMeal from "../../components/master-libraries/createCustomMeal";
import EditMeal from '../../components/master-libraries/editMeal.vue';
import Loader from "../../components/loader.vue";
import Inform from "../../components/inform.vue";
import Confirm from "../../components/confirm.vue";
import Filters from "../../components/filters.vue";
import config from "../../config";
import axios from "axios";
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            idsToDel: [],
            showContent: true,
            loaderText: null,
            suitable_for: null,
            mealDetails: null,
            allMeals: null,
            ingredients: null,
            directions: null,
            showDetails: false,
            showCreate: false,
            breakfast: true,
            lunch: false,
            dinner: false,
            snacks: false,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: null,
            modalDetail: null,
            search: "",
            btnValue: 'all',
            filters: false,
            tagsFilteredMeal: [],
            selectedTagsForFilter: [],
            finalVisibleMeals: [],
            tags: [],
            detailtagNames: null,
            editMeal: false,
        };
    },
    components: { CreateCustomMeal, Loader, Confirm, Inform, Filters, EditMeal },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getTags();
        this.getAllMeals();
    },
    computed: {
        searchSuggestions() {
            const searchValue = this.normalizeSearchText(this.search);
            if (searchValue === '') {
                return [];
            }

            return (this.tagsFilteredMeal || [])
                .map((item) => ({ item, score: this.mealSearchScore(item, searchValue) }))
                .filter((match) => match.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, 6)
                .map((match) => match.item);
        },
    },
    methods: {
        truncatedString(title) {
        const maxLength = 40;
            if (title.length > maxLength) {
                return title.substring(0, maxLength) + '...';
            } else {
                return title;
            }
        },
        normalizeSearchText(value) {
            return String(value ?? '')
                .toLowerCase()
                .replace(/[_\-/,.;:()[\]{}"'`~!@#$%^&*+=|\\<>?]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },
        collectSearchableValues(value) {
            if (value === null || value === undefined) {
                return [];
            }

            if (Array.isArray(value)) {
                return value.flatMap((item) => this.collectSearchableValues(item));
            }

            if (typeof value === 'object') {
                return Object.values(value).flatMap((item) => this.collectSearchableValues(item));
            }

            const text = String(value);
            const trimmed = text.trim();
            if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
                try {
                    return [text, ...this.collectSearchableValues(JSON.parse(trimmed))];
                } catch (e) {
                    return [text];
                }
            }

            return [text];
        },
        mealSearchText(item) {
            return this.normalizeSearchText(this.collectSearchableValues(item).join(' '));
        },
        mealSearchTerms(searchValue) {
            return searchValue.split(' ').filter((term) => term.length > 1);
        },
        mealMatchesSearch(item, searchValue, requireEveryTerm = false) {
            const searchableText = this.mealSearchText(item);
            if (searchableText.includes(searchValue)) {
                return true;
            }

            const terms = this.mealSearchTerms(searchValue);
            if (terms.length === 0) {
                return false;
            }

            return requireEveryTerm
                ? terms.every((term) => searchableText.includes(term))
                : terms.some((term) => searchableText.includes(term));
        },
        mealSearchScore(item, searchValue) {
            const searchableText = this.mealSearchText(item);
            const name = this.normalizeSearchText(item.name);
            const terms = this.mealSearchTerms(searchValue);
            let score = 0;

            if (name.includes(searchValue)) {
                score += 100;
            }
            if (searchableText.includes(searchValue)) {
                score += 50;
            }

            terms.forEach((term) => {
                if (name.includes(term)) {
                    score += 10;
                } else if (searchableText.includes(term)) {
                    score += 3;
                }
            });

            return score;
        },
        selectSearchSuggestion(item) {
            this.search = item.name;
            this.applySearch();
        },
        getVisibleMeals() {
            const searchValue = this.normalizeSearchText(this.search);
            let visibleMeals = this.tagsFilteredMeal || [];

            if (this.btnValue !== 'all') {
                visibleMeals = visibleMeals.filter((ml) => {
                    try {
                        return JSON.parse(ml.suitable_for || '[]').includes(this.btnValue);
                    } catch (e) {
                        return false;
                    }
                });
            }

            if (searchValue !== '') {
                visibleMeals = visibleMeals.filter((ml) => this.mealMatchesSearch(ml, searchValue, true));
            }

            return visibleMeals;
        },
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.tagsFilteredMeal = [];
            for (let i = 0; i < this.allMeals.length; i++) {
                const ml = this.allMeals[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(ml.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(ml.tags.includes(tId)){
                        this.tagsFilteredMeal.push(ml);
                        break;
                    }
                };
            }
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.tagsFilteredMeal = this.allMeals;
            this.applySearch();
        },
        applySearch(){
            if (this.normalizeSearchText(this.search) === '') {
                this.search = '';
            }
            this.finalVisibleMeals = this.getVisibleMeals();
        },
        selectedTab(){
            this.finalVisibleMeals = this.getVisibleMeals();
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
        createCustomMeal() {
            this.showCreate = !this.showCreate;
        },
        changeBtnValue(m) {
            this.btnValue = m;
            this.selectedTab();
        },
        showDetailsDiv(id) {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meal-detail/" + id, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.mealDetails = res.data.data;
                        this.pageLoading = false;
                        this.ingredients = JSON.parse(this.mealDetails.ingredients);
                        this.directions = JSON.parse(this.mealDetails.directions);
                        this.detailtagNames = this.mealDetails.tagNames;
                        this.showDetails = true;
                        this.showContent = false;
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        quitDetails() {
            this.showDetails = false;
            this.showContent = true;
        },
        getAllMeals() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meals", this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.allMeals = res.data.data;
                        this.finalVisibleMeals = this.allMeals;
                        this.tagsFilteredMeal = this.allMeals;
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Meals not fetched';
                    this.informModal = true;
                })
        },
        deleteMeals() {
            if (this.idsToDel.length == 0) {
                this.informModal = true;
                this.modalTitle = 'Error';
                this.modalDetail = 'No Meals selected';
                return;
            }
            const postData = {
                'ids': this.idsToDel
            };
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.post(config.baseApiUrl + "delete-meals", postData, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.pageLoading = false;
                        this.idsToDel = [];
                        this.getAllMeals();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Meals not deleted';
                })
        },
        acknowledged() {
            this.informModal = false;
        },
    },
};
</script>
<style scoped>
.main {
    width: 100%;
    height: calc(100vh - 125px);
    padding: 0px;
    border: 1px solid #e7e7e7;
    border-radius: 15px;
}

.topbar {
    height: 50px;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
    background-color: #eeeeee;
}

.input1::placeholder {
    font-size: 9px;
}

.meal-search-suggestions {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    z-index: 20;
    max-height: 220px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #f2a18c;
    border-radius: 8px;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
}

.meal-search-suggestion {
    display: block;
    width: 100%;
    padding: 8px 12px;
    border: 0;
    background: #fff;
    text-align: left;
}

.meal-search-suggestion:hover {
    background: #f7f7f7;
}

.meal-search-suggestion span,
.meal-search-suggestion small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.meal-search-suggestion small {
    color: #777;
    font-size: 11px;
}

.content {
    height: calc(100% - 60px);
}

.bar {
    height: 50px;
}

.btn1 {
    background-color: #f2a18c;
    border: none;
    width: 120px;
    height: 25px;
    font-size: 13px;
    font-weight: 100;
    float: left;
    padding: 0;
    margin: 10px 0px 0px 8px;
    border-radius: 3px;
}
.img-as-bg{
    background-position: center;
    background-size: cover;
}
.btn1:hover {
    background-color: black;
    color: #f2a18c;
}

.btn3 {
    background-color: #ffffff;
    border: none;
    height: 25px;
    float: left;
    padding: 0px 0px 3px 5px;
    margin: 10px 0px 0px 8px;
    border-radius: 3px;
}

.three-dots:after {
    cursor: pointer;
    content: "\2807";
    font-size: 18px;
    padding: 0px 0px 0px 0px;
}

.bar-btn {
    border: none;
    background-color: transparent;
    font-size: 12px;
}

.active {
    border-bottom: 4px solid #f2a18c;
    z-index: 99;
}

.content-main {
    height: calc(100% - 50px);
    width: 98%;
    padding: 20px;
    overflow-y: auto;
    border-radius: 10px;
    margin-right: auto;
    margin-left: auto;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
}

.content-card {
    width: 100%;
    height: 150px;
    border-radius: 10px;
    border: none;
    padding: 10px;
    float: left;
    margin: 10px;
}

.btn_active {
    height: 6px !important;
    border-radius: 10px !important;
    background-color: #f2a18c !important;
    margin-top: -2px;
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
