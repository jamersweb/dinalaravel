<template lang="">
    <div class="main">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <div class="w-100 py-2" style="background-color:#EEEEEE;border-top-right-radius:9px;border-top-left-radius:9px" >
            <div class="d-flex justify-content-between">
                <h4 class="m-0 px-3" >Food</h4>
                <div class="d-flex">
                    <div class="me-3 position-relative" style="width:250px">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="image-error" 
                        class="position-absolute" style="width: 12px;height: 12px;left: 8px;top: 11px;">
                        <input @input="applySearch()" type="text" class="ps-4 pe-2 py-1 bg-white brds-2 tm-brdr w-100" placeholder="Search for food" v-model="search">
                    </div>
                    <div class="filter-btn ms-1 me-2">
                        <button @click="filters=true" style="border:none;background-color:transparent">
                            <img src="/cms-assets/images/master-libraries/filter.png" alt="" class="img-fluid mt-1" style="max-height:20px">
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-main">
            <div class="content-bar">
                <button type="button" class="btn1" @click="addFoodPopup">New</button>
                <button class="btn3 p-0 tsl position-relative" type="button" data-bs-toggle="dropdown" >
                    <img src="/cms-assets/images/master-libraries/three-dots.png" style="width:20%" alt="">
                </button>
                <ul class="dropdown-menu tsl">
                    <li><button class="dropdown-item" @click="getFoodDetails()">Edit</button></li>
                    <li><button class="dropdown-item" @click="deleteFood()">Delete</button></li>
                </ul>
                <!-- <select class="inpname" id="">
                    <option>Name</option>
                </select> -->
            </div>
            <div class="content">
                <div class="col-4 h-75 mx-auto pt-5 mt-5 w-80" style="text-align:center;" v-if="allFoods.length==0">
                    <h5 style="font-size:30px;font-weight:600;color:#000000;">THERE ARE NO CUSTOM FOODS ADDED YET</h5>
                    <p style="font-size:16px;color:#B1B0B0;font-weight:lighter;">Start adding custom foods by clicking "New" button above,so that you can use them later when creating your meals.You will always be able to use over a million foods from our system library.</p>
                    <!-- <button class="prim_bg rounded mt-1" style="border:none;height:50px;width:195px;font-size:13px;color:#343434;">Add New</button> -->
                </div>
                <div class="col-4 h-75 mx-auto pt-5 mt-5 w-80" style="text-align:center;" v-if="allFoods.length>0 && finalVisibleFoods.length==0">
                    <h5 style="font-size:30px;font-weight:600;color:#000000;">No Food Matches Your Search or Filters</h5>
                </div>
                <div class="row w-100 mx-0">
                    <div class="col-md-4 col-sm-6 col-12"  v-for="items in finalVisibleFoods" :key="items">
                        <div class="card tsl pointer" @click="fooddetailsshow(items.id)">
                            <input @click.stop="" class="form-check-input tsl" :value="items.id" type="checkbox" v-model="selectedFoods">
                            <div class="d-flex w-100 p-2" style="height:90px">
                                <div class="w-50">
                                    <p class="mb-0 fw-bold wb-all" style="height: 24px; overflow: hidden;" data-toggle="tooltip" :title="items.name">{{trimTitle(items.name)}}</p>
                                    <p class="mb-0 h8">{{items.serving_size}} serving</p>
                                </div>
                                <div class="w-50 h-100 img-as-bg brds-1" :style='{"background-image":"url("+items.image+")"}'>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between px-1">
                                <div class="card1 tsl mx-1">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Protien</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{items.protein}}</p>
                                </div>
                                <div class="card1 tsl mx-1">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fat</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{items.fat}}</p>
                                </div>
                                <div class="card1 tsl mx-1">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Carbs</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{items.carbs}}</p>
                                </div>
                                <div class="card1 tsl mx-1">
                                    <img src="../../../../public/images/Group57573.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Calories</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{items.calories}}</p>
                                </div>
                                <div class="card1 tsl mx-1">
                                    <img src="../../../../public/images/Group2.png" alt="error" class="img1">
                                    <h6 style="color:#1D262D;font-size:8px;font-weight:bold;margin-top:5px;margin-bottom:0px;">Fibers</h6>
                                    <p style="color:#C5C5C5;font-size:9px;margin:0px">{{items.fiber}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class=" foot">
                <div class="itms">
                    <p class="p1">Showing 1 to 8 of 40 items</p>
                </div>
                <div class="pgin">
                    <ul class="pginul1">
                        <li class="pginli">&lt;</li>
                        <li class="pginli">1</li>
                        <li class="pginli">2</li>
                        <li class="pginli">3...</li>
                        <li class="pginli">10</li>
                        <li class="pginli">&gt;</li>
                    </ul>
                </div>
            </div> -->
        </div>
        <addCustomFood v-if="showAddFood"/>
        <foodDetails v-if="showFoodDetails" :FoodId="FoodId" />
        <EditFood v-if="foodEdit" :foodDet="foodData" />
        <Filters v-if="filters" :tags="tags" :prefillTags="selectedTagsForFilter"/>
    </div>
</template>
<script>
import addCustomFood from '../../components/master-libraries/addFoodPopup';
import foodDetails from '../../components/master-libraries/foodDetails';
import EditFood from '../../components/master-libraries/editFood';
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Filters from '../../components/filters.vue';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            showAddedFoods: false,
            showAddFood: false,
            allFoods: [],
            showFoodDetails: false,
            FoodId: null,
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            deletionIds: [],
            search: "",
            editFood: false,
            foodEdit: false,
            selectedFoods : [],
            tags: [],
            selectedTagsForFilter : [],
            finalVisibleFoods: [],
            tagsFilteredFoods: [],
            filters : false,
            foodData : null
        }
    },
    components: {
        addCustomFood,
        foodDetails,
        Loader,Filters,
        Inform,EditFood
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getAllFoods();
        this.getTags();
    },
    methods: {
        trimTitle(title){
            if(title.length>11){
                title = title.substring(0,11);
                return title+"..."
            }else{
                return title
            }
        },
        applyFilters(tagIds){
            this.selectedTagsForFilter = tagIds;
            this.tagsFilteredFoods = [];
            for (let i = 0; i < this.allFoods.length; i++) {
                const wrk = this.allFoods[i];
                for (let j = 0; j < tagIds.length; j++) {
                    if(wrk.tags===null)
                    break;
                    const tId = tagIds[j];
                    if(wrk.tags.includes(tId)){
                        this.tagsFilteredFoods.push(wrk);
                        break;
                    }
                };
            }
            this.finalVisibleFoods = this.tagsFilteredFoods;
            this.applySearch();
        },
        clearFilters(){
            this.selectedTagsForFilter = [];
            this.tagsFilteredFoods = this.allFoods;
            this.finalVisibleFoods = this.allFoods;
            this.applySearch();
        },
        applySearch(){
            let searchValue = this.search.toLowerCase().trim();
            if(searchValue==""){
                this.finalVisibleFoods = this.tagsFilteredFoods;
                this.search = '';
                return;
            }
            let tempArray = [];
            this.tagsFilteredFoods.forEach(fd => {
                if(fd.name.toLowerCase().includes(searchValue))
                tempArray.push(fd);
            });
            this.finalVisibleFoods = tempArray;
        },
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-tags?category=food', this.apiConfig).then(res => {
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
        addFoodPopup() {
            this.showAddFood = !this.showAddFood;
        },
        getAllFoods() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-foods', this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.allFoods = res.data.data;
                        this.finalVisibleFoods = this.allFoods;
                        this.tagsFilteredFoods = this.allFoods;
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = "Something went wrong";
                        this.informModal = true;
                        console.log("Error: fetching foods ", res.data.message);
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: fetching foods ", er);
                });
        },
        deleteids() {
            this.deletionIds = [];
        },
        getFoodDetails(){
            if(this.selectedFoods.length !== 1){
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select 1 food to edit.';
                this.informModal = true;
                return;
            }
            this.loaderText = 'Getting Food';
            this.pageLoading = true;
            axios.get(config.baseApiUrl+'food-full-details/'+this.selectedFoods[0],this.apiConfig).then(res => {
                this.pageLoading = false;
                if(res.data.status){
                    this.foodData = res.data.data;
                    this.foodEdit = true;
                    this.selectedFoods = [];
                }
                else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong!';
                this.informModal = true;
                console.log(" food edit error: ",er.message);
            });
        },
        fooddetailsshow(id) {
            this.FoodId = id;
            this.showFoodDetails = !this.showFoodDetails;
        },
        foodeditshow() {
            if (this.deletionIds.length == 0 || this.deletionIds.length > 1) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Number of selected foods to edit should only be one';
                this.informModal = true;
            }
            else {
                this.FoodId = this.deletionIds;
                this.showFoodDetails = !this.showFoodDetails;
                this.editFood = true;
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        insertIdArray(id) {
            const index = this.deletionIds.indexOf(id)
            if (index == -1)
                this.deletionIds.push(id);
            else
                this.deletionIds.splice(index, 1);
        },
        deleteFood() {
            if(this.selectedFoods.length < 1){
                this.modalTitle = 'No food selected!';
                this.modalDetail = 'Please select a food first';
                this.informModal = true;
                return;
            }
            const postData = {
                'ids': this.selectedFoods
            };
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.post(config.baseApiUrl + 'delete-food', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                this.deletionIds = [];
                postData.ids = [];
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = "Food Deleted Successfully";
                    this.informModal = true;
                    this.getAllFoods();
                    this.selectedFoods = [];
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in deleting food", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error deleting food: ", er);
            });
        },
    }
}
</script>
<style scoped>
.main {
    width: calc(100% - 20px);
    border: 1px solid;
    border-color: #E7E7E7;
    border-radius: 10px;
    height: 80vh;
    padding: 0;
    position: relative;
    height: calc(100vh - 125px);
}

.input1 {
    border: none;
    background-color: transparent;
}

.input1::placeholder {
    font-size: 9px;
}

.content-main {
    width: 98%;
    height: calc(100% - 50px);
    margin-left: auto;
    margin-right: auto;
}

.content-bar {
    width: 100%;
    height: 35px;
}

.content {
    width: 100%;
    height: calc(100% - 45px);
    margin-top: 5px;
    overflow-y: auto;
    overflow-x: hidden;
    border: 1px solid #E7E7E7;
    border-radius: 10px;
    padding-bottom: 10px;
}

.card-main {
    width: 100%;
    height: 90px;
    position: absolute;
    top: 60px;
    padding-top: 20px;
}

.card1 {
    height: 50px;
    width: 40px;
    text-align: center;
    border: none;
    border-radius: 5px;

}

.img1 {
    height: 20px;
    width: 20px;
}

.form-check-input {
    height: 20px;
    width: 20px;
    position: absolute;
    right: 10px;
    top: 5px;
    border: none;
    z-index: 5;
}

.content-p1 {
    font-size: 12px;
    font-weight: bold;
    color: #0A0A0A;
    position: absolute;
    width: 150px;
    cursor: pointer;
    top: 15px;
    left: 5px;
}

.content-p2 {
    font-size: 9px;
    color: #343434;
    position: absolute;
    top: 60px;
    left: 5px;
}

.card {
    width: 250px;
    height: 150px;
    border: none;
    float: left;
    margin-top: 20px;
    border-radius: 10px;
}

.btn1 {
    background-color: #F2A18C;
    border: none;
    width: 120px;
    height: 25px;
    font-size: 13px;
    font-weight: 100;
    float: left;
    padding: 0;
    margin: 5px 0px 0px 0px;
    border-radius: 3px;
}

.btn1:hover {
    background-color: black;
    color: #F2A18C;
}

.btn3 {
    background-color: #FFFFFF;
    border: none;
    width: 20px;
    height: 25px;
    float: left;
    margin: 5px 0px 0px 10px;
    border-radius: 3px;
}

/* .three-dots:after {
    cursor: pointer;
    content: '\2807';
    font-size: 18px;
    padding: 0px 0px 0px 0px;
} */

.inpname {
    width: 35%;
    background-color: inherit;
    font-size: 12px;
    color: #343434;
    border-color: #E7E7E7;
    border-radius: 10px;
    float: right;
    margin: 5px 8px 0px 0px;
    height: 25px;
}

.foot {
    width: 95%;
    height: 35px;
    position: absolute;
    bottom: 0;
    margin-bottom: 5px;
}

.itms {
    height: 30px;
    width: 120px;
    position: relative;
    top: 15px;
    left: 10px;
    float: left;
}

.p1 {
    font-size: 8px;
}

.pgin {
    height: 25px;
    width: 110px;
    border-radius: 30px;
    border: 1px solid #E7E7E7;
    position: relative;
    margin-top: 10px;
    margin-right: 10px;
    float: right;
}

.pginul1 {
    padding-top: 3px;
    height: 25px;
    width: 110px;
    padding-left: 0;
}
.img-as-bg{
    background-position: center;
    background-size: cover;
}

.pginli {
    float: left;
    list-style: none;
    margin-left: 9px;
    font-size: 11px;
}
</style>
