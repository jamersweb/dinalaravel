<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <div class="brds-2 w-100" style="border:1px solid #E7E7E7;height:calc(100vh - 125px);overflow-y:auto;">
        <div style="width:100%;min-height:100px;position:relative;">
            <div class="float-start position-relative pe-2" style="height:100px">
                <img v-if="userData.picture" :src="userData.picture" alt="Error" style="width:80px;height:80px;margin-top:10px;margin-left:30px;float:left;position:relative;">
                <!-- <button class="position-absolute" style="bottom:-5px;left:80px;background-color:transparent;border:none;font-size:40px;">+</button> -->
            </div>
            <div class="float-start mt-2 ms-4">
                <p class="mb-0 ms-2" style="font-size:37px;">{{userData.fullname}}</p>
                <p class="mb-0 ms-3" style="font-size:14px;color:#B1B0B0;">{{userData.role}}</p>
            </div>
            <button class="float-start brds-4 tslin" style="position:absolute;top:30px;right:20px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;" @click="editProfile=true">Edit</button>
        </div>
        <div style="width:100%;min-height:300px;padding-top:15px;display:flex;justify-content:center;padding-bottom:20px;">
            <div class="brds-3 tsl float-start me-2" style="width:430px;min-height:270px;">
                <div class="w-80 mx-auto mt-3" style="height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Email:</p>
                    <p class="m-0 p2">{{userData.email}}</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Phone:</p>
                    <p class="m-0 p2">{{userData.phone}}</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">DOB: </p>
                    <p class="m-0 p2">{{userData.dob}}</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Sex:</p>
                    <p class="m-0 p2">{{userData.gender}}</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Added on:</p>
                    <p class="m-0 p2">{{userData.member_since}}</p>
                </div>
            </div>
            <div class="brds-3 tsl float-start ms-2" style="width:430px;min-height:270px;">
                <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Country:</p>
                    <p class="m-0 p2">{{userData.country}}</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                    <p class="m-0 pt-1 p1">Assigned Clients:</p>
                    <p class="m-0 p2">{{userData.clients}} clients</p>
                </div>
                <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between;margin-bottom:10px">
                    <p class="m-0 pt-1 p1">Trainers:</p>
                    <p class="m-0 p2">{{userData.trainers}} Trainers</p>
                </div>
            </div>
        </div>
    </div>
    <div v-if="editProfile" class="my-popup-component" @click.self="showEdit">
        <div class="brds-3 pb-3 position-relative" style="height:70vh;background-color:white;width:45%;overflow-y:auto">
            <button class="trans_btn position-absolute" @click="editProfile=false"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="my-3" style="text-align:center">Update Profile</h3>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Name</p>
                <input type="text" class="brds-2 tsl px-2" placeholder="Enter Name" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="full_name">
            </div>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Email</p>
                <input type="email" class="brds-2 tsl px-2" placeholder="Enter Email" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="postData.email">
            </div>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Enter New Password</p>
                <input type="password" class="brds-2 tsl px-2" placeholder="Enter Password" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="postData.password">
            </div>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Confirm New Password</p>
                <input type="password" class="brds-2 tsl px-2" placeholder="Confirm Password" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="passwordCnfrm">
            </div>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Phone</p>
                <input type="number" class="brds-2 tsl px-2" placeholder="Enter Phone Number" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="postData.phone">
            </div>
            <div class="w-100 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Country</p>
                <input type="text" class="brds-2 tsl px-2" placeholder="Enter Country" style="width:50%;height:30px;border:none;color:#B1B0B0;" v-model="postData.country">
            </div>
            <div class="w-100 mb-2 px-4 d-flex flex-wrap justify-content-between">
                <p style="font-size:17px;color:#B1B0B0;">Image</p>
                <div class="brds-2 tsl px-2 position-relative" style="width:50%;min-height:30px;border:none;color:#B1B0B0;">
                    <p class="mb-0" v-if="postData.image==null">Upload Image</p>
                    <p class="mb-0" style="word-break:break-all" v-else>{{postData.image.name}}</p>
                    <input @change="getImage()" type="file" ref="selectedImage" accept="image/*" style="opacity:0;width:100%;height:100%;position:absolute;z-index:999;top:0;left:0;">
                </div>
            </div>
            <div class="w-100" style="text-align:center;">
                <button class="brds-2 prim_bg border-0" style="height:30px;width:90px;" @click="post()">Save</button>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { Loader, Inform },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            pageLoading: false,
            loaderText: '',
            userData: {},
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            editProfile: false,
            full_name: null,
            passwordCnfrm: null,
            error: false,
            postData: {
                image: null,
                email: '',
                country: '',
                password: '',
                phone: '',
            },
            imageError: false,
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getProfile();
    },
    methods: {
        showEdit() {
            this.editProfile = !this.editProfile;
            this.postData.password = null;
            this.passwordCnfrm = null;
            this.postData.image = null;
        },
        getImage() {
            this.imageError = false;
            this.postData.image = this.$refs.selectedImage.files[0];
            if (!this.postData.image.type.includes("image")) {
                this.imageError = true;
            }
            else {
                this.imageError = false;
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        getProfile() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'my-profile', this.apiConfig).then(res => {
                this.pageLoading = false;
                this.userData = res.data.data;
                this.full_name = this.userData.fullname;
                this.postData.email = this.userData.email;
                this.postData.phone = this.userData.phone;
                this.postData.country = this.userData.country;
            })
                .catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Can not fetch data';
                    this.informModal = true;
                })
        },
        post() {
            let fd = new FormData();
            let postBody = {

            };
            if (this.imageError == true) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Selected file is not an Image';
                this.informModal = true;
                this.error = true
            }
            if (this.imageError !== true) {
                postBody.image = this.postData.image;
                fd.append('image', postBody.image);
            }
            if (this.userData.fullname != this.full_name) {
                let index = this.full_name.indexOf(" ");
                postBody.first_name = this.full_name.slice(0, index);
                postBody.last_name = this.full_name.slice(index + 1, this.full_name.length);
                fd.append('first_name', postBody.first_name);
                fd.append('last_name', postBody.last_name);
            }
            if (this.userData.email != this.postData.email) {
                postBody.email = this.postData.email;
                fd.append('email', postBody.email)
            }
            if (this.postData.password != '') {
                if (this.postData.password == this.passwordCnfrm) {
                    postBody.password = this.postData.password;
                    this.error = false;
                    fd.append('password', postBody.password);
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Please Enter Same Password';
                    this.informModal = true;
                    this.error = true;
                }
            }
            if (this.userData.phone != this.postData.phone) {
                postBody.phone = this.postData.phone;
                fd.append('phone', postBody.phone);
            }
            if (this.userData.country != this.postData.country) {
                postBody.country = this.postData.country;
                fd.append('country', postBody.country);
            }
            if (this.error == true) {
                return
            }
            this.loaderText = 'Uploading';
            this.pageLoading = true;
            axios.post(config.baseApiUrl + 'update-profile', fd, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = 'Profile Updated Successfully';
                    this.informModal = true;
                    this.getProfile();
                    this.editProfile = false;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = 'Cannot Update Profile';
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = 'Cannot Update Profile';
                this.informModal = true;
            })
        }
    }
}
</script>
<style scoped>
.p1 {
    font-size: 15px;
    float: left;
    width: 130px;
}

.p2 {
    font-size: 15px;
    float: right;
    width: 190px;
    padding: 6px 0px 5px 10px;
    color: #B1B0B0
}

input::placeholder {
    color: #B1B0B0;
}
</style>
