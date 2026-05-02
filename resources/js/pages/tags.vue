<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="w-100 brds-3 position-relative" style="height:calc(100vh - 125px);border:1px solid #C5C5C5;">
        <div class="w-100" style="border-top-right-radius:14px;border-top-left-radius:14px;background-color:#E7E7E7;height:50px;">
            <p class="ms-4 my-0 pt-1" style="font-size:26px;font-weight:bold;margin-top:7px;">Tags</p>
        </div>
        <div class="w-100" style="height:calc(100% - 50px);overflow-y:auto;">
            <div class="w-90 mx-auto my-3 d-flex flex-wrap justify-content-between">
                <div>
                    <p class="mb-0 float-start" style="font-size:20px;">Tags for:</p>
                    <div class="tsl float-start ms-2 brds-3 mt-1 px-3">
                        <select @change="getTags" v-model="tagsFor" class="border-0 brds-2" name="" id="" style="background-color:transparent;">
                            <option value="client">Clients</option>
                            <option value="program">Programs</option>
                            <option value="workout">Workouts</option>
                            <option value="exercise">Exercises</option>
                            <option value="meal">Meals</option>
                            <option value="food">Foods</option>
                        </select>
                    </div>
                </div>
                <button @click="showTagPopup()" class="px-4 prim_bg border-0 brds-2">Add a new tag</button>
            </div>
            <div class="mx-auto" style="width:95%;">
                <Vue3EasyDataTable
                    :headers="headers"
                    :items="items"
                    >
                    <template #item-actions="item">
                        <button @click="editTag(item)" class="border-0 brds-1 px-3 prim_bg" style="font-size:15px;">Edit</button>
                        <button @click="deleteTag(item.id)" class="border-0 brds-1 ms-4 px-3 prim_bg" style="font-size:15px;">Delete</button>
                    </template>
                </Vue3EasyDataTable>
            </div>
        </div>
    </div>
    <div v-if="addNewTag" @click.self="showTagPopup()" class="my-popup-component">
        <div class="brds-2 position-relative text-center" style="width:50vw;height:50vh;background-color:white;overflow-y:auto;">
            <button class="trans_btn float-end" @click="addNewTag=false" style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="my-3" style="font-size:25px;">Add New Tag</p>
            <div class="mx-auto w-90 mt-2 position-relative" style="height:37px;">
                <p class="mb-0 float-start" style="font-size:20px;">Tag Name:</p>
                <input v-model="postData.name" class="tsl brds-4 w-60 border-0 px-3 float-end" type="text" name="" id="">
            </div>
            <div class="mx-auto w-90 mt-2 position-relative" style="height:40px;">
                <p class="mb-0 float-start" style="font-size:20px;">Tags for:</p>
                <div class="tsl float-end w-60 ms-2 brds-3 mt-1 px-3">
                    <select v-model="postData.category" class="border-0 brds-2 w-100" name="" id="" style="background-color:transparent;">
                        <option value="">Select Tag type</option>
                        <option value="client">Clients</option>
                        <option value="program">Programs</option>
                        <option value="workout">Workouts</option>
                        <option value="exercise">Exercises</option>
                        <option value="meal">Meals</option>
                        <option value="food">Foods</option>
                    </select>
                </div>
            </div>
            <div class="mx-auto w-90 mt-2 position-relative" style="height:40px;">
                <p class="mb-0 float-start" style="font-size:20px;">Tag Type(if any):</p>
                <input v-model="type" class="tsl brds-4 w-60 border-0 px-3 float-end" type="text" name="" id="">
            </div>
            <div class="mt-2 mt-md-5">
                <button @click="saveTags()" class="prim_bg px-4 py-1 brds-2 border-0">Save</button>
            </div>
        </div>
    </div>
</template>
<script>
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
export default {
    components: { Vue3EasyDataTable, Loader, Inform },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            headers: [
                { text: "Name", value: "name", sortable: true },
                { text: "Type", value: "type", sortable: true },
                { text: "Action", value: "actions", sortable: true },
            ],
            items: [],
            addNewTag: false,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            type: null,
            saveType: 'new',
            tagsFor: 'client',
            postData: {
                name: null,
                category: '',
            }
        }
    },
    mounted() {
        this.getTags();
        this.$emit('adminCheckEvent');
    },
    methods: {
        showTagPopup() {
            this.postData.name = null;
            this.postData.category = '';
            this.saveType = 'new';
            this.type = null;
            this.addNewTag = !this.addNewTag;
        },
        editTag(m) {
            this.postData.name = m.name;
            this.postData.category = m.category;
            this.type = m.type;
            this.postData.id = m.id;
            this.addNewTag = true;
            this.saveType = 'old';
        },
        deleteTag(m) {
            this.pageLoading = true;
            this.loaderText = 'Deleting Tag';
            axios.get(config.baseApiUrl + 'delete-tag/' + m, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Tag deleted successfully';
                    this.informModal = true;
                    this.getTags();
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
        saveTags() {
            if (this.saveType == 'new') {
                this.createTag();
            }
            else {
                this.saveEditTag();
            }
        },
        saveEditTag() {
            if (this.postData.name == null || this.postData.name == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please enter the name for the tag';
                this.informModal = true;
                return
            }
            if (this.postData.category == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Select the Tag for';
                this.informModal = true;
                return
            }
            if (this.type !== null) {
                this.postData.type = this.type;
            }
            this.pageLoading = true;
            this.loaderText = 'Updating Tag';
            axios.post(config.baseApiUrl + 'update-tag', this.postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.type = null;
                    this.postData.name = null;
                    this.postData.category = '';
                    this.addNewTag = false;
                    this.getTags();
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
        createTag() {
            if (this.postData.name == null || this.postData.name == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please enter the name for the tag';
                this.informModal = true;
                return
            }
            if (this.postData.category == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Select the Tag for';
                this.informModal = true;
                return
            }
            if (this.type !== null) {
                this.postData.type = this.type;
            }
            this.pageLoading = true;
            this.loaderText = 'Creating Tag';
            axios.post(config.baseApiUrl + 'create-tag', this.postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.type = null;
                    this.postData.name = null;
                    this.postData.category = '';
                    this.addNewTag = false;
                    this.getTags();
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
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-uncategorized-tags?category=' + this.tagsFor, this.apiConfig).then(res => {
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
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style lang="">

</style>
