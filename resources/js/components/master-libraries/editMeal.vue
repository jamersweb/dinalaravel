<template lang="">
    <div class="my-popup-component">
        <Loader v-if="pageLoading" loadingText="Uploading" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <div class="main">
            <h4 class="mt-3">Edit Meal</h4>
            <button class="trans_btn position-absolute" @click="quitComponent"
                    style="right:10px;top:5px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
            </button>
            <div style="width:95%;height:440px;margin-left:auto;margin-right:auto;">
                <div class="pt-5 div1">
                    <button @click="addDetailsShow()" :class="{active : mealDetails}" style="width:100%;height:50px;font-size:18px;font-weight:bold;border:none;background-color:transparent;">Meal Details</button>
                    <button @click="addRecipeShow()" :class="{active : recipe}" style="width:100%;height:50px;font-size:18px;font-weight:bold;border:none;background-color:transparent;">Recipe</button>
                    <div style="height:50px;margin-top:250px;">
                        <button class="btn4" v-if="addRecipe" @click="saveForm">Save</button>
                    </div>
                </div>
                <div class="div2" v-if="addDetails">
                    <p class="ms-2 my-2" style="font-size:13px;">Meal Name <span style="color:red;">*</span></p>
                    <input type="text" placeholder="Meal Name e.g" class="inp1" v-model="mealData.name">
                    <p class="ms-2 my-2" style="font-size:13px;">Language <span style="color:red;">*</span></p>
                    <select v-model="mealData.language" @change="getAllFoods()" style="white-space: nowrap; width: 100px">
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                    </select>
                    <p class="ms-2 my-2" style="font-size:13px;">Meal Prep Time </p>
                    <div class="col-12" style="height:50px">
                        <input type="number" placeholder="10" class="inp2" v-model="mealData.prep_time">
                        <!-- <select class="inp2" id="" v-model="mealData.prep_time" >
                            <option disabled selected >Choose from the list</option>
                            <option value="5">5 min</option>
                            <option value="10">10 min</option>
                            <option value="15">15 min</option>
                            <option value="20">20 min</option>
                            <option value="25">25 min</option>
                        </select> -->
                        <p style="font-size:13px;float:left;margin:5px;margin-top:11px;">Prep<span style="color:red;">*</span></p>
                        <input type="number" placeholder="10" class="inp2" v-model="mealData.cook_time">
                        <!-- <select class="inp2" id="" v-model="mealData.cook_time">
                            <option disabled selected >Choose from the list</option>
                            <option value="5">5 min</option>
                            <option value="10">10 min</option>
                            <option value="15">15 min</option>
                            <option value="20">20 min</option>
                            <option value="25">25 min</option>
                        </select> -->
                        <p style="font-size:13px;float:left;margin:5px;margin-top:11px;">Cook<span style="color:red;">*</span></p>
                    </div>
                    <div class="mt-3">
                        <h5 class="m-0">Suitable For <span class="text-danger">*</span></h5>
                        <div class="row w-100 mt-2">
                            <div class="col-3 mb-1" v-for="(item, index) in suitableArray" :key="index">
                                <input class="form-check-input tsl m-0" :id="'chkst'+index" type="checkbox" :value="item" v-model="mealData.suitable_for">
                                <label class="ms-2 d-inline wb-all pointer text-capitalize" :for="'chkst'+index">{{item}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5 class="m-0">Tags <span class="text-danger">*</span></h5>
                        <div class="row w-100 mt-2">
                            <div class="col-3 mb-1" v-for="(item, index) in tags" :key="index">
                                <input class="form-check-input tsl m-0" :id="'chktg'+index" type="checkbox" :value="item.id" v-model="mealData.tags">
                                <label class="ms-2 d-inline wb-all pointer text-capitalize" :for="'chktg'+index">{{item.name}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h5 class="m-0">Contains</h5>
                        <div class="row w-100 mt-2">
                            <div class="col-3 mb-1" v-for="(item, index) in containsArray" :key="index">
                                <input class="form-check-input tsl m-0" :id="'chkct'+index" type="checkbox" :value="item" v-model="mealData.contains">
                                <label class="ms-2 d-inline wb-all pointer text-capitalize" :for="'chkct'+index">{{item}}</label>
                            </div>
                        </div>
                    </div>
                    <div style="width:100%;text-align:center;">
                        <button class="btn1 mx-auto" @click="coverDiv">Next</button>
                    </div>
                </div>
                <div class="div2 position-relative" style="overflow-x:hidden;" v-if="addCover">
                    <div v-if="mealData.file==null" class="position-relative" style="width:80%;height:300px;border:1px solid #C5C5C5;margin-left:auto;margin-right:auto;margin-top:30px;border-radius:10px;text-align:center;padding-right:30px;;padding-left:30px;">
                        <p style="color:#F2A18C;font-size:23px;margin-bottom:0px;margin-top:10px;">Cover</p>
                        <p style="font-size:26px;margin-bottom:0px;">Drop Here to add </p>
                        <p style="font-size:14px;color:#B1B0B0;margin-bottom:0px;margin-top:10px;">or Select the file</p>
                        <input type="file" ref="selectedCover" @change="getCover" style="margin-left:0pxs;opacity:0;height:100%;width:100%;position:absolute;top:0px;left:0px;z-index:999999 !important;" accept=".jpg,.JPG,.png,.PNG,.jpeg,.JPEG,.mp4,.MP4,.mkv,.MKV">
                        <p style="font-size:14px;color:#B1B0B0;margin-bottom:0px;margin-top:10px;">.jpeg, .jpg files less than 5MB for photo
                            cover or .mp4, .mov files less than 500MB for video cover
                        </p>
                        <p style="font-size:14px;color:#B1B0B0;margin-bottom:0px;margin-top:10px;">
                            .mov files need more time to upload.
                            You can compress .mov files for faster
                            upload. Learn how.
                        </p>
                    </div>
                    <div v-else class="position-relative px-2 mx-auto text-center brds-4" style="width:80%;height:300px;border:1px solid #C5C5C5;">
                        <div class="w-100 h-100 py-2 px-3">
                            <img v-if="mealData.file_type==='image'" :src="imageURL" class="img-fluid brds-2 w-100 h-100" style="object-fit: contain; background: white;">
                            <video v-if="mealData.file_type==='video'" :src="videoURL" controls class="img-fluid brds-2 w-100 h-100"></video>
                        </div>
                    </div>
                    <div class="w-80 mx-auto" style="text-align:center;">
                        <button v-if="mealData.file!==null" class="prim_bg" @click="mealData.file=null" style="border:none;height:30px;width:90px;border-radius:10px;margin-top:10px;">Change</button>
                    </div>
                    <div style="text-align:center;width:100%;height:30px;bottom:10px;margin-top:15px;">
                        <button class="btn1" @click="showRecipe1">Save</button>
                    </div>
                </div>
                <div class="div2" v-if="addRecipe">
                    <div class="col-7 float-start" style="height:100%;overflow-y:auto;">
                        <div class="col-12 float-start" style="height:90%;" v-if="autoModeV">
                            <div class="col-12 pe-2 float-start">
                                <p style="font-size:13px;float:left;margin-left:10px;">Ingredients <span style="color:red;">*</span></p>
                                <button style="font-size:13px;color:#F2A18C;float:right;background-color:transparent;border:none;" @click="manualMode">Go to Manual mode</button>
                            </div>
                            <div class="dropdown" style="height:30px;border:1px solid #C5C5C5;float:left;width:95%;border-radius:7px;">
                                <img src="/cms-assets/images/navbar-topbar/search.png" alt="Error" style="height:15px;width:17px;float:left;margin-top:6px;margin-left:5px;">
                                <textarea v-model="searchFood" data-bs-toggle="dropdown" style="width:calc(100% - 24px);color:#C5C5C5;font-size:13px;border:none;background-color:transparent;text-align:start;">Choose Food to add</textarea>
                                    <ul class="dropdown-menu tsl" style="width:95%;height:150px;overflow-y:auto;">
                                        <li style="width:90%;height:50px;border-bottom:1px solid;margin-right:auto;margin-left:auto;color:#C5C5C5;padding:5px;" v-for="(items,index) in allFoods" :key="items" @click="addFood(items)">
                                            <div style="float:left;width:60%;">
                                                <p style="font-size:10px;font-weight:bold;margin:0px;">{{items.name}}</p>
                                                <p style="font-size:8px;font-weight:bold;margin:0px;">{{items.serving_size}} Serving</p>
                                            </div>
                                            <div style="float:right;width:20%;">
                                                <p style="font-size:8px;font-weight:bold;margin:0px;">{{items.calories}} Cal</p>
                                            </div>
                                        </li>
                                    </ul>
                            </div>
                            <div class="tsh " style="width:90%;height:200px;float:left;margin-top:20px;border-radius:30px;margin-left:10px;padding:5px;">
                                <div style="height:95%;width:100%;justify-content:center;align-items:center;text-align:center;padding-top:30px;" v-if="mealData.ingredients.length==0">
                                    <p style="font-size:18px;font-weight:bold;">No foods added yet</p>
                                    <p style="font-size:10px">Start adding foods to your custom recipe</p>
                                </div>
                                <div style="height:90%;width:100%;padding-top:10px;padding-bottom:10px;margin-bottom:10px;margin-top:10px;overflow-y:auto;" v-else>
                                    <div style="width:100%;height:70px;border-bottom:1px solid #707070;margin-left:auto;margin-right:auto;padding:5px;" v-for="(item,index) in mealData.ingredients" :key="index">
                                        <div class="col-12 float-start" style="height:30px">
                                            <p style="font-size:10px;width:80%;float:left;">{{item.name}}</p>
                                            <p style="font-size:9px;float:right;margin-top:10px;">{{item.calories}} cal</p>
                                        </div>
                                        <div class="col-12 float-start" style="height:20px">
                                            <select class="select1 float-start" style="width:auto;" v-model="mealData.ingredients[index].quantity1" @change="changeQuantity()">
                                                <option selected vlaue="0">0</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                                <option value="5">5</option>
                                            </select>
                                            <select class="select1 float-start" style="width:auto;" v-model="mealData.ingredients[index].quantity2" @change="changeQuantity()">
                                                <option selected value="0">0</option>
                                                <option value="0.25">1/4</option>
                                                <option value="0.33">1/3</option>
                                                <option value="0.5">1/2</option>
                                            </select>
                                            <!-- <select class="select1 float-start" disabled>
                                                <option>1</option>
                                            </select> -->
                                            <button class="btn5" @click="removeFood(index)"><img class="btn-img" src="/images/Group579661.png" alt="Error"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="width:95%;height:40px;float:left;margin-top:20px;">
                                <p style="font-size:13px;float:left;margin-left:10px;">Recipe Makes <span style="color:red;">*</span></p>
                                <input type="number" class="inp4" min="1" placeholder="e.g 1" v-model="mealData.no_of_servings">
                                <p style="font-size:10px;float:left;margin-left:5px;margin-top:5px">Servings</p>
                            </div>
                            <div style="width:95%;height:40px;float:left;margin-top:20px;">
                                <p style="font-size:13px;float:left;">Per Serving </p>
                                <div class="card1 tsl">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:10px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Protien</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{mealData.protein_per_serving.toFixed(2)}}</p>
                                </div>
                                <div class="card1 tsl">
                                    <img src="../../../../public/images/Group57578.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:10px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fat</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{mealData.fat_per_serving.toFixed(2)}}</p>
                                </div>
                                <div class="card1 tsl">
                                    <img src="../../../../public/images/Group57580.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:10px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Carbs</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{mealData.carbs_per_serving.toFixed(2)}}</p>
                                </div>
                                <div class="card1 tsl">
                                    <img src="../../../../public/images/Group57965.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:10px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Calories</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{mealData.calories_per_serving.toFixed(2)}}</p>
                                </div>
                                <div class="card1 tsl">
                                    <img src="../../../../public/images/Group2.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:10px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fiber</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{mealData.fiber_per_serving.toFixed(2)}}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 float-start" style="height:400px;" v-if="manualModeV">
                            <div class="col-12 pe-2 float-start">
                                <p style="font-size:13px;float:left;margin-left:10px;">Ingredients <span style="color:red;">*</span></p>
                                <button style="font-size:13px;width:50%;height:auto;float:right;border:none;background-color:transparent" @click="autoMode">Go to Auto-calculate mode</button>
                            </div>
                            <p style="font-size:13px;color:#C5C5C5;">Copy and paste your premade list of ingredients with serving sizes</p>
                            <textarea v-model="ingredientEntered" class="txt1" 
                                placeholder="Enter ingredients here.
                                Take care that each ingredient is in new row, e.g:
                                1 lbs boneless, skinless chicken breast
                                1 small zucchini, sliced into 1/2 inch coins
                                1 red bell pepper, cut into 1 inch pieces">
                            </textarea>
                            <div style="width:95%;height:25px;float:left;margin-top:10px;">
                                <p style="font-size:13px;float:left;margin-left:10px;">Recipe Makes <span style="color:red;">*</span></p>
                                <input type="number" v-model="mealData.serving_size" min="1" class="inp4" placeholder="e.g 1">
                                <p style="font-size:8px;float:left;margin-left:5px;margin-top:5px">Servings</p>
                            </div>
                            <p style="font-size:13px;float:left;margin-left:10px;margin-bottom:0px;">Macro-nutrient split per serving<span style="color:red;">*</span></p>
                            <div style="width:95%;height:70px;float:left;margin-top:10px;">
                                <div class="px-1 card2 tsl">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Protien</h6>
                                    <input class="inp5" v-model="mealData.protein_per_serving" type="number" min="1" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 card2 tsl">
                                    <img src="../../../../public/images/Group57578.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fat</h6>
                                    <input class="inp5" v-model="mealData.fat_per_serving" type="number" min="1" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 card2 tsl">
                                    <img src="../../../../public/images/Group57580.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Carbs</h6>
                                    <input class="inp5" v-model="mealData.carbs_per_serving" type="number" min="1" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 card2 tsl">
                                    <img src="../../../../public/images/Group57965.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Calories</h6>
                                    <input class="inp5" v-model="mealData.calories_per_serving" type="number" min="1" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 card2 tsl">
                                    <img src="../../../../public/images/Group57965.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fiber</h6>
                                    <input class="inp5" v-model="mealData.fiber_per_serving" type="number" min="1" placeholder="0g (0%)">
                                </div>
                            </div>
                            <p style="font-size:13px;float:left;margin-left:10px;margin-bottom:0px;">Macro-nutrient split per serving</p>
                            <p style="font-size:13px;color:#C5C5C5;float:left;margin-left:10px;">These are other nutritional information per serving of this meal.</p>
                            <div style="width:95%;height:200px;float:left;margin-top:0px;">
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">saturated_fat(g)</h6>
                                    <input v-model="mealData.nutrient.saturated_fat" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">trans_fat(g)</h6>
                                    <input v-model="mealData.nutrient.trans_fat" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">polyunsaturated_fat(g)</h6>
                                    <input v-model="mealData.nutrient.polyunsaturated_fat" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">monounsaturated_fat(g)</h6>
                                    <input v-model="mealData.nutrient.monounsaturated_fat" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">cholestrol(g)</h6>
                                    <input v-model="mealData.nutrient.cholestrol" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">sodium(g)</h6>
                                    <input v-model="mealData.nutrient.sodium" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">dietary_fiber(g)</h6>
                                    <input v-model="mealData.nutrient.dietary_fiber" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">total_sugars(g)</h6>
                                    <input v-model="mealData.nutrient.total_sugars" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_a(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_a" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_c(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_c" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_d(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_d" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_e(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_e" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">thiamin(g)</h6>
                                    <input v-model="mealData.nutrient.thiamin" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">riboflavin(g)</h6>
                                    <input v-model="mealData.nutrient.riboflavin" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">niacin(g)</h6>
                                    <input v-model="mealData.nutrient.niacin" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_b6(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_b6" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">vitamin_b12(g)</h6>
                                    <input v-model="mealData.nutrient.vitamin_b12" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">pantothenic_acid(g)</h6>
                                    <input v-model="mealData.nutrient.pantothenic_acid" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">calcium(g)</h6>
                                    <input v-model="mealData.nutrient.calcium" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">iron(g)</h6>
                                    <input v-model="mealData.nutrient.iron" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">potassium(g)</h6>
                                    <input v-model="mealData.nutrient.potassium" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">phosphorus(g)</h6>
                                    <input v-model="mealData.nutrient.phosphorus" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">magnesium(g)</h6>
                                    <input v-model="mealData.nutrient.magnesium" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">zinc(g)</h6>
                                    <input v-model="mealData.nutrient.zinc" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">selenium(g)</h6>
                                    <input v-model="mealData.nutrient.selenium" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">copper(g)</h6>
                                    <input v-model="mealData.nutrient.copper" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">menganese(g)</h6>
                                    <input v-model="mealData.nutrient.menganese" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">alchohal(g)</h6>
                                    <input v-model="mealData.nutrient.alchohal" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                                <div class="px-1 py-2 card3 tsl">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;word-break:break-all;">caffeine(g)</h6>
                                    <input v-model="mealData.nutrient.caffeine" class="inp5" type="number" style="position: absolute;bottom: 10px;left: 2px;" placeholder="0g (0%)">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-5 float-start p-2 h-100 tm-brdr brds-2 text-center">
                        <h5 style="font-size:13px;text-align:start;">Directions</h5>
                        <p style="font-size:10px;color:#B1B0B0;text-align:start;">Add directions for preparing this custom recipe</p>
                        <div class="col-12">
                            <textarea class="w-100 p-2 brds-1 h8 tm-brdr brds-1" placeholder="Enter details for meal preparation" 
                            v-model="directions" rows="3"></textarea>
                            <!-- <input class="inp3" type="text" placeholder="Enter details for meal preparation" v-model="directions"> -->
                            <!-- <button class="btn2" @click="directions=null"><img class="btn-img" src="../../../../public/images/Group579661.png" alt="Error"></button>
                            <button class="btn2"><img class="btn-img" src="../../../../public/images/Group57966.png" alt="Error"></button> -->
                        </div>
                        <button class="prim_btn h8 brds-1 py-1" @click="addDirection()">Add next step</button>
                        <div class="mt-3 px-2 overflow-auto text-start" style="max-height:190px">
                            <!-- <p style="word-break: break-all;" class="m-0 h7" v-for="(dir,index) in mealData.directions">{{index+1}}. {{dir}}</p> -->
                            <div v-for="(dir,index) in mealData.directions" class="d-flex justify-content-between">
                                <input type="text" class="brds-1 py-1 px-2 h8 tm-brdr w-80 my-1" v-model="mealData.directions[index]">
                                <button @click="removeDirection(index)" class="scnd_btn py-0 px-1 brds-1 my-2" title="remove direction" style="height: 15px;font-size: 12px;">X</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from "axios";
import config from "../../config";
import Loader from "../../components/loader.vue";
import Inform from "../../components/inform.vue";
export default {
    name: "createCustomMeal",
    components: { Loader, Inform },
    props: ['mealDetail'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            searchFood: '',
            addDetails: true,
            addCover: false,
            addRecipe: false,
            pageLoading: false,
            informModal: false,
            modalTitle: null,
            modalDetail: null,
            autoModeV: false,
            manualModeV: false,
            containsArray: ['Meat','Fish','Shellfish','Soy','Tree Nuts','Eggs','Dairy','Gluten','Peanuts'],
            suitableArray: ['breakfast','lunch','dinner','snacks'],
            allFoods: null,
            foodDetails: [],
            mealDetails: true,
            recipe: false,
            fileError: false,
            directions: null,
            videoURL: null,
            imageURL: null,
            fileChanged: false,
            ingredientEntered: null,
            tags: [],
            mealData: {
                name: null,
                prep_time: null,
                cook_time: null,
                suitable_for: [],
                tags: [],
                contains: [],
                file: null,
                file_type: null,
                video_thumbnail: null,
                no_of_servings: 1,
                calories_per_serving: null,
                protein_per_serving: null,
                carbs_per_serving: null,
                fat_per_serving: null,
                fiber_per_serving: null,
                ingredients: [],
                directions: [],
                meal_type: 'auto',
                nutrient: {
                    saturated_fat: 0,
                    trans_fat: 0,
                    polyunsaturated_fat: 0,
                    monounsaturated_fat: 0,
                    cholestrol: 0,
                    sodium: 0,
                    dietary_fiber: 0,
                    total_sugars: 0,
                    vitamin_a: 0,
                    vitamin_c: 0,
                    vitamin_d: 0,
                    vitamin_e: 0,
                    thiamin: 0,
                    riboflavin: 0,
                    niacin: 0,
                    vitamin_b6: 0,
                    vitamin_b12: 0,
                    pantothenic_acid: 0,
                    calcium: 0,
                    iron: 0,
                    potassium: 0,
                    phosphorus: 0,
                    magnesium: 0,
                    zinc: 0,
                    selenium: 0,
                    copper: 0,
                    menganese: 0,
                    alchohal: 0,
                    caffeine: 0,
                }
            }
        };
    },
    // beforeUnmount(){
    //     this.mealDetail = null;
    // },
    mounted() {
        let tempMD = JSON.parse(JSON.stringify(this.mealDetail));
        // console.log("meadetail as mount: ",JSON.parse(JSON.stringify(this.mealDetail)));
        this.getAllFoods();
        this.getAllTags();
        let tempNutrient = JSON.parse(JSON.stringify(this.mealData.nutrient));
        tempMD.contains = JSON.parse(tempMD.contains);
        tempMD.directions = JSON.parse(tempMD.directions);
        tempMD.ingredients = JSON.parse(tempMD.ingredients);
        tempMD.suitable_for = JSON.parse(tempMD.suitable_for);
        tempMD.tags = JSON.parse(tempMD.tags);
        this.mealData = JSON.parse(JSON.stringify(tempMD));
        this.imageURL = this.mealData.file;
        this.videoURL = this.mealData.file;
        // console.log("meadetail after mount: ",JSON.parse(JSON.stringify(this.mealDetail)));
        if(this.mealData.meal_type === 'auto'){
            this.autoModeV = true;
            this.manualModeV = false;
        } else {
            this.autoModeV = false;
            this.manualModeV = true;
            this.ingredientEntered = tempMD.ingredients.join('\n');
        }
        // ============================================================
        // this piece of code is for avoiding crashing for older meals
        // ============================================================
        for (let k = 0; k < tempMD.ingredients.length; k++) {
            if(this.mealData.meal_type === 'auto' && !this.mealData.ingredients[k].hasOwnProperty('quantity1')){
                this.mealData.ingredients[k].quantity1 = 0;
                this.mealData.ingredients[k].quantity2 = 0;
            }
        }
        if(this.mealData.nutrient == null){
            this.mealData.nutrient = tempNutrient;
        }
        // ============================================================
    },
    watch: {
        searchFood(newValue, oldValue) {
            this.searchFoodApi(newValue);
        }
    },
    methods: {
        filterMealsByLanguage() {
            this.allFoods = this.allFoods.filter((item) => item.language == this.mealData.language);
        },
        searchFoodApi(text){
            if(text == ''){
                this.getAllFoods()
            }else{
                axios
                .get(config.baseApiUrl + "get-specific-foods/" + text, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.allFoods = res.data.data;
                        this.filterMealsByLanguage();
                    } else {
                        this.modalTitle = "Error";
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                })
                .catch((er) => {
                    this.modalTitle = "Error!";
                    this.modalDetail = er;
                    this.informModal = true;
                });
            }
        },
        getAllTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-uncategorized-tags?category=meal', this.apiConfig).then(res => {
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
        getAllFoods() {
            axios.get(config.baseApiUrl + "get-foods", this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.allFoods = res.data.data;
                    this.filterMealsByLanguage();
                } else {
                    this.modalTitle = "Error";
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch((er) => {
                this.modalTitle = "Error!";
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        addDetailsShow() {
            this.addDetails = true;
            this.addCover = false;
            this.addRecipe = false;
            this.mealDetails = true;
            this.recipe = false;
        },
        addRecipeShow() {
            if (this.addDetails == true) {
                this.coverDiv();
            }
            else if (this.addCover == true) {
                this.showRecipe1();
            }
        },
        changeQuantity() {
            this.mealData.protein_per_serving = 0;
            this.mealData.fat_per_serving = 0;
            this.mealData.carbs_per_serving = 0;
            this.mealData.calories_per_serving = 0;
            this.mealData.fiber_per_serving = 0;
            for (let i = 0; i < this.mealData.ingredients.length; i++) {
                let qty = this.mealData.ingredients[i].quantity1 + parseFloat(this.mealData.ingredients[i].quantity2);
                this.mealData.protein_per_serving += (this.mealData.ingredients[i].protein * qty);
                this.mealData.fat_per_serving += (this.mealData.ingredients[i].fat * qty);
                this.mealData.carbs_per_serving += (this.mealData.ingredients[i].carbs * qty);
                this.mealData.calories_per_serving += (this.mealData.ingredients[i].calories * qty);
                this.mealData.fiber_per_serving += (this.mealData.ingredients[i].fiber * qty);
            }
        },
        quitComponent() {
            this.$parent.editMeal = false;
        },
        coverDiv() {
            if (
                this.mealData.name == null ||
                this.mealData.name == "" ||
                this.mealData.prep_time == null ||
                this.mealData.prep_time == "" ||
                this.mealData.cook_time == null ||
                this.mealData.cook_time == "" ||
                this.mealData.suitable_for.length == 0 ||
                this.mealData.tags.length < 1
            ) {
                this.modalTitle = "Error"
                this.modalDetail = "* are rquired fields please fill them all";
                this.informModal = true;
                return;
            } else {
                this.addDetails = false;
                this.addCover = true;
            }
        },
        showRecipe1() {
            if (this.mealData.file == null || this.fileError) {
                this.modalTitle = "File Error";
                this.modalDetail = "Please select a video or image file";
                this.informModal = true;
                return;
            } else {
                this.addCover = false;
                this.mealDetails = false;
                this.recipe = true;
                this.addRecipe = true;
            }
        },
        manualMode() {
            this.mealData.ingredients = [];
            this.ingredientEntered = '';
            this.mealData.protein_per_serving = 0;
            this.mealData.fat_per_serving = 0;
            this.mealData.calories_per_serving = 0;
            this.mealData.carbs_per_serving = 0;
            this.mealData.fiber_per_serving = 0;
            this.mealData.no_of_servings = 1;
            this.mealData.meal_type = 'manual';
            this.autoModeV = false;
            this.manualModeV = true;
        },
        autoMode() {
            this.mealData.ingredients = [];
            this.ingredientEntered = '';
            this.mealData.protein_per_serving = 0;
            this.mealData.fat_per_serving = 0;
            this.mealData.calories_per_serving = 0;
            this.mealData.carbs_per_serving = 0;
            this.mealData.fiber_per_serving = 0;
            this.mealData.no_of_servings = 1;
            this.mealData.meal_type = 'auto';
            this.manualModeV = false;
            this.autoModeV = true;
        },
        async getCover() {
            this.fileError = false;
            this.mealData.file = this.$refs.selectedCover.files[0];
            if (
                !this.mealData.file.type.includes("video") &&
                !this.mealData.file.type.includes("image")
            ) {
                this.modalTitle = "File Error";
                this.modalDetail = "Please select a video or image file";
                this.informModal = true;
                this.fileError = true;
                return;
            }

            if (this.mealData.file.type.includes("video")) {
                this.imageURL = null;
                this.mealData.file_type = 'video';
                this.videoURL = URL.createObjectURL(this.mealData.file);
                const xx = await this.generateThumbnail(this.mealData.file);
                this.mealData.video_thumbnail = new File([xx], "thumbailImage.png", { type: "image/png", lastModified: new Date().getTime() });
            }
            if (this.mealData.file.type.includes("image")) {
                this.videoURL = null;
                this.mealData.file_type = 'image';
                this.imageURL = URL.createObjectURL(this.mealData.file);
            }
            this.fileChanged = true;
        },
        generateThumbnail(file) {
            return new Promise((resolve) => {
                const canvas = document.createElement("canvas");
                const video = document.createElement("video");
                video.autoplay = true;
                video.muted = true;
                video.src = URL.createObjectURL(file);

                video.onloadeddata = () => {
                    let ctx = canvas.getContext("2d");

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
                    video.pause();
                    ctx.canvas.toBlob(
                        blob => {
                            resolve(blob);
                        },
                        "image/jpeg",
                        0.5 /* quality */
                    );
                };
            });
        },
        addFood(food) {
            food.quantity1 = 1;
            food.quantity2 = 0;
            this.mealData.ingredients.push(food);
            this.changeQuantity();
        },
        removeFood(m) {
            this.mealData.ingredients.splice(m, 1);
            this.changeQuantity();
        },
        addDirection() {
            if (this.directions == null || this.directions == "") {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Add directions first';
                this.informModal = true;
            }
            else {
                this.mealData.directions.push(this.directions);
                this.directions = null;
            }
        },
        removeDirection(i){
            this.mealData.directions.splice(i,1);
        },
        acknowledged() {
            this.informModal = false;
        },
        saveForm() {
            console.log("meadata",this.mealData);
            if (this.manualModeV == true && this.ingredientEntered !== null) {
                this.mealData.ingredients = this.ingredientEntered.split(/\r?\n|\r|\n/g);
            }
            if (this.mealData.ingredients.length == 0 ||
                this.mealData.no_of_servings === null || this.mealData.no_of_servings === "" ||
                this.mealData.protein_per_serving === null || this.mealData.protein_per_serving === "" ||
                this.mealData.fat_per_serving === null || this.mealData.fat_per_serving === "" ||
                this.mealData.calories_per_serving === null || this.mealData.calories_per_serving === "" ||
                this.mealData.fiber_per_serving === null || this.mealData.fiber_per_serving === "" ||
                this.mealData.carbs_per_serving === null || this.mealData.carbs_per_serving === "" ||
                this.mealData.language === null) 
                {
                this.pageLoading = false;
                this.modalTitle = "Error";
                this.modalDetail = "* are required field please fill them";
                this.informModal = true;
                return;
            }
            if (this.mealData.no_of_servings < 0 || this.mealData.protein_per_serving < 0 || this.fiber_per_serving < 0 ||
                this.mealData.calories_per_serving < 0 || this.mealData.fat_per_serving < 0 || this.mealData.carbs_per_serving < 0) 
            {
                this.modalTitle = "Error";
                this.modalDetail = "no value can be less than 0";
                this.informModal = true;
                return;
            }
            if(this.mealData.protein_per_serving == 0 && this.fiber_per_serving == 0 || this.mealData.calories_per_serving == 0 
                && this.mealData.fat_per_serving == 0 && this.mealData.carbs_per_serving == 0) {
                this.modalTitle = "Error";
                this.modalDetail = "all macro nutrients cannot be zero";
                this.informModal = true;
                return;
            }
            if (this.mealData.nutrient.saturated_fat < 0 || this.mealData.nutrient.trans_fat < 0 || this.mealData.nutrient.polyunsaturated_fat < 0 ||
                this.mealData.nutrient.monounsaturated_fat < 0 || this.mealData.nutrient.cholestrol < 0 || this.mealData.nutrient.sodium < 0 ||
                this.mealData.nutrient.dietary_fiber < 0 || this.mealData.nutrient.total_sugars < 0 || this.mealData.nutrient.vitamin_a < 0 ||
                this.mealData.nutrient.vitamin_c < 0 || this.mealData.nutrient.vitamin_d < 0 || this.mealData.nutrient.vitamin_e < 0 || this.mealData.nutrient.thiamin < 0 ||
                this.mealData.nutrient.riboflavin < 0 || this.mealData.nutrient.niacin < 0 || this.mealData.nutrient.vitamin_b6 < 0 || this.mealData.nutrient.vitamin_b02 < 0 ||
                this.mealData.nutrient.pantothenic_acid < 0 || this.mealData.nutrient.calcium < 0 || this.mealData.nutrient.iron < 0 || this.mealData.nutrient.potassium < 0 ||
                this.mealData.nutrient.phosphorus < 0 || this.mealData.nutrient.magnesium < 0 || this.mealData.nutrient.zinc < 0 || this.mealData.nutrient.selenium < 0 || this.mealData.nutrient.copper < 0 || this.mealData.nutrient.menganese < 0 ||
                this.mealData.nutrient.alchohal < 0 || this.mealData.nutrient.caffeine < 0
            ) {
                this.pageLoading = false;
                this.modalTitle = "Error";
                this.modalDetail = "no value can be less than 1";
                this.informModal = true;
                return;
            }


            let fd = new FormData();
            fd.append("id", this.mealData.id);
            fd.append("name", this.mealData.name);
            fd.append("prep_time", this.mealData.prep_time);
            fd.append("cook_time", this.mealData.cook_time);
            fd.append("suitable_for", JSON.stringify(this.mealData.suitable_for));
            fd.append("tags", JSON.stringify(this.mealData.tags));
            fd.append("contains", JSON.stringify(this.mealData.contains));
            fd.append("no_of_servings", this.mealData.no_of_servings);
            fd.append("calories_per_serving", this.mealData.calories_per_serving);
            fd.append("protein_per_serving", this.mealData.protein_per_serving);
            fd.append("carbs_per_serving", this.mealData.carbs_per_serving);
            fd.append("fat_per_serving", this.mealData.fat_per_serving);
            fd.append("fiber_per_serving", this.mealData.fiber_per_serving);
            fd.append("meal_type", this.mealData.meal_type);
            fd.append("ingredients", JSON.stringify(this.mealData.ingredients));
            fd.append("language", this.mealData.language);
            fd.append("directions", JSON.stringify(this.mealData.directions));
            if (this.manualModeV == true) {
                fd.append("nutrient", JSON.stringify(this.mealData.nutrient));
            }
            if(this.fileChanged){
                fd.append("file_type", this.mealData.file_type);
                fd.append("file", this.mealData.file);
                if (this.mealData.file_type == 'video') {
                    fd.append("video_thumbnail", this.mealData.video_thumbnail);
                }
            }
            this.pageLoading = true;
            axios.post(config.baseApiUrl + "update-meal", fd, this.apiConfig).then((res) => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = "Done!";
                    this.modalDetail = "Meal Updated";
                    this.informModal = true;
                    this.$parent.getAllMeals();
                    this.$parent.showDetails = false;
                    this.$parent.showContent = true;
                    this.$parent.editMeal = false;
                } else {
                    this.modalTitle = "Error!";
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch((er) => {
                this.pageLoading = false;
                this.modalTitle = "Failed!";
                this.modalDetail = er.message;
                this.informModal = true;
            });
        },
    },
};
</script>
<style scoped>
.main {
    width: 80%;
    height: 85vh;
    background-color: white;
    border-radius: 25px;
    text-align: center;
    position: relative;
    overflow-y: auto;
    padding-bottom: 20px;
}

.div1 {
    width: 30%;
    height: 100%;
    background-color: #f7f7f7;
    border: none;
    float: left;
    border-radius: 10px;
}

.div2 {
    width: 67%;
    height: 100%;
    background-color: white;
    float: right;
    border: 1px solid #e7e7e7;
    border-radius: 10px;
    text-align: start;
    padding: 10px;
    overflow-y: auto;
}

.inp1 {
    border: 1px solid #C5C5C5;
    border-radius: 5px;
    max-width: 380px;
    width: 100%;
    height: 40px;
    color: #C5C5C5;
    padding-left: 10px;
}

.inp1::placeholder {
    font-size: 13px;
    color: #C5C5C5;
}

.inp2 {
    border: 1px solid #c5c5c5;
    border-radius: 5px;
    max-width: 210px;
    width: 100%;
    height: 40px;
    color: #C5C5C5;
    float: left;
    font-size: 13px;
}

.btn1 {
    height: 30px;
    width: 150px;
    background-color: #f2a18c;
    border: none;
    border-radius: 10px;
    color: white;
    font-size: 16px;
}

.btn3 {
    height: 25px;
    width: 115px;
    background-color: #f2a18c;
    border: none;
    border-radius: 7px;
    color: black;
    font-size: 9px;
    margin-top: 20px;
}

.btn4 {
    height: 30px;
    width: 70%;
    background-color: #f2a18c;
    border: none;
    border-radius: 10px;
    color: black;
    font-size: 16px;
}

.btn5 {
    height: 24px;
    width: 26px;
    margin-left: 5px;
    margin-top: 0px;
    float: right;
    border: 1px solid #c5c5c5;
    background: none;
    padding: 0px;
    border-radius: 2px;
}



.inp3::placeholder {
    font-size: 12px;
    color: #c5c5c5;
}

.inp4 {
    height: 20px;
    width: 75px;
    font-size: 10px;
    float: left;
    border: 1px solid #c5c5c5;
    border-radius: 3px;
}

.inp4::placeholder {
    font-size: 10px;
    color: #c5c5c5;
}

.inp5 {
    width: 95%;
    height: 13px;
    padding: 0px;
    margin: 0px;
    margin-top: 0px;
    border: 1px solid #c5c5c5;
    text-align: center;
    font-size: 8px;
}

.inp5::placeholder {
    font-size: 8px;
    color: #c5c5c5;
}

.btn2 {
    height: 24px;
    width: 26px;
    margin-left: 5px;
    margin-top: 0px;
    float: left;
    border: 1px solid #c5c5c5;
    background: none;
    padding: 0px;
    border-radius: 2px;
}

.btn-img {
    width: 15px;
    height: 15px;
}

.inputfood {
    border: none;
    background-color: transparent;
    width: calc(100% - 30px);
    font-size: 13px;
    color: black;
}

.select1 {
    border: 1px solid #c5c5c5;
    border-radius: 5px;
    height: 25px;
    width: 35px;
}

.card1 {
    height: 50px;
    width: 40px;
    float: left;
    text-align: center;
    border: none;
    border-radius: 5px;
    margin-left: 8px;
}

.card2 {
    height: 70px;
    width: 60px;
    float: left;
    text-align: center;
    border: none;
    border-radius: 5px;
    margin-left: 8px;
}

.card3 {
    height: 70px;
    width: 60px;
    float: left;
    text-align: center;
    border: none;
    border-radius: 5px;
    margin-left: 10px;
    margin-top: 10px;
    position: relative;
}

.img1 {
    height: 20px;
    width: 20px;
}

.txt1 {
    width: 95%;
    height: 150px;
    border: 1px solid #c5c5c5;
    color: #c5c5c5;
    font-size: 13px;
    border-radius: 10px;
    padding: 5px;
}

.txt1::placeholder {
    font-size: 13px;
    color: #c5c5c5;
}

.dropdown {
    display: inline-block;
}

.dropdown-content {
    display: none;
    z-index: 1;
}

.dropbtn:hover .dropdown-content {
    display: block;
}

.active {
    background-color: #dfe0e2 !important;
}

.form-check-input {
    height: 20px;
    width: 20px;
}

@media screen and (max-width: 1010px) {
    .main {
        width: 90%;
    }
}

@media screen and (max-width: 730px) {
    .main {
        width: 100%;
    }

    .div1 {
        width: 23%;
    }

    .div2 {
        width: 76%;
    }
}
</style>
