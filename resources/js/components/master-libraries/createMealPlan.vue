<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="my-popup-component" @click.self="quitComponent">
        <div class="w-90 position-relative brds-5 p-4" style="height:90vh;background-color:white;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent" style="right:10px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="gray_bg px-4 py-2 brds-2 d-flex justify-content-between" style="width:99%;">
                <div>
                    <p class="mb-0 me-3 py-2 float-start">Name:</p>
                    <input v-model="postData.name" class="brds-2 p-2 float-start me-4" style="border:1px solid #c5c5c5;color:#a69e9e;" type="text" placeholder="Enter name">
                    <p class="mb-0 me-3 py-2 float-start">Language:</p>
                    <select v-model="postData.language" @change="languageChanged()" class="brds-2 p-2" style="border:1px solid #c5c5c5;color:#a69e9e;">
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                    </select>
                </div>
                <div>
                    <p v-if="type=='plan'" class="mb-0 pt-2 mx-1 float-start">Select Weeks: </p>
                    <select v-if="type=='plan'" @change="mealPlanDays()" v-model="durationweeks" class="brds-2 p-2 mx-1 float-start" style="border:1px solid #c5c5c5;color:#a69e9e">
                        <option value="1" selected>1 Week</option>
                        <option value="2">2 Week</option>
                        <option value="3">3 Week</option>
                        <option value="4">4 Week</option>
                        <option value="5">5 Week</option>
                        <option value="6">6 Week</option>
                        <option value="7">7 Week</option>
                        <option value="8">8 Week</option>
                        <option value="9">9 Week</option>
                        <option value="10">10 Week</option>
                    </select>
                    <button @click="build()" class="prim_btn px-5 text-white float-start" style="border-radius:10px">Save</button>
                </div>
            </div>
            <div class="col-12 d-flex position-relative justify-content-around" style="min-height:500px;">
                <div class="col-7 px-2 pt-2 h-100 position-relative">
                    <h2 v-if="type=='plan'"><strong>Files</strong></h2>
                    <div v-if="type=='plan'">
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">Thumbnail: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <img v-if="thumbnailPreview" :src="thumbnailPreview" class="me-2" alt="Meal plan thumbnail preview" style="height:54px;width:85px;object-fit:contain;background:white;">
                                <p v-if="postData.image==null" class="mb-0">Select an image file (optional)</p>
                                <p v-else class="mb-0">{{postData.image.name}}</p>
                                <input type="file" @change="getImage" ref="thumbnailFile" accept="image/*" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 1: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="postData.attatchment==null" class="mb-0">Select a PDF File</p>
                                <p v-else class="mb-0">{{postData.attatchment.name}}</p>
                                <input type="file" @change="getFile" ref="PDFFile" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 2: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="postData.attatchment2==null" class="mb-0">Select a PDF File (optional)</p>
                                <p v-else class="mb-0">{{postData.attatchment2.name}}</p>
                                <input type="file" @change="getFile2" ref="PDFFile2" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 3: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="postData.attatchment3==null" class="mb-0">Select a PDF File (optional)</p>
                                <p v-else class="mb-0">{{postData.attatchment3.name}}</p>
                                <input type="file" @change="getFile3" ref="PDFFile3" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                    </div>
                    <h2 class="mt-2"><strong>Description</strong></h2>
                    <textarea v-model="postData.description" class="col-12 mt-1 mx-auto tsl border-0 brds-2 px-2" style="height:70px" placeholder="Enter description"></textarea>
                    <div class="col-12 mt-2" style="height:40px;">
                        <h2 class="mb-0 float-start"><strong>Meals</strong></h2>
                        <button @click="removeFromDay" class="scnd_btn brds-2 py-1 mt-1 float-end">Remove</button>
                    </div>
                    <div class="col-12 px-2 pt-2 mt-2" style="overflow-y:auto;height:435px;">
                        <div v-if="type=='days'" class="col-12 tsl my-3 brds-2 p-2 ">
                            <div class="col-12 d-flex justify-content-around mt-2">
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop(0,'Breakfast')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="days[0].Breakfast==null">Drag and Drop <strong>Breakfast</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" :value="0+' Breakfast'" v-model="selectedItems">
                                        <img v-if="days[0].Breakfast.file_type=='image'" :src="days[0].Breakfast.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="days[0].Breakfast.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="days[0].Breakfast.name" style="word-break:break-all;">{{this.truncatedString(days[0].Breakfast.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop(0,'Lunch')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="days[0].Lunch==null">Drag and Drop <strong>Lunch</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" :value="0+' Lunch'" v-model="selectedItems">
                                        <img v-if="days[0].Lunch.file_type=='image'" :src="days[0].Lunch.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="days[0].Lunch.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="days[0].Lunch.name" style="word-break:break-all;">{{this.truncatedString(days[0].Lunch.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop(0,'Dinner')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="days[0].Dinner==null">Drag and Drop <strong>Dinner</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" :value="0+' Dinner'" v-model="selectedItems">
                                        <img v-if="days[0].Dinner.file_type=='image'" :src="days[0].Dinner.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="days[0].Dinner.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="days[0].Dinner.name" style="word-break:break-all;">{{this.truncatedString(days[0].Dinner.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop(0,'Snacks')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="days[0].Snacks==null">Drag and Drop <strong>Snacks</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" :value="0+' Snacks'" v-model="selectedItems">
                                        <img v-if="days[0].Snacks.file_type=='image'" :src="days[0].Snacks.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="days[0].Snacks.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="days[0].Snacks.name" style="word-break:break-all;">{{this.truncatedString(days[0].Snacks.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop(0,'Drink')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="days[0].Drink==null">Drag and Drop <strong>Drink</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" :value="0+' Drink'" v-model="selectedItems">
                                        <img v-if="days[0].Drink.file_type=='image'" :src="days[0].Drink.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="days[0].Drink.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="days[0].Drink.name" style="word-break:break-all;">{{this.truncatedString(days[0].Drink.name,15)}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='weeks'" v-for="(item, index) in weeks" class="col-4 float-start my-3 p-2">
                            <div class="tsl brds-2 py-2 px-3">
                                <strong> Day{{index+1}}</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" :key="index" @drop="onDrop(index,'Breakfast')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="weeks[index]==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" :value="index" v-model="selectedItems">
                                            <img  :src="weeks[index].image" alt="" style="height:80px;width:80%; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="weeks[index].name">{{this.truncatedString(weeks[index].name,20)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='plan'" v-for="(item, index) in plan" class="col-4 float-start my-3 p-2">
                            <div class="tsl brds-2 py-2 px-3">
                                <strong> Week{{index+1}}</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" :key="index" @drop="onDrop(index,'Breakfast')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="plan[index]==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" :value="index" v-model="selectedItems">
                                            <img :src="plan[index].image" alt="" style="height:80px;width:80%; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="plan[index].name">{{this.truncatedString(plan[index].name,20)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-5 px-2 py-4 h-100">
                    <div class="shd_card p-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-muted">Tags:</h5>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 mt-2 border">
                            <span v-for="tag in selectedTags" class="px-2 py-1 prim_bg mx-2 brds-1 my-1">{{tag}}</span>
                            <button class="scnd_btn px-4 py-1 brds-2 my-1 mx-2" @click="assignTagsShow">Add +</button>
                        </div>
                    </div>
                    <div class="shd_card heavy_shd my-2 p-md-3 p-1">
                        <div class="d-flex justify-content-between brds-1 gray_bg p-3">
                            <div class="position-relative w-100">
                                <input
                                    type="text"
                                    class="w-100 exSearch"
                                    placeholder="Search for a Meal"
                                    v-model="search"
                                    @input="showSearchSuggestions = true"
                                    @focus="showSearchSuggestions = true"
                                    @blur="hideSearchSuggestions"
                                    @keydown.escape="showSearchSuggestions = false"
                                >
                                <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
                                <div v-if="showSearchSuggestions && searchSuggestions.length > 0" class="meal-search-suggestions">
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
                            <!-- <div>
                                <button class="trans_btn py-2">
                                    <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid">
                                </button>
                            </div> -->
                        </div>
                        <div class="mt-4 p-3 d-flex justify-content-between shd_card">
                            <!-- <button class="text-muted align-self-center trans_btn">+Add Exercise</button>
                            <select>
                                <option value="">Name</option>
                            </select> -->
                            <h5>Drag and Drop to add</h5>
                        </div>
                        <div class="p-2" style="height:300px;overflow-y:auto;overflow-x:hidden;">
                            <div class="row text-center">
                                <div v-for="(item, index) in filteredMeals" :key="item.name"  class="col-xl-3 col-md-4 col-sm-6 col-12 mt-3">
                                    <div class="shd_card p-0 h-100 text-center drag-el" draggable="true" @dragstart="startDrag(item)" style="width:100%;cursor:pointer">
                                        <div v-if="type=='days'" class="p-2">
                                            <img v-if="item.file_type=='image'" :src="item.file" alt="" style="height:80px;width:100%;">
                                            <img v-else :src="item.video_thumbnail" alt="" style="height:80px;width:100%;">
                                        </div>
                                        <div v-else class="p-2">
                                            <img :src="item.image" alt="" style="height:80px;width:100%;">
                                        </div>
                                        <p class="mb-0 col-12" style="word-break:break-all;" data-toggle="tooltip" :title="item.name">{{this.truncatedString(item.name,15)}}</p>
                                    </div>
                                </div>
                                <p v-if="filteredMeals.length==0" class="mt-3 fw-bold">No Meals to display regarding the filter</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <assignTags v-if="showTags" :tagType="'meal'" :prefilledTags="postData.tags"/>
</template>
<script>
import config from "../../config";
import axios from "axios";
import assignTags from '../clients/assignTags.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
export default {
    components: { Loader, Inform, assignTags },
    props: ['type'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            apiConfig2: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            postData: {
                name: null,
                description: null,
                tags: [],
                image: null,
                attatchment: null,
                attatchment2: null,
                attatchment3: null,
                language: 'en'
            },
            days: [{ Breakfast: null, Lunch: null, Dinner: null, Snacks: null, Drink: null }],
            weeks: [null, null, null, null, null, null, null],
            plan: [null],
            allMeals: [],
            tempItem: null,
            search: "",
            showTags: false,
            tags: [],
            selectedTags: [],
            selectedItems: [],
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            durationweeks: '1',
            thumbnailPreview: null,
            showSearchSuggestions: false
        }
    },
    computed: {
        filteredMeals() {
            const searchValue = this.normalizeSearchText(this.search);
            if (searchValue === '') {
                return this.allMeals;
            }

            return this.allMeals.filter((item) => this.mealMatchesSearch(item, searchValue, true));
        },
        searchSuggestions() {
            const searchValue = this.normalizeSearchText(this.search);
            if (searchValue === '') {
                return [];
            }

            return this.allMeals
                .map((item) => ({ item, score: this.mealSearchScore(item, searchValue) }))
                .filter((match) => match.score > 0)
                .sort((a, b) => b.score - a.score)
                .slice(0, 6)
                .map((match) => match.item);
        },
    },
    mounted() {
        if (this.type == 'days') {
            this.getAllMeals();
        }
        else if (this.type == 'weeks') {
            this.getAllMealDays();
        }
        else if (this.type == 'plan') {
            this.getAllMealWeeks();
        }
    },
    methods: {
        truncatedString(title,maxLength) {
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
            this.showSearchSuggestions = false;
        },
        hideSearchSuggestions() {
            setTimeout(() => {
                this.showSearchSuggestions = false;
            }, 120);
        },
        getImage() {
            const tempFile = this.$refs.thumbnailFile.files[0];
            if (tempFile == null) {
                return;
            }
            if (!tempFile.type.includes('image')) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Selected file is not an image';
                this.informModal = true;
                this.postData.image = null;
                this.thumbnailPreview = null;
                return;
            }
            this.postData.image = tempFile;
            this.thumbnailPreview = URL.createObjectURL(tempFile);
        },
        getFile() {
            this.postData.attatchment = this.$refs.PDFFile.files[0];
            if (!this.postData.attatchment.type.includes('pdf')) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Selected file is not PDF';
                this.informModal = true;
                this.postData.attatchment = null;
            }
        },
        getFile2() {
            this.postData.attatchment2 = this.$refs.PDFFile2.files[0];
            if (!this.postData.attatchment2.type.includes('pdf')) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Selected file is not PDF';
                this.informModal = true;
                this.postData.attatchment2 = null;
            }
        },
        getFile3() {
            this.postData.attatchment3 = this.$refs.PDFFile3.files[0];
            if (!this.postData.attatchment3.type.includes('pdf')) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Selected file is not PDF';
                this.informModal = true;
                this.postData.attatchment3 = null;
            }
        },
        languageChanged(){
            if (this.type == 'days') {
                this.getAllMeals();
            }
            else if (this.type == 'weeks') {
                this.weeks = [null, null, null, null, null, null, null];
                this.getAllMealDays();
            }
            else if (this.type == 'plan') {
                this.plan = [];
                for (let index = 0; index < this.durationweeks; index++) {
                    this.plan.push(null);
                }
                this.getAllMealWeeks();
            }
        },
        filterMealsByLanguage() {
            this.allMeals = this.allMeals.filter((item) => item.language == this.postData.language);
        },
        build() {
            if (this.postData.name == null || this.postData.name == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please enter name for the meal day';
                this.informModal = true;
                return
            }
            if (this.postData.tags.length < 1) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please select at least one tag';
                this.informModal = true;
                return
            }
            if (this.type == 'days') {
                if (this.days[0].Breakfast == null && this.days[0].Dinner == null && this.days[0].Lunch == null && this.days[0].Snacks == null && this.days[0].Drink == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Please select atleast one meal for the day';
                    this.informModal = true;
                    return
                }
                if (this.days[0].Breakfast !== null) {
                    this.postData.breakfast = this.days[0].Breakfast.id;
                }
                else {
                    this.postData.breakfast = this.days[0].Breakfast;
                }
                if (this.days[0].Lunch !== null) {
                    this.postData.lunch = this.days[0].Lunch.id;
                }
                else {
                    this.postData.lunch = this.days[0].Lunch;
                }
                if (this.days[0].Dinner !== null) {
                    this.postData.dinner = this.days[0].Dinner.id;
                }
                else {
                    this.postData.dinner = this.days[0].Dinner;
                }
                if (this.days[0].Snacks !== null) {
                    this.postData.snacks = this.days[0].Snacks.id;
                }
                else {
                    this.postData.snacks = this.days[0].Snacks;
                }
                if (this.days[0].Drink !== null) {
                    this.postData.drinks = this.days[0].Drink.id;
                }
                else {
                    this.postData.drinks = this.days[0].Drink;
                }
                this.pageLoading = true;
                this.loaderText = 'Creating Meal day';
                axios.post(config.baseApiUrl + 'create-meal-day', this.postData, this.apiConfig)
                    .then((res) => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            this.$parent.getAllMealDays();
                            this.quitComponent();
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
                for (let index = 0; index < this.weeks.length; index++) {
                    if (this.weeks[index] == null) {
                        this.modalTitle = 'Error';
                        this.modalDetail = 'Enter data for each day';
                        this.informModal = true;
                        return
                    }
                }
                if (this.weeks[0] !== null) {
                    this.postData.meal_day1 = this.weeks[0].id;
                }
                if (this.weeks[1] !== null) {
                    this.postData.meal_day2 = this.weeks[1].id;
                }
                if (this.weeks[2] !== null) {
                    this.postData.meal_day3 = this.weeks[2].id;
                }
                if (this.weeks[3] !== null) {
                    this.postData.meal_day4 = this.weeks[3].id;
                }
                if (this.weeks[4] !== null) {
                    this.postData.meal_day5 = this.weeks[4].id;
                }
                if (this.weeks[5] !== null) {
                    this.postData.meal_day6 = this.weeks[5].id;
                }
                if (this.weeks[6] !== null) {
                    this.postData.meal_day7 = this.weeks[6].id;
                }
                this.pageLoading = true;
                this.loaderText = 'Creating Meal Week';
                axios.post(config.baseApiUrl + 'create-meal-week', this.postData, this.apiConfig)
                    .then((res) => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            this.$parent.getAllMealWeeks();
                            this.quitComponent();
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
                for (let index = 0; index < this.plan.length; index++) {
                    if (this.plan[index] == null) {
                        this.modalTitle = 'Error';
                        this.modalDetail = 'Enter data for each Week';
                        this.informModal = true;
                        return
                    }
                }
                this.postData.week_data = [];
                for (let index = 0; index < this.plan.length; index++) {
                    this.postData.week_data.push(this.plan[index].id)
                }
                this.postData.duration = parseInt(this.durationweeks);
                let fd = new FormData();
                fd.append('name', this.postData.name);
                fd.append('duration', this.postData.duration);
                fd.append('description', this.postData.description);
                fd.append('language', this.postData.language);
                fd.append('week_data', JSON.stringify(this.postData.week_data));
                fd.append('tags', JSON.stringify(this.postData.tags));
                if(this.postData.image!=null)
                fd.append('image', this.postData.image);
                fd.append('attatchment', this.postData.attatchment);
                if(this.postData.attatchment2!=null)
                fd.append('attatchment2', this.postData.attatchment2);
                if(this.postData.attatchment3!=null)
                fd.append('attatchment3', this.postData.attatchment3);
                this.pageLoading = true;
                this.loaderText = 'Creating Meal Plan';
                axios.post(config.baseApiUrl + 'create-meal-plan', fd, this.apiConfig2)
                    .then((res) => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            this.$parent.getAllMealPlans();
                            this.quitComponent();
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
        removeFromDay() {
            for (let index = 0; index < this.selectedItems.length; index++) {
                if (this.type == 'days') {
                    let temp = this.selectedItems[index].split(" ");
                    let index1 = temp[0];
                    if (temp[1] == 'Breakfast') {
                        this.days[index1].Breakfast = null;
                    }
                    if (temp[1] == 'Lunch') {
                        this.days[index1].Lunch = null;
                    }
                    if (temp[1] == 'Dinner') {
                        this.days[index1].Dinner = null;
                    }
                    if (temp[1] == 'Snacks') {
                        this.days[index1].Snacks = null;
                    }
                    if (temp[1] == 'Drink') {
                        this.days[index1].Drink = null;
                    }
                }
                else if (this.type == 'weeks') {
                    this.weeks[this.selectedItems[index]] = null;
                }
                else if (this.type == 'plan') {
                    this.plan[this.selectedItems[index]] = null;
                }
            }
            this.selectedItems = [];
        },
        assignTagsShow() {
            this.showTags = !this.showTags;
        },
        assignTags(tags) {
            this.showTags = false;
            this.selectedTags = [];
            this.postData.tags = [];
            tags.forEach(element => {
                this.selectedTags.push(element.tagName);
                this.postData.tags.push(element.tagId);
            });
        },
        mealPlanDays() {
            if (this.plan.length < this.durationweeks) {
                let rep = (this.durationweeks - this.plan.length);
                for (let index = 0; index < rep; index++) {
                    this.plan.splice(this.plan.length, 0, null)
                }
            }
            else {
                let rep = this.plan.length - this.durationweeks;
                for (let index = 0; index < rep; index++) {
                    this.plan.pop();
                }
            }
        },
        getAllMeals() {
            this.allMeals = [];
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meals", this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.allMeals = res.data.data;
                        this.pageLoading = false;
                        this.filterMealsByLanguage();
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
        getAllMealDays() {
            this.allMeals = [];
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meal-days?lang="+this.postData.language, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.allMeals = res.data.data;
                        this.pageLoading = false;
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
        getAllMealWeeks() {
            this.allMeals = [];
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meal-weeks?lang="+this.postData.language, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.allMeals = res.data.data;
                        this.pageLoading = false;
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
        startDrag(item) {
            this.tempItem = item;
        },
        async onDrop(list, m) {
            if (this.type == 'days') {
                if (m == 'Breakfast') {
                    this.days[list].Breakfast = this.tempItem;
                }
                if (m == 'Lunch') {
                    this.days[list].Lunch = this.tempItem;
                }
                if (m == 'Dinner') {
                    this.days[list].Dinner = this.tempItem;
                }
                if (m == 'Snacks') {
                    this.days[list].Snacks = this.tempItem;
                }
                if (m == 'Drink') {
                    this.days[list].Drink = this.tempItem;
                }
            }
            else if (this.type == 'weeks') {
                this.weeks[list] = this.tempItem;
            }
            else if (this.type == 'plan') {
                this.plan[list] = this.tempItem;
            }
        },
        quitComponent() {
            this.$parent.showCreateMealPlanPopup();
        }
    }

}
</script>
<style scoped>
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

.drag-el {
    background-color: #fff;
    margin-bottom: 10px;
    padding: 5px;
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
</style>
