<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="my-popup-component">
        <div class="main-box position-relative pb-2">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 style="font-size:37px;margin-top:20px;margin-bottom:0px;color:#343434;">Add to Group</h5>
            <p style="font-size:16px;color:#B1B0B0;">Basic members cannot be added to group</p>
            <div class="mx-auto brds-3" style="width:80%;height:70px;background-color:#F7F7F7;padding-top:15px;">
                <div class="brds-2 mx-auto position-relative" style="width:90%;height:40px;border:1px solid #C5C5C5;background-color:white;">
                    <input v-model="search" type="text" class="brds-2 float-start" style="width:calc(100% - 25px);height:37px;border:none;">
                    <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" style="width:20px;height:20px;top:10px;right:5px;" class="img-fluid position-absolute">
                </div>
            </div>
            <div class="w-80 tsl px-1 py-2 mx-auto mt-1 brds-2 position-relative" style="height:80px;overflow-y:auto;background-color:white;">
                <p style="margin-top:30%" v-if="searchedGroup.length==0">No Group </p>
                <p @click="addToGroup(item.id)" class="group" v-for="(item, index) in searchedGroup" :key="index">{{item.name}}</p>
            </div>
            <button class="mt-3 prim_bg brds-2" style="font-size:16px;width:180px;height:40px;border:none;">Add</button>
        </div>
    </div>
</template>
<script>
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import config from '../../config';
import axios from 'axios';
export default {
    name: 'addToGroup',
    components: { Loader, Inform },
    props: ['selectedUsers'],
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
            groups: [],
            search: ''
        }
    },
    mounted() {
        this.getAllGroups();
    },
    computed: {
        searchedGroup() {
            return this.groups.filter((item) => {
                return item.name.toLowerCase().includes(this.search.toLowerCase())
            })
        }
    },
    methods: {
        quitComponent(m) {
            this.$parent.closeAddToGroup(m);
        },
        getAllGroups() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'my-groups', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.groups = res.data.data;
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
        addToGroup(id) {
            let postData = {
                user_ids: this.selectedUsers,
                group_id: id,
            }
            this.pageLoading = true;
            this.loaderText = 'Adding';
            axios.post(config.baseApiUrl + 'add-members', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.quitComponent(1);
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
<style scoped>
.main-box {
    width: 45%;
    min-height: 300px;
    border-radius: 15px;
    background-color: #FFFFFF;
    text-align: center;
}

.group {
    width: 100%;
    height: 25px;
    text-align: center;
    cursor: pointer;
    font-size: 15px;
    margin: 0;
}

.group:hover {
    background-color: #EEEE;
}
</style>
