<template lang="">
    <div class="my-popup-component" @click.self="showConfirmModal">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <AssignTags v-if="tagsComp" :tagType="'food'" :prefilledTags="tagIds"/>
        <div class="main-box position-relative">
            <button class="trans_btn position-absolute" @click="showConfirmModal"
                style="right:35px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h4 class="my-2 text-center">Edit Food</h4>
            <div class="info overflow-auto">
                <div class="row w-100 mb-4">
                    <div class="col-6 px-2">
                        <div class="mx-0">
                            <div class="px-3 mt-2">
                                <h5 class="mb-2">Food Name <span class="text-danger">*</span></h5>
                                <input @input="this.nameError=false" placeholder="Food Name" class="w-100 px-2 py-1 tm-brdr brds-1" v-model="AddFoodData.name">
                                <p v-show="nameError" class="h8 mb-0 mt-1 text-danger">please enter name</p>
                            </div>
                            <div class="px-3 mt-2">
                                <p class="ms-2 my-2" style="font-size:13px;">Language <span style="color:red;">*</span></p>
                                <select v-model="AddFoodData.language" style="white-space: nowrap; width: 100px">
                                    <option value="en">English</option>
                                    <option value="ar">Arabic</option>
                                </select>
                                <p v-show="languageError" class="h8 mb-0 mt-1 text-danger">please select language</p>
                            </div>
                            <div class="px-3 mt-2">
                                <h5 class="mb-2">Serving Size <span class="text-danger">*</span></h5>
                                <input @input="this.serving_sizeError=false" placeholder="serving size" class="w-100 px-2 py-1 tm-brdr brds-1" v-model="AddFoodData.serving_size">
                                <p v-show="serving_sizeError" class="h8 mb-0 mt-1 text-danger">please enter serving size between 1 & 99</p>
                            </div>
                            <div class="px-3 mt-2">
                                <h5 class="mb-2">Tags <span class="text-danger">*</span></h5>
                                <div class="w-100 px-2 py-1 tm-brdr brds-1 d-flex flex-wrap" style="max-height:150px;overflow-y:auto">
                                    <span class="tg-pill" v-for="tgn in AddFoodData.tagNames">{{tgn}}</span>
                                    <span class="tg-btn pointer" @click="assignTagsShow()">add/remove</span>
                                </div>
                                <p v-show="tagError" class="h8 mb-0 mt-1 text-danger">please select atleast 1 tag</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 px-2">
                        <div class="d-flex justify-content-between mb-2 mt-2">
                            <h6 class="">Image <span class="text-danger">*</span></h6>
                            <button class="prim_btn py-1 px-2 h7 brds-1" @click="this.$refs.foodImage.click()">select</button>
                        </div>
                        <div class="img-side w-100">
                            <input type="file" ref="foodImage" @change="getImage()" style="display:none">
                            <div style="max-height: 200px;overflow: hidden;" v-if="tempImgUrl!==null">
                                <img :src="tempImgUrl" alt="" class="img-fluid brds-2">
                            </div>
                            <p v-show="imgError" class="h8 mb-0 mt-1 text-danger">please select image file</p>
                        </div>
                    </div>
                </div>
                <div class="w-100 pb-5 text-center">
                    <div class="d-flex justify-content-center">
                        <div class="brds-2 py-3 px-2 text-center mx-3 tsh">
                            <img src="/images/Group57573.png" alt="image-error" class="img1 m-0">
                            <p class="h7 my-2">Protein</p>
                            <input @input="this.pcfc=false" type="number" placeholder="0g(0%)" class="card1-input h8 px-2 py-1 brds-1 tm-brdr" v-model="AddFoodData.protein">
                        </div>
                        <div class="brds-2 py-3 px-2 text-center mx-3 tsh">
                            <img src="/images/Group57578.png" alt="image-error" class="img1 m-0">
                            <p class="h7 my-2">Fats</p>
                            <input @input="this.pcfc=false" type="number" placeholder="0g(0%)" class="card1-input h8 px-2 py-1 brds-1 tm-brdr" v-model="AddFoodData.fat">
                        </div>
                        <div class="brds-2 py-3 px-2 text-center mx-3 tsh">
                            <img src="/images/Group57580.png" alt="image-error" class="img1 m-0">
                            <p class="h7 my-2">Carbs</p>
                            <input @input="this.pcfc=false" type="number" placeholder="0g(0%)" class="card1-input h8 px-2 py-1 brds-1 tm-brdr" v-model="AddFoodData.carbs">
                        </div>
                        <div class="brds-2 py-3 px-2 text-center mx-3 tsh">
                            <img src="/images/Group57965.png" alt="image-error" class="img1 m-0">
                            <p class="h7 my-2">Calories</p>
                            <input @input="this.pcfc=false" type="number" placeholder="0g(0%)" class="card1-input h8 px-2 py-1 brds-1 tm-brdr" v-model="AddFoodData.calories">
                        </div>
                        <div class="brds-2 py-3 px-2 text-center mx-3 tsh">
                            <img src="/images/Group2.png" alt="image-error" class="img1 m-0">
                            <p class="h7 my-2">Fiber</p>
                            <input @input="this.pcfc=false" type="number" placeholder="0g(0%)" class="card1-input h8 px-2 py-1 brds-1 tm-brdr" v-model="AddFoodData.fiber">
                        </div>
                    </div>
                    <p class="mt-2 mb-0 h7 text-danger" v-show="pcfc">these fields must be between 0 & 9999</p>
                </div>
                <h5 class="border-bottom pb-2 my-0 mx-4">NUTRITION FACTS</h5>
                <div class="d-flex justify-content-center flex-wrap px-2 py-3">
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Saturated Fat(g)</p>
                        <input class="mini-card-input" type="number" max="999" placeholder="0g (0%)" v-model="AddFoodData.saturated_fat">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">trans_fat(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.trans_fat">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">polyunsaturated_fat(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.polyunsaturated_fat">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">monounsaturated_fat(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.monounsaturated_fat">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">cholestrol(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.cholestrol">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">sodium(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.sodium">
                    </div>
                    <!-- <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">dietary_fiber(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.dietary_fiber">
                    </div> -->
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">total_sugars(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.total_sugars">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_a(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_a">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_c(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_c">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_d(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_d">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_e(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_e">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">thiamin(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.thiamin">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">riboflavin(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.riboflavin">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">niacin(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.niacin">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_b6(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_b6">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">vitamin_b12(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.vitamin_b12">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">pantothenic_acid(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.pantothenic_acid">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">calcium(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.calcium">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">iron(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.iron">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">potassium(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.potassium">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">phosphorus(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.phosphorus">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">magnesium(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.magnesium">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">zinc(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.zinc">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">selenium(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.selenium">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">copper(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.copper">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">menganese(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.menganese">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">alchohal(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.alchohal">
                    </div>
                    <div class="card2 tsl">
                        <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">caffeine(g)</p>
                        <input class="mini-card-input" type="number" placeholder="0g (0%)" v-model="AddFoodData.caffeine">
                    </div>
                </div>
            </div>
            <button type="button" class="prim_bg savebtn" @click="updateFood()">Update</button>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import AssignTags from '../clients/assignTags.vue';
export default {
    components: { Loader, Inform, Confirm, AssignTags },
    name: 'addCustomFood',
    props : ['foodDet'],
    data() {
        return {
            apiConfig: {
                "headers": {
                    "Authorization": 'Bearer ' + config.storage.getItem('fwd_session_token'),
                    "Content-Type" : "multipart/form-data"
                }
            },
            nameError: false,
            languageError: false,
            serving_sizeError: false,
            pcfc : false,
            tagError : false,
            tagsComp : false,
            tagIds : this.foodDet.tags,
            tagNames : [],
            imgError: false,
            tempImgUrl: this.foodDet.image,
            imageChanged: false,
            AddFoodData: this.foodDet,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    mounted(){
        this.AddFoodData.image = null;
    },
    methods: {
        getImage(){
            let img = this.$refs.foodImage.files[0];
            this.imgError = false;
            if(img.type.includes('jpg') || img.type.includes('png') || img.type.includes('jpeg')){
                this.AddFoodData.image = img;
                this.tempImgUrl = URL.createObjectURL(img);
                this.imageChanged = true;
            } else {
                this.imgError = true;
            }
        },
        assignTags(tags) {
            this.AddFoodData.tagNames = [];
            this.tagIds = [];
            tags.forEach(element => {
                this.AddFoodData.tagNames.push(element.tagName);
                this.tagIds.push(element.tagId);
                this.tagsComp = false;
            });
        },
        assignTagsShow(){
            this.tagError = false;
            this.tagsComp = !this.tagsComp;
        },
        showConfirmModal() {
            this.modalDetail = 'Your progress will be deleted.'
            this.modalTitle = 'Are you sure?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
            return;
            this.quitComponent();
        },
        quitComponent() {
            this.$parent.foodEdit = false;
        },
        updateFood() {
            this.AddFoodData.name = this.AddFoodData.name.trim();
            if (this.AddFoodData.name == null || this.AddFoodData.name == '') {
                this.nameError = true;
                return;
            }
            if (this.AddFoodData.language == null || this.AddFoodData.language == '') {
                this.languageError = true;
                return;
            }
            if (this.AddFoodData.serving_size == '' || this.AddFoodData.serving_size < 0 || this.AddFoodData.serving_size > 99) {
                this.serving_sizeError = true;
                return;
            }
            if (this.AddFoodData.protein == null || this.AddFoodData.protein ==="" || this.AddFoodData.protein < 0 || this.AddFoodData.protein > 9999
                || this.AddFoodData.fat == null || this.AddFoodData.fat ==="" || this.AddFoodData.fat < 0 || this.AddFoodData.fat > 9999
                || this.AddFoodData.carbs == null || this.AddFoodData.carbs ==="" || this.AddFoodData.carbs < 0 || this.AddFoodData.carbs > 9999
                || this.AddFoodData.calories == null || this.AddFoodData.calories ==="" || this.AddFoodData.calories < 0 || this.AddFoodData.calories > 9999
                || this.AddFoodData.fiber == null || this.AddFoodData.fiber ==="" || this.AddFoodData.fiber < 0 || this.AddFoodData.fiber > 9999) {
                this.pcfc = true;
                return;
            }
            if (this.AddFoodData.saturated_fat > 999 || this.AddFoodData.trans_fat > 999 || this.AddFoodData.polyunsaturated_fat > 999 
                || this.AddFoodData.monounsaturated_fat > 999 || this.AddFoodData.cholestrol > 999 || this.AddFoodData.sodium > 999
                // || this.AddFoodData.dietary_fiber > 999 
                || this.AddFoodData.total_sugars > 999 || this.AddFoodData.vitamin_a > 999 
                || this.AddFoodData.vitamin_c > 999 || this.AddFoodData.vitamin_d > 999 || this.AddFoodData.vitamin_e > 999 
                || this.AddFoodData.thiamin > 999 || this.AddFoodData.riboflavin > 999 || this.AddFoodData.niacin > 999 
                || this.AddFoodData.vitamin_b6 > 999 || this.AddFoodData.vitamin_b12 > 999 || this.AddFoodData.pantothenic_acid > 999 
                || this.AddFoodData.calcium > 999 || this.AddFoodData.iron > 999 || this.AddFoodData.potassium > 999 
                || this.AddFoodData.phosphorus > 999 || this.AddFoodData.magnesium > 999 || this.AddFoodData.zinc > 999 
                || this.AddFoodData.selenium > 999 || this.AddFoodData.copper > 999 || this.AddFoodData.menganese > 999 
                || this.AddFoodData.alchohal > 999 || this.AddFoodData.caffeine > 999) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Nutrition Facts cannot be greater than 999';
                this.informModal = true;
                return
            }
            if(this.tagIds.length < 1){
                this.tagError = true;
                return;
            }
            if(this.imageChanged && this.AddFoodData.image===null){
                this.imgError = true;
                return;
            }
            let fd = new FormData();
            fd.append('id',this.AddFoodData.id);
            fd.append('name',this.AddFoodData.name);
            fd.append('serving_size',this.AddFoodData.serving_size);
            fd.append('calories',this.AddFoodData.calories);
            fd.append('protein',this.AddFoodData.protein);
            fd.append('carbs',this.AddFoodData.carbs);
            fd.append('fat',this.AddFoodData.fat);
            fd.append('fiber',this.AddFoodData.fiber);
            fd.append('tags',JSON.stringify(this.tagIds));
            fd.append('saturated_fat',this.AddFoodData.saturated_fat);
            fd.append('trans_fat',this.AddFoodData.trans_fat);
            fd.append('polyunsaturated_fat',this.AddFoodData.polyunsaturated_fat);
            fd.append('monounsaturated_fat',this.AddFoodData.monounsaturated_fat);
            fd.append('cholestrol',this.AddFoodData.cholestrol);
            fd.append('sodium',this.AddFoodData.sodium);
            // fd.append('dietary_fiber',this.AddFoodData.dietary_fiber);
            fd.append('total_sugars',this.AddFoodData.total_sugars);
            fd.append('vitamin_a',this.AddFoodData.vitamin_a);
            fd.append('vitamin_c',this.AddFoodData.vitamin_c);
            fd.append('vitamin_d',this.AddFoodData.vitamin_d);
            fd.append('vitamin_e',this.AddFoodData.vitamin_e);
            fd.append('thiamin',this.AddFoodData.thiamin);
            fd.append('riboflavin',this.AddFoodData.riboflavin);
            fd.append('niacin',this.AddFoodData.niacin);
            fd.append('vitamin_b6',this.AddFoodData.vitamin_b6);
            fd.append('vitamin_b12',this.AddFoodData.vitamin_b12);
            fd.append('pantothenic_acid',this.AddFoodData.pantothenic_acid);
            fd.append('calcium',this.AddFoodData.calcium);
            fd.append('iron',this.AddFoodData.iron);
            fd.append('potassium',this.AddFoodData.potassium);
            fd.append('phosphorus',this.AddFoodData.phosphorus);
            fd.append('magnesium',this.AddFoodData.magnesium);
            fd.append('zinc',this.AddFoodData.zinc);
            fd.append('selenium',this.AddFoodData.selenium);
            fd.append('copper',this.AddFoodData.copper);
            fd.append('menganese',this.AddFoodData.menganese);
            fd.append('alchohal',this.AddFoodData.alchohal);
            fd.append('caffeine',this.AddFoodData.caffeine);
            fd.append('language',this.AddFoodData.language);
            if(this.imageChanged)
            fd.append('image',this.AddFoodData.image);

            this.pageLoading = true;
            this.loaderText = 'Uploading';
            axios.post(config.baseApiUrl + 'edit-food', fd, this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$parent.getAllFoods();
                        this.quitComponent();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = "Something went wrong";
                        this.informModal = true;
                        console.log("Error: adding food ", res.data.message);
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: adding food ", er);
                });
        },
        acknowledged() {
            this.informModal = false;
        },
    },
}
</script>
<style scoped>
.main-box {
    background-color: white;
    border-radius: 30px;
    width: 60%;
    height: 95%;
    padding-bottom: 20px;
    text-align: center;
}

.savebtn {
    border: none;
    width: 180px;
    height: 40px;
    border-radius: 10px;
    margin-right: auto;
    margin-left: auto;
    margin-top: 3vh;
}

.info {
    border: 1px solid;
    border-radius: 10px;
    border-color: #E7E7E7;
    width: 95%;
    height: 80%;
    margin-left: auto;
    margin-right: auto;
    text-align: start;
}

.info-inp {
    float: left;
    font-size: 13px;
    color: #C5C5C5;
    border: 1px solid #C5C5C5;
    border-radius: 5px;
}

.main-card {
    width: 100%;
    height: 140px;
    padding-top: 10px;
    padding-left: 10%;
}

.card1 {
    height: 130px;
    width: 110px;
    border-radius: 10px;
    text-align: center;
    padding-top: 10px;
    float: left;
    margin-left: 6%;
}

.img1 {
    height: 40px;
    width: 40px;
}




.card1-input{
    width: 110px;
}
.card1-input::-webkit-outer-spin-button,
.card1-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.card1-input[type=number] {
    -moz-appearance: textfield;
}



.card2 {
    width: 70px;
    height: 90px;
    padding: 10px;
    margin-top: 10px;
    margin-left: 5px;
    margin-right: 5px;
    border-radius: 5px;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.mini-card-input {
    border-color: #C5C5C5;
    font-size: 7px;
    border: 1px solid #C5C5C5;
    border-radius: 3px;
    border-radius: 5px;
    padding: 3px 5px 3px 5px;
    width: 100%;
}


.mini-card-input::-webkit-outer-spin-button,
.mini-card-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.mini-card-input[type=number] {
    -moz-appearance: textfield;
}

@media screen and (max-width: 1195px) {
    .main-box {
        width: 75%;
    }
}

@media screen and (max-width: 955px) {
    .main-box {
        width: 90%;
    }
}

@media screen and (max-width: 801px) {
    .main-box {
        width: 95%;
    }
}
</style>
