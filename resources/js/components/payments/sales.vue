<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div style="padding:5px;height:calc(100vh - 210px);overflow:auto;">
        <Vue3EasyDataTable :headers="headers" :items="items"
            :search-field="searchField"
            :search-value="searchValue">
            <template #item-product="item">
                <button @click="showDetails(item)" style="border:none;background-color:transparent;">{{item.product}}</button>
            </template>
        </Vue3EasyDataTable>
    </div>
    <div v-if="purchaseDetails" class="my-popup-component">
        <div class="brds-4 position-relative" style="width:80%;height:90vh;background-color:white;overflow-y:auto">
            <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="showDetails">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="w-90 mx-auto d-flex justify-content-between mt-5">
                <p class="m-0" style="font-size:26px;">Purchase Detail</p>
                <!-- <button class="brds-2 prim_bg " style="width:180px;height:40px;border:none;font-size:16px;">Delete</button> -->
            </div>
            <div class=" mx-auto d-flex flex-wrap" style="min-height:300px;justify-content:space-between;width:95%;">
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Purchased By:</p>
                    <input readonly type="text" v-model="detailsToShow.client" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Product:</p>
                    <input readonly type="text" v-model="detailsToShow.product" style="width:100% !important;height: 40px;font-size: 13px;color: #C5C5C5;border: 1px solid #C5C5C5;" class="ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Status:</p>
                    <input readonly type="text" v-model="detailsToShow.status" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Discount Code:</p>
                    <input readonly type="text" placeholder="None" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Date Added:</p>
                    <input readonly type="text" v-model="detailsToShow.date_added" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Product Start:</p>
                    <input readonly type="text" v-model="detailsToShow.date_start" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Product End:</p>
                    <input readonly type="text" v-model="detailsToShow.date_end" class="inp2 ps-2 brds-2">
                </div>
                <div style="width:50%;height:65px;">
                    <p class="m-1 ms-2" style="font-size:13px;">Next Invoice:</p>
                    <input readonly type="text" v-model="detailsToShow.next_inv" class="inp2 ps-2 brds-2">
                </div>
            </div>
            <!-- <div class="w-90 mx-auto">
                <p  style="font-size:26px;font-weight:bold;">Invoices</p>
                <DataTable
                    class="display" width="100%" :data="[]">
                    <thead>
                        <tr>
                            <th>Invoice No.</th>
                            <th>Billed On</th>
                            <th>Client</th>
                            <th>Product Name</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </DataTable>
            </div> -->
        </div>
    </div>
    <div v-if="filters" class="my-popup-component">
        <div class="w-60 brds-4 position-relative" style="height:90vh;background-color:white;text-align:center;overflow-y:auto;">
            <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="quitcomponent">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mx-auto mt-4" style="font-size:23px;width:525px;">Sell Home workout Programs & Coaching /برامج التمارين المنزلية و التدريب اونلاين</p>
            <div class="brds-2 mx-auto" style="width:445px;height:105px;background-color:#F7F7F7;text-align:center;">
                <p class="mb-2 pt-3" style="font-size:12px;font-weight:bold;color:#343434;">Which clients do you wish to sell the product to?</p>
                <input type="text" class="inp3 brds-1 mt-1 ps-3" placeholder="Enter one or more clients">
            </div>
            <p class="my-1" style="font-size:20px">Select a product to sell</p>
            <div class="mx-auto position-relative" style="width:380px;height:130px;">
                <button class="brds-1 mx-auto" style="width:365px;height:50px;text-align:center;background-color:#F2A18C;font-size:21px;border:none">Select Product</button>
                <div class="brds-1 position-absolute" style="width:380px;height:80px;bottom:5px;z-index:9999!important;background-color:white;box-shadow: 0 0 10px 0px #F2A18C inset;">
                    <div class="float-start" style="width:290px;text-align:start">
                        <p class="mb-0 ms-2 mt-1" style="font-size:15px;font-weight:bold;">Home workout Programs & Coaching/برامج التمارين المنزلية و التدريب اونلاين</p>
                        <p class="mb-0 ms-2 mt-1" style="font-size:11px;">Price: $29.99 USD / month</p>
                    </div>
                    <img class="mt-1" src="/images/getFile.png" alt="Error" style="height:65px;width:65px;float:right;">
                </div>
            </div>
            <p class="mb-0 mt-2" style="font-size:20px;">Set product start date</p>
            <div class="mx-auto" style="width:350px;height:140px;">
                <div class="my-1 w-100" style="height:30px">
                    <input type="checkbox" class="form-check-input float-start" style="height:25px;width:25px;">
                    <p class="mb-0 ms-3 float-start" style="font-size:15px;margin-top:5px;">Today</p>
                </div>
                <div class="my-1 w-100" style="height:30px">
                    <input type="checkbox" class="form-check-input float-start" style="height:25px;width:25px;">
                    <p class="mb-0 ms-3 float-start" style="font-size:15px;margin-top:5px;">Next week Starts 25 Jul 2022 (Monday)</p>
                </div>
                <div class="my-1 w-100" style="height:30px">
                    <input type="checkbox" class="form-check-input float-start" style="height:25px;width:25px;">
                    <p class="mb-0 ms-3 float-start" style="font-size:15px;margin-top:5px;">Selected Date</p>
                </div>
                <div class="my-1 w-100" style="height:30px">
                    <input type="checkbox" class="form-check-input float-start" style="height:25px;width:25px;">
                    <p class="mb-0 ms-3 float-start" style="font-size:15px;margin-top:5px;">After current product</p>
                </div>
            </div>
                <p class="my-1" style="font-size:20px;">Apply discount code?</p>
                <input type="text" class="brds-2 inp4" placeholder="No-Discount"><br>
                <button class="brds-2 mt-3 mb-3" style="width:250px;height:50px;font-size:21px;background-color:#F2A18C;border:none;">Send</button>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
export default {
    components: { Loader, Inform, Vue3EasyDataTable },
    props: ['searchValue'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            detailsToShow: null,
            purchaseDetails: false,
            filters: false,
            searchField: ref("client"),
            headers: [
                { text: "Product Name", value: "product", sortable: true },
                { text: "Client", value: "client", sortable: true },
                { text: "Product Start", value: "date_start", sortable: true },
                { text: "Product End", value: "date_end", sortable: true },
                { text: "Status", value: "status", sortable: true },
            ],
            items: []
        }
    },
    mounted() {
        this.pageLoading = true;
        this.loaderText = 'Fetching';
        axios.get(config.baseApiUrl + 'payments-sales-data', this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.items = res.data.data;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Error';
            this.modalDetail = er;
            this.informModal = true;
        })
    },
    methods: {
        showDetails(m) {
            if (this.purchaseDetails == false) {
                this.detailsToShow = m;
            }
            this.purchaseDetails = !this.purchaseDetails;
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style scoped>
.inp2 {
    max-width: 380px;
    width: 100%;
    height: 40px;
    font-size: 13px;
    color: #C5C5C5;
    border: 1px solid #C5C5C5;
}

.inp2::placeholder {
    font-size: 13px;
    color: #C5C5C5;
}
</style>
