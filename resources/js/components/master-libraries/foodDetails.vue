<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <div class="main position-relative brds-5">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:10px;top:5px;font-size:25px;z-index:9999!important;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div v-if="foodDetails!=null" style="height:65vh;margin-top:35px">
                <h4 class="mt-4 col-11 mx-auto title">{{foodDetails.name}}</h4>
                <div class="col-11 mt-3 p-1 content">
                    <div class="d-flex justify-content-between py-3">
                        <div class="w-50">
                            <img :src="foodDetails.image" alt="no-image" class="img-fluid brds-2" style="max-height:200px">
                        </div>
                        <div class="w-50">
                            <h6 class="col-12 m-0 h7">{{foodDetails.calories}} cals Calories / 44caZ</h6>
                            <p class="col-12 m-0 h8">{{foodDetails.serving_size}}:Servings</p>
                            <p class="col-12 m-0 h8">{{foodDetails.carbs}}g Carbs, {{foodDetails.protein}}g Protein, {{foodDetails.fat}}g Fat, {{foodDetails.fiber}}g Fiber</p>
                        </div>
                    </div>
                    <h5 class="mb-1">Nutrition Facts</h5>
                    <div class="col-12 nutrition">
                        <div class="card2 tsl" v-if="foodDetails.saturated_fat">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Saturated Fat(g)</p>
                            <p style="font-size:10px">{{foodDetails.saturated_fat}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.trans_fat">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Trans fat(g)</p>
                            <p style="font-size:10px">{{foodDetails.trans_fat}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.polyunsaturated_fat">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Polyunsaturated fat(g)</p>
                            <p style="font-size:10px">{{foodDetails.polyunsaturated_fat}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.monounsaturated_fat">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Monounsaturated fat(g)</p>
                            <p style="font-size:10px">{{foodDetails.monounsaturated_fat}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.cholestrol">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Cholestrol (g)</p>
                            <p style="font-size:10px">{{foodDetails.cholestrol}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.sodium">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Sodium (g)</p>
                            <p style="font-size:10px">{{foodDetails.sodium}}</p>
                        </div>
                        <!-- <div class="card2 tsl" v-if="foodDetails.dietary_fiber">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Dietary fiber(g)</p>
                            <p style="font-size:10px">{{foodDetails.dietary_fiber}}</p>
                        </div> -->
                        <div class="card2 tsl" v-if="foodDetails.total_sugars">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Total sugars(g)</p>
                            <p style="font-size:10px">{{foodDetails.total_sugars}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_a">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin a(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_a}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_c">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin c(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_c}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_d">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin d(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_d}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_e">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin e(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_e}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.thiamin">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Thiamin(g)</p>
                            <p style="font-size:10px">{{foodDetails.thiamin}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.riboflavin">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Riboflavin(g)</p>
                            <p style="font-size:10px">{{foodDetails.riboflavin}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.niacin">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Niacin(g)</p>
                            <p style="font-size:10px">{{foodDetails.niacin}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_b6">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin b6(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_b6}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.vitamin_b12">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Vitamin b12(g)</p>
                            <p style="font-size:10px">{{foodDetails.vitamin_b12}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.pantothenic_acid">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Pantothenic acid(g)</p>
                            <p style="font-size:10px">{{foodDetails.pantothenic_acid}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.calcium">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Calcium(g)</p>
                            <p style="font-size:10px">{{foodDetails.calcium}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.iron">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Iron(g)</p>
                            <p style="font-size:10px">{{foodDetails.iron}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.potassium">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Potassium(g)</p>
                            <p style="font-size:10px">{{foodDetails.potassium}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.phosphorus">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Phosphorus(g)</p>
                            <p style="font-size:10px">{{foodDetails.phosphorus}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.magnesium">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Magnesium(g)</p>
                            <p style="font-size:10px">{{foodDetails.magnesium}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.zinc">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Zinc(g)</p>
                            <p style="font-size:10px">{{foodDetails.zinc}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.selenium">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Selenium(g)</p>
                            <p style="font-size:10px">{{foodDetails.selenium}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.copper">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Copper(g)</p>
                            <p style="font-size:10px">{{foodDetails.copper}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.menganese">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Menganese(g)</p>
                            <p style="font-size:10px">{{foodDetails.menganese}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.alchohal">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Alchohal(g)</p>
                            <p style="font-size:10px">{{foodDetails.alchohal}}</p>
                        </div>
                        <div class="card2 tsl" v-if="foodDetails.caffeine">
                            <p style="font-size:9px;font-weight:bold;margin:0px;word-break:break-all;">Caffeine(g)</p>
                            <p style="font-size:10px">{{foodDetails.caffeine}}</p>
                        </div>
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
    name: 'foodDetails',
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            foodDetails: null,
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    props: ['FoodId'],
    mounted() {
        this.getDetails();
    },
    methods: {
        getDetails() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'food-detail/' + this.FoodId, this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.foodDetails = res.data.data;
                    } else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = "Something went wrong";
                        this.informModal = true;
                        console.log("Error: geting food details ", res.data.message);
                    }
                }).catch(err => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: geting food details ", err);
                });
        },
        quitComponent() {
            this.$parent.fooddetailsshow();
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.main {
    width: 55%;
    height: 80vh;
    background-color: white;
    text-align: center;
}

.savebtn {
    width: 150px;
    height: 25px;
    font-size: 15px;
    border: none;
    border-radius: 5px;
}

.nutrition {
    overflow-y: auto;
    border-top: 1px solid #E7E7E7;
    height: calc(100% - 120px);
}

.content {
    margin-right: auto;
    margin-left: auto;
    border: 1px solid #E7E7E7;
    border-radius: 10px;
    height: calc(100% - 50px);
}

.card2 {
    width: 60px;
    height: 60px;
    padding-top: 10px;
    margin-top: 10px;
    margin-left: 10px;
    border-radius: 6px;
    text-align: center;
    float: left;
}

.main-box {
    background-color: white;
    border-radius: 30px;
    width: 100%;
    height: 83%;
    padding: 0px;
    margin-top: 15px;
    text-align: center;

}

.info {
    border: 1px solid;
    border-radius: 10px;
    border-color: #E7E7E7;
    width: 98%;
    height: 95%;
    margin-top: 5px;
    margin-left: auto;
    margin-right: auto;
    text-align: start;
    overflow-y: auto;

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
    height: 150px;
    padding-top: 10px;
    padding-left: 10px;
}

.card1 {
    height: 130px;
    width: 100px;
    border-radius: 10px;
    text-align: center;
    padding-top: 10px;
    float: left;
    margin-left: 6%;
}

.card1-input {
    width: 60px;
    height: 15px;
    border-color: #C5C5C5;
    font-size: 10px;
    border: 1px solid #C5C5C5;
    border-radius: 3px;
}

.card1-input::placeholder {
    font-size: 10px;
    text-align: center;
}

.card1-input::-webkit-outer-spin-button,
.card1-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.card1-input[type=number] {
    -moz-appearance: textfield;
}

.img1 {
    height: 40px;
    width: 40px;
}

.mini-cards {
    width: 98%;
    height: calc(100% - 190px);
    overflow-y: auto;
    padding: 0;
}

.mini-card-input {
    width: 55px;
    height: 15px;
    border-color: #C5C5C5;
    font-size: 10px;
    border: 1px solid #C5C5C5;
    border-radius: 2px;
    position: absolute;
    bottom: 5px;
    left: 8px;
}

.mini-card-input::placeholder {
    font-size: 10px;
    text-align: center;
}

.mini-card-input::-webkit-outer-spin-button,
.mini-card-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.mini-card-input[type=number] {
    -moz-appearance: textfield;
}

.card3 {
    width: 70px;
    height: 80px;
    padding: 10px;
    margin-top: 10px;
    margin-left: 13px;
    border-radius: 6px;
    text-align: center;
    float: left;
    position: relative;
}

@media screen and (max-width: 670px) {
    .main {
        width: 80%;
    }
}

@media screen and (max-width: 501px) {
    .main {
        width: 90%;
    }

    .title {
        font-size: 15px;
    }
}

@media screen and (max-width: 1195px) {
    .main {
        width: 75%;
    }
}

@media screen and (max-width: 955px) {
    .main {
        width: 90%;
    }
}

@media screen and (max-width: 801px) {
    .main {
        width: 95%;
    }
}
</style>
