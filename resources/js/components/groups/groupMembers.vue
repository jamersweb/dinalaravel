<template lang="">
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <div class="my-popup-component" @click.self="quitComponent" style="z-index:9999">
        <div class="bg-white px-3 py-5 brds-5 position-relative col-11 col-md-8 col-lg-6">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0" style="font-size:37px;text-align:center;">New Message</p>
            <div class="h-100 px-2 px-md-5">
                <p class="mb-0 ms-3" style="font-size:16px;color:#B1B0B0;">Recipients</p>
                <div class="position-relative my-2 gray_bg px-1 py-2 brds-2">
                    <input class="searchinput" type="search" placeholder="Select people to start a conversations with" v-model="search"/>
                    <!-- <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" /> -->
                </div>
                <div class="gray_bg p-3 brds-2">
                    <div class="border brds-2 bg-white" style="height:250px; overflow-y:auto">
                        <div class="my-2 mx-3" v-for="(item,index) in filteredUsers">
                            <input v-model="selectedUsers" type="checkbox" class="form-check-input" :value="item.id" :id="'check'+item.id"/>
                            <label class="ps-3 w-80 pointer trans_btn" :for="'check'+item.id">
                                <span>{{item.name}}</span>
                                <span class="float-end">{{item.sub}}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button @click="addMember()" class="prim_btn brds-2">Add Members</button>
                </div>
            </div>
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
        axios.get(config.baseApiUrl + 'client-list-for-group/'+this.groupId, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status)
            this.users = res.data.data;
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
        addMember(){
            if(this.selectedUsers.length>0)
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
<style scoped>
.searchinput {
    border: 1px solid #c5c5c5;
    border-radius: 11px;
    padding: 8px;
    width: 95%;
    padding-left: 25px;
    margin-left: 14px;
    font-size: 14px;
}

.searchab {
    position: absolute;
    width: 15px;
    top: 21px;
    left: 25px;
}
</style>
