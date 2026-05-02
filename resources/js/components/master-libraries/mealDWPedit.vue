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
                    <input v-model="DWPdetails.name" class="brds-2 p-2 float-start me-4" style="border:1px solid #c5c5c5;color:#a69e9e;" type="text" placeholder="Enter name">
                    <p class="mb-0 me-3 py-2 float-start">Language:</p>
                    <select v-model="DWPdetails.language" @change="languageChanged()" class="brds-2 p-2" style="border:1px solid #c5c5c5;color:#a69e9e;">
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                    </select>
                </div>
                <div>
                    <p v-if="type=='plan'" class="mb-0 pt-2 mx-1 float-start">Select Weeks: </p>
                    <select v-if="type=='plan'" @change="durationChanged()" v-model="DWPdetails.duration" class="brds-2 p-2 mx-1 float-start" style="border:1px solid #c5c5c5;color:#a69e9e">
                        <option value="1">1 Week</option>
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
                    <button @click="validate()" class="prim_btn px-5 text-white float-start" style="border-radius:10px">Update</button>
                </div>
            </div>
            <div class="col-12 d-flex position-relative justify-content-around" style="min-height:500px;">
                <div class="col-7 px-2 pt-2 h-100 position-relative">
                    <h2 v-if="type=='plan'"><strong>Files</strong></h2>
                    <div v-if="type=='plan'">
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 1: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="DWPdetails.attatchment==null" class="mb-0">Select a PDF File</p>
                                <p v-else class="mb-0">{{DWPdetails.attatchment_name}}</p>
                                <input type="file" @change="getFile(1)" ref="PDFFile" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 2: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="DWPdetails.attatchment2==null" class="mb-0">Select a PDF File (optional)</p>
                                <p v-else class="mb-0">{{DWPdetails.attatchment2_name}}</p>
                                <input type="file" @change="getFile(2)" ref="PDFFile2" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                        <div class="row px-2 mb-2">
                            <div class="col-2">
                                <p class="mb-0 mt-2">File 3: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <p v-if="DWPdetails.attatchment3==null" class="mb-0">Select a PDF File (optional)</p>
                                <p v-else class="mb-0">{{DWPdetails.attatchment3_name}}</p>
                                <input type="file" @change="getFile(3)" ref="PDFFile3" accept=".pdf" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
                    </div>
                    <h2 class="mt-2"><strong>Description</strong></h2>
                    <textarea v-model="DWPdetails.description" class="col-12 mt-1 mx-auto tsl border-0 brds-2 px-2" style="height:70px" placeholder="Enter description"></textarea>
                    <div class="col-12 mt-2" style="height:40px;">
                        <h2 class="mb-0 float-start"><strong>Meals</strong></h2>
                        <button @click="removeItem()" class="scnd_btn brds-2 py-1 mt-1 float-end">Remove</button>
                    </div>
                    <div class="col-12 px-2 pt-2 mt-2" style="overflow-y:auto;height:435px;">
                        <div v-if="type=='days'" class="col-12 tsl my-3 brds-2 p-2 ">
                            <div class="col-12 d-flex justify-content-around mt-2">
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('breakfast')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.breakfast==null">Drag and Drop <strong>Breakfast</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="breakfast" v-model="selectedItems">
                                        <img v-if="DWPdetails.breakfast_detail.file_type=='image'" :src="DWPdetails.breakfast_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.breakfast_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.breakfast_detail.name" style="word-break:break-all;">{{this.truncatedString(DWPdetails.breakfast_detail.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('lunch')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.lunch==null">Drag and Drop <strong>Lunch</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="lunch" v-model="selectedItems">
                                        <img v-if="DWPdetails.lunch_detail.file_type=='image'" :src="DWPdetails.lunch_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.lunch_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.lunch_detail.name" style="word-break:break-all;">{{this.truncatedString(DWPdetails.lunch_detail.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('dinner')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.dinner==null">Drag and Drop <strong>Dinner</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="dinner" v-model="selectedItems">
                                        <img v-if="DWPdetails.dinner_detail.file_type=='image'" :src="DWPdetails.dinner_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.dinner_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.dinner_detail.name" style="word-break:break-all;">{{this.truncatedString(DWPdetails.dinner_detail.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('snacks')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.snacks==null">Drag and Drop <strong>Snacks</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="snacks" v-model="selectedItems">
                                        <img v-if="DWPdetails.snacks_detail.file_type=='image'" :src="DWPdetails.snacks_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.snacks_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.snacks_detail.name" style="word-break:break-all;">{{this.truncatedString(DWPdetails.snacks_detail.name,15)}}</p>
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('drinks')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.drinks==null">Drag and Drop <strong>Drink</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="drinks" v-model="selectedItems">
                                        <img v-if="DWPdetails.drinks_detail.file_type=='image'" :src="DWPdetails.drinks_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.drinks_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.drinks_detail.name" style="word-break:break-all;">{{this.truncatedString(DWPdetails.drinks_detail.name,15)}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='weeks'" class="w-100 p-2 d-flex flex-wrap justify-content-between">
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day1</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day1')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day1==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day1" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day1_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day1_detail.name">{{this.truncatedString(DWPdetails.meal_day1_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day2</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day2')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day2==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day2" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day2_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day2_detail.name">{{this.truncatedString(DWPdetails.meal_day2_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day3</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day3')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day3==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day3" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day3_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day3_detail.name">{{this.truncatedString(DWPdetails.meal_day3_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day4</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day4')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day4==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day4" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day4_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day4_detail.name">{{this.truncatedString(DWPdetails.meal_day4_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day5</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day5')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day5==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day5" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day5_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day5_detail.name">{{this.truncatedString(DWPdetails.meal_day5_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day6</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day6')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day6==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day6" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day6_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day6_detail.name">{{this.truncatedString(DWPdetails.meal_day6_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day7</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" @drop="onDrop('meal_day7')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day7==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day7" v-model="selectedItems">
                                            <img  :src="DWPdetails.meal_day7_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="DWPdetails.meal_day7_detail.name">{{this.truncatedString(DWPdetails.meal_day7_detail.name,15)}}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='plan'" v-for="(item, index) in DWPdetails.week_detail" class="col-4 float-start my-3 p-2">
                            <div class="tsl brds-2 py-2 px-3">
                                <strong> Week{{index+1}}</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center" :key="index" @drop="onDrop(index)" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="item==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" :value="index" v-model="selectedItems">
                                            <img :src="item.image" alt="" style="height:80px;width:80%; object-fit: contain; background: white;">
                                            <p class="mb-0 mt-2" data-toggle="tooltip" :title="item.name">{{this.truncatedString(item.name,20)}}</p>
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
                            <span v-for="tag in selectedTags" class="px-2 py-1 prim_bg mx-2 brds-1 my-1">{{tag.tagName}}</span>
                            <button class="scnd_btn px-4 py-1 brds-2 my-1 mx-2" @click="assignTagsShow">Add/Remove</button>
                        </div>
                    </div>
                    <div class="shd_card heavy_shd my-2 p-md-3 p-1">
                        <div class="d-flex justify-content-between brds-1 gray_bg p-3">
                            <div class="position-relative w-100">
                                <input type="text" class="w-100 exSearch" placeholder="search by name or tag" v-model="search">
                                <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
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
                                <div v-for="(item, index) in filteredMeals" :key="item.name"  class="col-xl-4 col-md-4 col-sm-6 col-12 mt-3">
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
    <assignTags v-if="showTags" tagType="meal" :prefilledTags="DWPdetails.tags"/>
</template>
<script>
import config from "../../config";
import axios from "axios";
import assignTags from '../clients/assignTags.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
export default {
    components: { Loader, Inform, assignTags },
    props: ['type','DWPdetails'],
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
                attatchment: null,
                attatchment2: null,
                attatchment3: null,
                language: 'en'
            },
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
            durationweeks: '1'
        }
    },
    computed: {
        filteredMeals() {
            return this.allMeals.filter((item) => {
                if (item.name.toLowerCase().includes(this.search.toLowerCase())) {
                    return item
                }
                else {
                    for (let index = 0; index < item.tagNames.length; index++) {
                        if (item.tagNames[index].toLowerCase().includes(this.search.toLowerCase())) {
                            return item
                        }
                    }

                }
            });
        },
    },
    mounted() {
        for (let x = 0; x < this.DWPdetails.tags.length; x++) {
            let tempTag = {
                tagId : this.DWPdetails.tags[x],
                tagName : this.DWPdetails.tagNames[x]
            }
            this.selectedTags.push(tempTag);
        }
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
        getFile(n) {
            let tempFile;
            let fileRef;
            let attatchmentVar;
            if(n===1){
                fileRef = 'PDFFile';
                attatchmentVar = 'attatchment';
            } else if(n===2) {
                fileRef = 'PDFFile2';
                attatchmentVar = 'attatchment2';
            } else {
                fileRef = 'PDFFile3';
                attatchmentVar = 'attatchment3';
            }
            tempFile = this.$refs[fileRef].files[0];
            if(tempFile!=null){
                if(!(tempFile.type.includes("pdf") || tempFile.type.includes("PDF"))){
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Selected file is not PDF';
                    this.informModal = true;
                    return;
                }
                this.DWPdetails[attatchmentVar] = tempFile;
                this.DWPdetails[attatchmentVar+'_name'] = tempFile.name;
            }
        },
        languageChanged(){
            if (this.type == 'days') {
                this.getAllMeals();
            }
            else if (this.type == 'weeks') {
                this.DWPdetails.meal_day1 = this.DWPdetails.meal_day1_detail = this.DWPdetails.meal_day2 = this.DWPdetails.meal_day2_detail =
                this.DWPdetails.meal_day3 = this.DWPdetails.meal_day3_detail = this.DWPdetails.meal_day4 = this.DWPdetails.meal_day4_detail =
                this.DWPdetails.meal_day5 = this.DWPdetails.meal_day5_detail = this.DWPdetails.meal_day6 = this.DWPdetails.meal_day6_detail =
                this.DWPdetails.meal_day7 = this.DWPdetails.meal_day7_detail = null; 
                this.selectedItems = [];
                this.getAllMealDays();
            }
            else if (this.type == 'plan') {
                for (let k = 0; k < this.DWPdetails.week_detail.length; k++) {
                    this.DWPdetails.week_detail[k] = null;
                }
                this.getAllMealWeeks();
            }
        },
        filterMealsByLanguage() {
            this.allMeals = this.allMeals.filter((item) => item.language == this.DWPdetails.language);
        },
        validate() {
            if (this.DWPdetails.name == null || this.DWPdetails.name == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please enter name';
                this.informModal = true;
                return;
            }
            if (this.DWPdetails.tags.length < 1) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please select at least one tag';
                this.informModal = true;
                return;
            }
            if (this.DWPdetails.description == null || this.DWPdetails.description == "") {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please write description';
                this.informModal = true;
                return;
            }
            if (this.type == 'days') {
                if (this.DWPdetails.breakfast == null && this.DWPdetails.dinner == null && this.DWPdetails.lunch == null 
                && this.DWPdetails.snacks == null && this.DWPdetails.drinks == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Please select atleast one meal for the day';
                    this.informModal = true;
                    return;
                }
                this.updateDWP(this.DWPdetails,this.apiConfig);
            }
            else if (this.type == 'weeks') {
                if (this.DWPdetails.meal_day1 == null || this.DWPdetails.meal_day2 == null || this.DWPdetails.meal_day3 == null ||
                this.DWPdetails.meal_day4 == null || this.DWPdetails.meal_day5 == null || this.DWPdetails.meal_day6 == null || 
                this.DWPdetails.meal_day7 == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'please fill all meals';
                    this.informModal = true;
                    return;
                }
                let tempObj = JSON.parse(JSON.stringify(this.DWPdetails));
                delete tempObj.meal_day1_detail;
                delete tempObj.meal_day2_detail;
                delete tempObj.meal_day3_detail;
                delete tempObj.meal_day4_detail;
                delete tempObj.meal_day5_detail;
                delete tempObj.meal_day6_detail;
                delete tempObj.meal_day7_detail;
                this.updateDWP(tempObj,this.apiConfig);    
            }
            else if (this.type == 'plan') {
                for (let index = 0; index < this.DWPdetails.week_detail.length; index++) {
                    if (this.DWPdetails.week_detail[index] == null) {
                        this.modalTitle = 'Error';
                        this.modalDetail = 'Enter data for each Week';
                        this.informModal = true;
                        return;
                    }
                }
                let fd = new FormData();
                fd.append('id', this.DWPdetails.id);
                fd.append('name', this.DWPdetails.name);
                fd.append('duration', this.DWPdetails.duration);
                fd.append('description', this.DWPdetails.description);
                fd.append('language', this.DWPdetails.language);
                fd.append('week_data', JSON.stringify(this.DWPdetails.week_detail));
                fd.append('tags', JSON.stringify(this.postData.tags));
                if(typeof this.DWPdetails.attatchment == 'object' && this.DWPdetails.attatchment !=null)
                fd.append('attatchment', this.DWPdetails.attatchment);
                if(typeof this.DWPdetails.attatchment2 == 'object' && this.DWPdetails.attatchment2 !=null)
                fd.append('attatchment2', this.DWPdetails.attatchment2);
                if(typeof this.DWPdetails.attatchment3 == 'object' && this.DWPdetails.attatchment3 !=null)
                fd.append('attatchment3', this.DWPdetails.attatchment3);
                this.updateDWP(fd,this.apiConfig2);
            }
        },
        updateDWP(paylaod,hdrs){
            let url;
            if(this.type==='days')
            url = 'update-meal-day';
            else if(this.type == 'weeks')
            url = 'update-meal-week';
            else 
            url = 'update-meal-plan';
            this.pageLoading = true;
            axios.post(config.baseApiUrl+url,paylaod,hdrs).then(res => {
                this.pageLoading = false;
                if(res.data.status){
                    this.$parent.editDWP = false;
                    this.$parent.mealDWP = null;
                    this.$parent.selectedItems = false;
                    if(this.type==='days')
                    this.$parent.getAllMealDays();
                    else if(this.type == 'weeks')
                    this.$parent.getAllMealWeeks();
                    else
                    this.$parent.getAllMealPlans();
                } else {
                    this.modalTitle = 'Failed';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("update dwp error: ",res.data.error);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
                console.log("update dwp error: ",er.message);
            });
        },
        acknowledged() {
            this.informModal = false;
        },
        removeItem() {
            for (let i = 0; i < this.selectedItems.length; i++) {
                const element = this.selectedItems[i];
                if(this.type==="days"){
                    this.DWPdetails[element] = null;
                    this.DWPdetails[element+'_details'] = null;
                } else if(this.type==="weeks"){
                    this.DWPdetails[element] = null;
                    this.DWPdetails[element+'_detail'] = null;
                } else {
                    this.DWPdetails.week_detail[element] = null;
                }
            }
            this.selectedItems = [];
        },
        assignTagsShow() {
            this.showTags = !this.showTags;
        },
        assignTags(tags) {
            this.showTags = false;
            this.selectedTags = tags;
            let tempTags = [];
            tags.forEach(tag => {
                tempTags.push(tag.tagId);
            });
            this.DWPdetails.tags = tempTags;
        },
        durationChanged() {
            console.log(this.DWPdetails);
            if (this.DWPdetails.week_detail.length < this.DWPdetails.duration) {
                let rep = (this.DWPdetails.duration - this.DWPdetails.week_detail.length);
                for (let index = 0; index < rep; index++) {
                    this.DWPdetails.week_detail.push(null);
                }
            }
            else {
                let rep = this.DWPdetails.week_detail.length - this.DWPdetails.duration;
                for (let index = 0; index < rep; index++) {
                    this.DWPdetails.week_detail.pop();
                }
            }
        },
        getAllMeals() {
            this.allMeals = [];
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + "get-meals?lang="+this.DWPdetails.language, this.apiConfig)
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
        async onDrop(meal) {
            if (this.type == 'days') {
                let tempObj = {
                    "file" : this.tempItem.file,
                    "file_type" : this.tempItem.file_type,
                    "name" : this.tempItem.name,
                    "video_thumbnail" : this.tempItem.video_thumbnail,
                }
                this.DWPdetails[meal] = this.tempItem.id;
                this.DWPdetails[meal+'_detail'] = tempObj;
            }
            else if (this.type == 'weeks') {
                this.DWPdetails[meal] = this.tempItem.id;
                this.DWPdetails[meal+'_detail'] = this.tempItem;
            }
            else if (this.type == 'plan') {
                this.DWPdetails.week_detail[meal] = this.tempItem;
            }
        },
        quitComponent() {
            this.$parent.editDWP = false;
            this.$parent.DWPdetails = null;
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
</style>
