<template lang="">
     <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <div class="my-popup-component">
        <div class="col-6 brds-4 position-relative py-3" style="height:80%;overflow-y:auto;background-color:white;padding-right:3%;padding-left:3%;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0" style="font-size:37px">Add Group Member</p>
            <p class="m-0 ms-3" style="font-size:16px;color:#B1B0B0;">Basic members cannot be added to groups</p>
            <div class="w-100 mx-auto d-flex flex-wrap" style="height:calc(100% - 140px);overflow-y:auto;justify-content:space-between;">
                <div v-for="(item, index) in users" :key="index" class="px-4 py-2 mt-3 mb-2 position-relative" style="width:47%;height:50px;background-color:#EEEEEE;">
                    <input v-model="selectedUsers" type="checkbox" :value="item.id" class="form-check-input position-absolute" style="left:2px;top:2px;">
                    <img v-if="item.image==''" class="float-start" src="/cms-assets/images/navbar-topbar/user.jpg" alt="" style="height:30px;width:30px;border-radius:15px">
                    <img v-else class="float-start" :src="item.image" alt="" style="height:30px;width:30px;border-radius:15px">
                    <p class="float-start ms-2 mt-2" style="font-size:9px;">{{item.name}}</p>
                </div>
            </div>
            <button @click="addMember()" class="prim_bg px-4 py-1 mb-1 border-0 brds-2 mt-3">Add New</button>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Inform from '../inform.vue';
import Loader from '../loader.vue';
export default {
    components: { Inform, Loader },
    props: ['groupId'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            users: [],
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            pageLoading: false,
            loaderText: null,
            search: "",
            selectedUsers: []
        }
    },
    computed: {
        filteredUsers() {
            return this.users.filter((items) => {
                return items.name.toLowerCase().includes(this.search.toLowerCase());
            })
        },
        noOfUsers() {
            return this.selectedusers.length;
        }
    },
    mounted() {
        this.pageLoading = true;
        this.loaderText = 'Loading';
        axios.get(config.baseApiUrl + 'client-list-for-group/' + this.groupId, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.users = res.data.data;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                console.log(res.data.error);
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Failed!';
            this.modalDetail = 'Something Went Wrong';
            console.log(er.message);
            this.informModal = true;
        });
    },
    methods: {
        addMember() {
            if (this.selectedUsers.length > 0)
                this.$parent.addMembers(this.selectedUsers);
        },
        quitComponent() {
            this.$parent.groupMemberAdd = false;
        },
        acknowledged() {
            this.informModal = false;
        },
    },
}
</script>
<style lang="">

</style>
