<template lang="">
    <div class="my-popup-component">
            <div v-if="addProduct1" class="brds-5 ps-4 pt-3 position-relative" style="width:70%;height:80vh;background-color:white;overflow:auto;padding:10px;">
                <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="quitcomponent">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="w-100" style="min-height:290px;">
                    <div class="px-3 col-8 float-start" style="min-height:260px">
                        <p class="mt-2" style="font-size:26px;margin:0px;">Product name</p>
                        <div class="brds-2 px-3 py-3" style="height:75px;max-width:550px;width:100%;background-color:#F7F7F7;">
                            <input v-model="postData.name" type="text" placeholder="Add name of product" class="brds-2 ps-3" style="font-size:13px;background-color:white;border:1px solid #C5C5C5;height:40px;width:99%;">
                        </div>
                        <p class="mt-2" style="font-size:26px;margin:0px;">Description</p>
                        <textarea v-model="postData.description" cols="60" rows="5" class="tsllight brds-2" style="max-width:550px;width:100%;border:none;font-size:13px;background-color:#F7F7F7;"></textarea>
                    </div>
                    <div class="col-4 float-start mt-3 pt-1 position-relative" style="min-height:260px;">
                        <p style="font-size:26px;margin:0px;margin-left:-15px;">Product image</p>
                        <input ref="selctedImage" @change="imageFile" type="file" accept="image/*" style="height:0px;width:0px;opacity:0;z-index:99999;position:absolute;">
                        <!-- <p v-if="imageURL==null" style="margin:0px;color:#C5C5C5;position:absolute;top:80px;">Add image file for the product</p> -->
                        <img v-if="imageURL!==null" :src="imageURL" alt="" style="height:175px;width:175px;border-radius:10px;">
                        <div style="height:50px;width:100%;margin-top:10px;">
                            <button class="prim_bg brds-2 border-0 px-2 py-1" style="margin-left:12%;" @click="addImg">Add Image</button>
                        </div>
                    </div>
                </div>
                <div class="px-3" style="width:100%;height:85px;">
                    <div style="height:85px;width:auto;float:left;">
                        <p style="margin:0px;color:#B1B0B0;font-size:16px;">Access Type</p>
                        <div class="brds-1" style="height:55px;width:auto;background-color:#F7F7F7;border:none;padding:12px;">
                            <select v-model="postData.access_type" class="brds-1" style="height:30px;width:170px;border-color:#EDEDED;font-size:12px;color:#B1B0B0;">
                                <option selected value="half_access">Half Access</option>
                                <option value="full_access">Full Access</option>
                            </select>
                        </div>
                    </div>
                    <div class="ms-3" style="height:85px;width:auto;float:left;">
                        <p style="margin:0px;color:#B1B0B0;font-size:16px;">Payment</p>
                        <div class="brds-1" style="height:55px;width:auto;background-color:#F7F7F7;border:none;padding:12px;">
                            <input v-model="postData.price" class="float-start inp1" type="number" style="height:30px;width:70px;color:#B1B0B0;font-size:12px;border:1px solid #EDEDED;" placeholder="Amount">
                            <p class="float-start mb-0 ms-2 mt-1 me-2" style="font-size:12px;color:#B1B0B0;">every month</p>
                        </div>
                    </div>
                </div>
                <div class="w-100 float-start py-4" style="text-align:center;">
                    <button v-if="preData==null" @click="post" class="brds-2 me-2" style="height:40px;width:180px;border:none;background-color:#F2A18C;font-size:16px;">Save</button>
                    <button v-else @click="editProduct" class="brds-2 me-2" style="height:40px;width:180px;border:none;background-color:#F2A18C;font-size:16px;">Save</button>
                </div>
            </div>
            <!-- <div v-if="automation" class="position-relative brds-5" style="width:80%;height:90%;background-color:white;overflow:auto;text-align:center">
                <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="quitcomponent">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="w-100" style="min-height:150px;text-align:center">
                    <p class="m-0 mx-auto mt-5" style="font-size:26px;max-width:75%;line-height:25px;">Set up automation for Home workout Programs & Coaching
                        /برامج التمارين المنزلية و التدريب اونلاين</p>
                    <p class="m-0 mx-auto mt-2 mb-3" style="font-size:18px;max-width:65%;color:#C5C5C5;line-height:20px;">Streamline your client setup and program delivery by adding automation to your product. Choose the actions you’d like to happen automatically and when you’d like them to happen and we'll take care of the rest.</p>
                    <button @click="firstPurchase=true,productStarts=false,productEnds=false" class="btn-1 brds-2 mb-3" :class="{active2:firstPurchase}" >First Purchase</button>
                    <button @click="productStarts=true,productEnds=false,firstPurchase=false;" class="btn-1 brds-2 mx-3 mb-3" :class="{active2:productStarts}">Product starts</button>
                    <button @click="productEnds=true,productStarts=false,firstPurchase=false" class="btn-1 brds-2 mb-3" :class="{active2:productEnds}">Product ends</button>
                </div>
                <div v-if="firstPurchase" class="mx-auto tsl brds-3 p-3" style="min-height:290px;width:98%;text-align:start;">
                    <p class="m-0" style="font-size:26px;font-weight:bold;">When a New Client Buys Their First Product</p>
                    <p class="mb-3" style="font-size:18px; width:80%;color:#C5C5C5;">This is triggered when a new client purchases their first product. To avoid possible conflicts, automation will not run for purchases made by existing clients.</p>
                    <div class="w-100 d-flex flex-wrap" style="justify-content:space-between">
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Change client type</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Assign to a trainer</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Subscribe to a main program</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Copy to client's custom program</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Tag the clients</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Attach meal plan PDF</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Join group (up to 3)</p>
                            <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div v-if="productStarts" class="mx-auto tsl brds-3 p-3" style="min-height:290px;width:98%;text-align:start;">
                    <p class="m-0" style="font-size:26px;font-weight:bold;">When the Product Starts</p>
                    <p class="mb-3" style="font-size:18px; width:80%;color:#C5C5C5;">This is triggered when the product starts. If you’ve selected a fixed start date, automation will run for all clients at the same time.
                        Otherwise, automation may run on different days for different clients depending on when they made their purchase.</p>
                    <div class="w-100 d-flex flex-wrap" style="justify-content:space-between">
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Change client type</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Assign to a trainer</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Subscribe to a main program</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Copy to client's custom program</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Tag the clients</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Attach meal plan PDF</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Join group (up to 3)</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                    </div>
                </div>
                <div v-if="productEnds" class="mx-auto tsl brds-3 p-3" style="min-height:290px;width:98%;text-align:start;">
                    <p class="m-0" style="font-size:26px;font-weight:bold;">When the Product Ends/Expires</p>
                    <p class="mb-3" style="font-size:18px; width:80%;color:#C5C5C5;">These actions are triggered when the product ends, either by expiring, being manually canceled, or being
                        automatically canceled because of a payment failure.</p>
                    <div class="w-100 d-flex flex-wrap" style="justify-content:space-between">
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Change client type</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Assign to a trainer</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Subscribe to a main program</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Copy to client's custom program</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Tag the clients</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Attach meal plan PDF</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                        <div style="width:500px" class="float-start">
                            <input class="form-check-input float-start ms-3" type="checkbox">
                            <p class="float-start ms-3" style="font-size:15px;">Join group (up to 3)</p> -->
                            <!-- <select class="brds-1 float-end me-4" style="height:30px;width:170px;border-color:#C5C5C5;font-size:12px;color:#C5C5C5;">
                                <option>Full Access</option>
                            </select> -->
                        <!-- </div>
                    </div>
                </div>
                <button class="btn-1 brds-2 mt-4 mb-3" style="border:none;background-color:#F2A18C;">Save</button>
            </div> -->
        </div>
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { Loader, Inform },
    props: ['preData'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            addProduct1: true,
            automation: false,
            firstPurchase: false,
            productStarts: false,
            productEnds: false,
            imageError: false,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            imageURL: null,
            postData: {
                name: null,
                description: null,
                price: null,
                access_type: 'one_way_msg',
                image: null,
            }
        }
    },
    mounted() {
        if (this.preData !== null) {
            this.postData.name = this.preData.name;
            this.postData.access_type = this.preData.access_type;
            this.postData.price = this.preData.price;
            this.postData.description = this.preData.description;
            this.imageURL = this.preData.image;
        }
    },
    methods: {
        imageFile() {
            this.imageError = false;
            this.postData.image = this.$refs.selctedImage.files[0];
            if (!this.postData.image.type.includes("image")) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Please select an image file';
                this.informModal = true;
                this.imageError = true;
            }
            else {
                this.imageError = false;
                this.imageURL = URL.createObjectURL(this.postData.image);
            }
        },
        post() {
            if (this.postData.name == null || this.postData.name == '' || this.postData.description == null || this.postData.description == '' || this.postData.price == null || this.postData.image == null) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All fields are rquired please fill them all';
                this.informModal = true;
                return
            }
            if (this.imageError == true) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'File selected is not an Image';
                this.informModal = true;
                return
            }
            if (this.postData.price < 1) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Price cannot be less than 1$';
                this.informModal = true;
                return
            }
            else {
                let fd = new FormData();
                fd.append("name", this.postData.name);
                fd.append("price", this.postData.price);
                fd.append("description", this.postData.description);
                fd.append("access_type", this.postData.access_type);
                if (this.postData.image !== null) {
                    fd.append("image", this.postData.image);
                }
                this.pageLoading = true;
                this.loaderText = 'Uploading';
                axios.post(config.baseApiUrl + 'create-product', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$parent.addProductPopup();
                        this.$parent.showProducts();
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
        addImg() {
            this.$refs.selctedImage.click();
        },
        editProduct() {
            if (this.postData.name == null || this.postData.name == '' || this.postData.description == null || this.postData.description == '' || this.postData.price == null || this.postData.price == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All fields are rquired please fill them all';
                this.informModal = true;
                return
            }
            if (this.imageError == true) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'File selected is not an Image';
                this.informModal = true;
                return
            }
            else {
                let fd = new FormData();
                fd.append("name", this.postData.name);
                fd.append("price", this.postData.price);
                fd.append("description", this.postData.description);
                fd.append("access_type", this.postData.access_type);
                if (this.postData.image !== null) {
                    fd.append("image", this.postData.image);
                }
                fd.append("id", this.preData.id);
                this.pageLoading = true;
                this.loaderText = 'Editing';
                axios.post(config.baseApiUrl + 'edit-product', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$parent.addProductPopup();
                        this.$parent.showProducts();
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
        quitcomponent() {
            this.$parent.addProductPopup();
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.active2 {
    background-color: #F2A18C !important;
    border: none !important;
}

.btn-1 {
    height: 40px;
    width: 180px;
    font-size: 16px;
    background-color: transparent;
    border: 1px solid #343434;
}
</style>
