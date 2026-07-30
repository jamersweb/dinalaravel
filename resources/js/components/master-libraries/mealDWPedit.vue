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
                                <p class="mb-0 mt-2">Thumbnail: </p>
                            </div>
                            <div class="col-10 tsl brds-2 p-2 d-flex align-items-center position-relative">
                                <img v-if="thumbnailPreview || DWPdetails.image" :src="thumbnailPreview || DWPdetails.image" class="me-2" alt="Meal plan thumbnail preview" style="height:54px;width:85px;object-fit:contain;background:white;">
                                <p v-if="DWPdetails.image_file==null" class="mb-0">Select an image file (optional)</p>
                                <p v-else class="mb-0">{{DWPdetails.image_file.name}}</p>
                                <input type="file" @change="getImage" ref="thumbnailFile" accept="image/*" style="position:absolute;height:100%;width:100%;top:0;left:0;opacity:0;">
                            </div>
                        </div>
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
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('lunch')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.lunch==null">Drag and Drop <strong>Lunch</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="lunch" v-model="selectedItems">
                                        <img v-if="DWPdetails.lunch_detail.file_type=='image'" :src="DWPdetails.lunch_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.lunch_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('dinner')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.dinner==null">Drag and Drop <strong>Dinner</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="dinner" v-model="selectedItems">
                                        <img v-if="DWPdetails.dinner_detail.file_type=='image'" :src="DWPdetails.dinner_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.dinner_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('snacks')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.snacks==null">Drag and Drop <strong>Snacks</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="snacks" v-model="selectedItems">
                                        <img v-if="DWPdetails.snacks_detail.file_type=='image'" :src="DWPdetails.snacks_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.snacks_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                    </div>
                                </div>
                                <div class="col-2 brds-2 p-2 text-center" @drop="onDrop('drinks')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                    <p class="mb-0" v-if="DWPdetails.drinks==null">Drag and Drop <strong>Drink</strong> for the day</p>
                                    <div v-else class="position-relative">
                                        <input type="checkbox" class="form-check-input position-absolute" value="drinks" v-model="selectedItems">
                                        <img v-if="DWPdetails.drinks_detail.file_type=='image'" :src="DWPdetails.drinks_detail.file" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                        <img v-else :src="DWPdetails.drinks_detail.video_thumbnail" alt="" style="height:80px;width:100%; object-fit: contain; background: white;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='weeks'" class="w-100 p-2 d-flex flex-wrap justify-content-between">
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day1</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day1!==null && showMealDayPreview(DWPdetails.meal_day1)" @drop="onDrop('meal_day1')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day1==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day1" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day1_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day2</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day2!==null && showMealDayPreview(DWPdetails.meal_day2)" @drop="onDrop('meal_day2')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day2==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day2" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day2_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day3</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day3!==null && showMealDayPreview(DWPdetails.meal_day3)" @drop="onDrop('meal_day3')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day3==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day3" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day3_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day4</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day4!==null && showMealDayPreview(DWPdetails.meal_day4)" @drop="onDrop('meal_day4')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day4==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day4" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day4_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day5</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day5!==null && showMealDayPreview(DWPdetails.meal_day5)" @drop="onDrop('meal_day5')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day5==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day5" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day5_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day6</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day6!==null && showMealDayPreview(DWPdetails.meal_day6)" @drop="onDrop('meal_day6')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day6==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day6" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day6_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tsl brds-2 py-2 px-3 my-2" style="width:200px">
                                <strong> Day7</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" @click="DWPdetails.meal_day7!==null && showMealDayPreview(DWPdetails.meal_day7)" @drop="onDrop('meal_day7')" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="DWPdetails.meal_day7==null">Drag and Drop Meal for the day</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" style="left:0" value="meal_day7" v-model="selectedItems" @click.stop>
                                            <img  :src="DWPdetails.meal_day7_detail.image" style="max-width: 100%; height:105px; object-fit: contain; background: white;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else-if="type=='plan'" v-for="(item, index) in DWPdetails.week_detail" class="col-4 float-start my-3 p-2">
                            <div class="tsl brds-2 py-2 px-3">
                                <strong> Week{{index+1}}</strong>
                                <div class="col-12 d-flex justify-content-around mt-2">
                                    <div class="col-12 brds-2 p-2 text-center meal-preview-row" :key="index" @click="item!==null && showMealWeekPreview(item.id)" @drop="onDrop(index)" @dragover.prevent @dragenter.prevent style="border:1px solid #c5c5c5;height:150px;">
                                        <p class="mb-0" v-if="item==null">Drag and Drop Week here</p>
                                        <div v-else class="position-relative text-center">
                                            <input type="checkbox" class="form-check-input position-absolute" :value="index" v-model="selectedItems" @click.stop>
                                            <img :src="item.image" alt="" style="height:80px;width:80%; object-fit: contain; background: white;">
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
                                    <div class="shd_card p-0 h-100 text-center drag-el" draggable="true" @dragstart="startDrag(item)" @click="addLibraryItem(item)" style="width:100%;cursor:pointer">
                                        <div v-if="type=='days'" class="p-2">
                                            <img v-if="item.file_type=='image'" :src="item.file" alt="" style="height:80px;width:100%;">
                                            <img v-else :src="item.video_thumbnail" alt="" style="height:80px;width:100%;">
                                        </div>
                                        <div v-else class="p-2">
                                            <img :src="item.image" alt="" style="height:80px;width:100%;">
                                        </div>
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
    <div v-if="selectedMealDetail" class="meal-preview-overlay meal-preview-overlay-meal" @click.self="closeMealPreview">
        <div class="meal-preview-box position-relative p-3">
            <button class="trans_btn position-absolute" @click="closeMealPreview" style="right:18px;top:12px;font-size:25px">
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
                    <p class="mb-0 text-muted">Preparation: {{selectedMealDetail.prep_time}} minutes<span v-if="hasCookTime(selectedMealDetail)"> / Cooking: {{selectedMealDetail.cook_time}} minutes</span></p>
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
    <div v-if="selectedMealDayDetail" class="meal-preview-overlay meal-preview-overlay-day" @click.self="closeMealDayPreview">
        <div class="meal-preview-box position-relative p-3">
            <button class="trans_btn position-absolute" @click="closeMealDayPreview" style="right:18px;top:12px;font-size:25px">
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
                    <div class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-preview-row" @click="showMealPreview(slot.id)">
                        <img v-if="slot.detail.file_type=='image'" :src="slot.detail.file" alt="" class="img-fluid" style="max-width:100px">
                        <img v-else :src="slot.detail.video_thumbnail" alt="" class="img-fluid" style="max-width:100px">
                        <p class="ms-3 mb-0" style="align-self: center;">{{slot.detail.name}}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
    <div v-if="selectedMealWeekDetail" class="meal-preview-overlay meal-preview-overlay-week" @click.self="closeMealWeekPreview">
        <div class="meal-preview-box position-relative p-3">
            <button class="trans_btn position-absolute" @click="closeMealWeekPreview" style="right:18px;top:12px;font-size:25px">
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
                    <div class="float-start d-flex shd_card w-100 mt-1 mb-0 py-2 meal-preview-row" @click="showMealDayPreview(slot.id)">
                        <img :src="slot.detail.image" alt="" class="img-fluid" style="max-width:100px">
                        <p class="ms-3 mb-0" style="align-self: center;">{{slot.detail.name}}</p>
                    </div>
                </template>
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
            durationweeks: '1',
            thumbnailPreview: null,
            selectedMealDetail: null,
            selectedMealIngredients: [],
            selectedMealDirections: [],
            selectedMealTags: [],
            selectedMealDayDetail: null,
            selectedMealDayTags: [],
            selectedMealWeekDetail: null,
            selectedMealWeekTags: []
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
            this.normalizePlanWeeks();
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
        hasCookTime(meal) {
            const value = meal?.cook_time;
            return value !== null && value !== undefined && value !== '' && value !== 0 && value !== '0';
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
        showMealPreview(mealId) {
            if (!mealId) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal';
            axios.get(config.baseApiUrl + 'get-meal-detail/' + mealId, this.apiConfig)
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
        showMealDayPreview(mealDayId) {
            if (!mealDayId) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal Day';
            axios.get(config.baseApiUrl + 'get-meal-day-detail/' + mealDayId, this.apiConfig)
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
        showMealWeekPreview(mealWeekId) {
            if (!mealWeekId) {
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Fetching Meal Week';
            axios.get(config.baseApiUrl + 'get-meal-week-detail/' + mealWeekId, this.apiConfig)
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
        closeMealPreview() {
            this.selectedMealDetail = null;
            this.selectedMealIngredients = [];
            this.selectedMealDirections = [];
            this.selectedMealTags = [];
        },
        closeMealDayPreview() {
            this.selectedMealDayDetail = null;
            this.selectedMealDayTags = [];
        },
        closeMealWeekPreview() {
            this.selectedMealWeekDetail = null;
            this.selectedMealWeekTags = [];
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
                this.DWPdetails.image_file = null;
                this.thumbnailPreview = null;
                return;
            }
            this.DWPdetails.image_file = tempFile;
            this.thumbnailPreview = URL.createObjectURL(tempFile);
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
                let fd = new FormData();
                fd.append('id', this.DWPdetails.id);
                fd.append('name', this.DWPdetails.name);
                fd.append('duration', this.DWPdetails.duration);
                fd.append('description', this.DWPdetails.description || '');
                fd.append('language', this.DWPdetails.language);
                fd.append('week_data', JSON.stringify(this.DWPdetails.week_detail.filter((item) => item != null)));
                fd.append('tags', JSON.stringify(this.DWPdetails.tags));
                if(typeof this.DWPdetails.image_file == 'object' && this.DWPdetails.image_file !=null)
                fd.append('image', this.DWPdetails.image_file);
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
        normalizePlanWeeks() {
            if (!Array.isArray(this.DWPdetails.week_detail)) {
                this.DWPdetails.week_detail = [];
            }

            const duration = parseInt(this.DWPdetails.duration || this.DWPdetails.week_detail.length || 1);
            this.DWPdetails.duration = duration;

            while (this.DWPdetails.week_detail.length < duration) {
                this.DWPdetails.week_detail.push(null);
            }

            while (this.DWPdetails.week_detail.length > duration) {
                this.DWPdetails.week_detail.pop();
            }
        },
        durationChanged() {
            this.normalizePlanWeeks();
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
            axios.get(config.baseApiUrl + "get-meal-days?lang="+this.DWPdetails.language, this.apiConfig)
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
            axios.get(config.baseApiUrl + "get-meal-weeks?lang="+this.DWPdetails.language, this.apiConfig)
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
        addLibraryItem(item) {
            this.tempItem = item;
            if (this.type == 'plan') {
                this.normalizePlanWeeks();
                const emptyIndex = this.DWPdetails.week_detail.findIndex((week) => week == null);
                if (emptyIndex !== -1) {
                    this.onDrop(emptyIndex);
                }
                return;
            }
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

.meal-preview-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.meal-preview-overlay-week {
    z-index: 1080;
}

.meal-preview-overlay-day {
    z-index: 1090;
}

.meal-preview-overlay-meal {
    z-index: 1100;
}

.meal-preview-box {
    background: white;
    border-radius: 20px;
    width: min(1000px, 92vw);
    max-height: 88vh;
    overflow-y: auto;
}

.meal-preview-row {
    cursor: pointer;
}

.meal-preview-row:hover {
    transform: translateY(-1px);
}

.detail-meta-box {
    min-height: 90px;
    overflow-y: auto;
}
</style>
