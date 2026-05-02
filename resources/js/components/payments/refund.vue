<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div style="padding:5px;height:calc(100vh - 210px);overflow:auto;">
        <Vue3EasyDataTable :headers="headers" :items="items"
        :search-field="searchField"
            :search-value="searchValue">
            <template #item-amount_paid="item">
                <p class="mb-0">${{item.amount_paid}}</p>
            </template>
            <template #item-status="item">
                <p class="mb-0 px-1 py-1 brds-4 text-center" style="background-color:red;color:white;" v-if="item.status=='rejected'">{{item.status}}</p>
                <p class="mb-0 px-1 py-1 brds-4 text-center" style="background-color:orange;color:white;" v-if="item.status=='applied'">{{item.status}}</p>
                <p class="mb-0 px-1 py-1 brds-4 text-center" style="background-color:green;color:white;" v-if="item.status=='approved'">{{item.status}}</p>
                <p class="mb-0 px-1 py-1 brds-4 text-center" style="background-color:grey;color:white;" v-if="item.status=='expired'">{{item.status}}</p>
            </template>
            <template #item-actions="item">
                <div class="d-flex">
                    <button v-if="item.approvable" @click="approvalPopup(item)" class="mb-0 prim_bg px-2 border-0 brds-1 py-1 mx-1 float-start">Approve</button>
                    <button v-if="item.rejectatble" @click="rejectRefund(item,1)" class="mb-0 px-2 border-0 brds-1 py-1 mx-1 float-start" style="box-shadow:0px 0px 5px 0px #F2A18C;background-color:white;">Reject</button>
                </div>
            </template>
        </Vue3EasyDataTable>
    </div>
    <div v-if="refundPopup" @click.self="quitComponent" class="my-popup-component">
        <div class="col-6 position-relative brds-3 text-center" style="height:300px;overflow-y:auto;background-color:white">
            <button class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px" @click="quitComponent">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mb-0 mt-2" style="font-size:25px;font-weight:bold;"><span v-if="popupType=='reject'">Reject Refund</span><span v-else>Approve Refund</span></p>
            <div v-if="popupType=='reject'" class="mt-5">
                <textarea v-model="postData.description" class="col-11 mx-auto brds-2" style="height:80px;" placeholder="Enter description for the rejection"></textarea>
                <button @click="rejectRefund(null,0)" class="prim_btn brds-3 px-5 mt-5">Reject</button>
            </div>
            <div class="py-3" v-else>
                <div class="col-11 mx-auto d-flex flex-wrap justify-content-around">
                    <div class="col-12 mt-1">
                        <p class="col-4 float-start mb-0">Amount paid: </p>
                        <p class="col-7 float-end mb-0 text-start">{{amountPaid}}</p>
                    </div>
                    <div class="col-12 mt-1">
                        <p class="col-4 float-start mb-0">Amount to refund: </p>
                        <div class="col-8 float-end">
                            <select class="col-5 brds-1 float-start" style="border:1px solid #C5C5C5;color:#C5C5C5" name="" id="" v-model="refundType">
                                <option value="full">Full refund</option>
                                <option value="partial">Partial refund</option>
                            </select>
                            <input v-if="refundType=='partial'" class="brds-1 col-3 ms-4 float-start px-2" style="border:1px solid #C5C5C5;" v-model="postData1.refund_amount" type="number" placeholder="Enter amount">
                        </div>
                    </div>
                </div>
                <!-- <input class="mt-2 brds-1 border-0 tsl" v-if="refundType=='partial'" v-model="postData1.refund_amount" type="text" placeholder="Enter amount for partial refund"> -->
                <textarea class="col-11 mx-auto mt-2 brds-2 p-1" v-model="postData1.description" style="height:80px;border:1px solid #C5C5C5;" placeholder="Enter description for the rejection"></textarea>
                <button @click="approveRequest()" class="prim_btn brds-3 mt-4 px-5">Approve</button>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { Vue3EasyDataTable, Loader, Inform },
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
            refundPopup: false,
            popupType: null,
            refundType: 'full',
            refundAmount: null,
            searchField: ref("user_name"),
            headers: [
                { text: "Name", value: "user_name", sortable: true },
                { text: "Amount Paid", value: "amount_paid", sortable: true },
                { text: "Days passed", value: "days_passed", sortable: true },
                { text: "Amount Refunded", value: "refundable_amount", sortable: true },
                { text: "Subscription", value: "for_sub", sortable: true },
                { text: "Status", value: "status", sortable: true },
                { text: "Created at", value: "time", sortable: true },
                { text: "Actions", value: "actions", sortable: true },
            ],
            items: [],
            amountPaid: null,
            postData: {
                refund_id: null,
                description: '',
            },
            postData1: {
                refund_id: null,
                description: '',
                refund_amount: null,
            }
        }
    },
    mounted() {
        this.getAllRefunds();
    },
    methods: {
        getAllRefunds() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'refund-requests', this.apiConfig).then(res => {
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
        quitComponent() {
            this.postData.refund_id = null;
            this.postData.description = null;
            this.amountPaid = null;
            this.postData1.description = '';
            this.postData1.refund_id = null;
            this.postData1.refund_amount = null;
            this.refundPopup = false;
        },
        rejectRefund(m, n) {
            this.popupType = 'reject';
            if (n == 1) {
                this.postData.refund_id = m.id;
                this.refundPopup = true;
            }
            else {
                this.pageLoading = true;
                this.loaderText = 'Rejecting';
                axios.post(config.baseApiUrl + 'reject-refund-request', this.postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.getAllRefunds();
                        this.quitComponent();
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
            }
        },
        approveRequest() {
            if (this.refundType == 'partial') {
                if (this.postData1.refund_amount > this.amountPaid) {
                    this.modalDetail = 'Error';
                    this.modalDetail = 'Refund amount cannot be greater than amount paid';
                    this.informModal = true;
                    return
                }
                if (this.postData1.refund_amount == null || this.postData1.refund_amount == '') {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Please enter ammount to refund';
                    this.informModal = true;
                    return
                }
            }
            else {
                this.postData1.refund_amount = this.amountPaid;
            }
            this.pageLoading = true;
            this.loaderText = 'Approving';
            axios.post(config.baseApiUrl + 'approve-refund-request', this.postData1, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.getAllRefunds();
                    this.quitComponent();
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
        approvalPopup(m) {
            this.postData1.refund_id = m.id;
            this.amountPaid = m.amount_paid;
            this.refundPopup = true;
            this.popupType = 'accept';
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style>

</style>
