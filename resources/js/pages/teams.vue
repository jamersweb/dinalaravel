<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="brds-4" style="width:99%;min-height:490px;border:1px solid #E7E7E7">
        <!-- <div class="border-end float-start position-relative" style="width:260px;min-height:488px;border-color:#E7E7E7;background-color:#F7F7F7;border-top-left-radius:18px;border-bottom-left-radius:20px;">
            <p class="m-1 ms-3" style="font-size:20px;font-weight:bold;">TEAM MEMBERS</p>
            <p class="m-1 ms-3" style="font-size:12px;color:#B1B0B0">View and filter your trainers by location using the drop-down below.</p>
            <div class="position-relative ms-2">
                <input type="text" class="brds-2 ps-4" placeholder="Search within this location" style="color:#C5C5C5;border:1px solid #C5C5C5;width:97%;height:35px;font-size:10px;" />
                <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute" style="top:8px;left:5px;height:20px;" />
            </div>
            <p class="m-1 ms-3" style="font-size:12px;">Choose location:</p>
            <select style="width:93%;height:35px;color:#606060;border:1px solid #C5C5C5;font-size:10px;" class="ms-2 brds-2">
                <option value="">Fitness with Dina</option>
            </select>
            <div class="position-absolute" style="width:100%;bottom:20px;">
                <p class="ms-3 mb-2" style="font-size:12px;color:#B1B0B0;">Add a new business location</p>
                <button class="ms-3 brds-1 px-3 py-1 border-0 prim_bg" style="font-size:9px;">Setup now</button>
            </div>
        </div> -->
        <div class="float-start" style="width:100%;min-height:490px;border-radius:18px;">
            <div style="width:100%;height:42px;border-top-right-radius:19px;border-top-left-radius:18px;background-color:#EEEEEE;">
                <!-- <button class="px-3 ms-2 prim_bg" style="border: none;font-size:12px;border-radius:7px;height:22px;margin-top:10px;">Summary</button> -->
                <!-- <button class="px-3 border-start-0 border-end-0" style="background-color:white;border: 1px solid #C5C5C5;font-size:12px;height:22px;">Client Compliance</button>
                <button class="px-3" style="background-color:white;border: 1px solid #C5C5C5;font-size:12px;border-top-right-radius:7px;border-bottom-right-radius:7px;height:22px;">Client Engagement</button> -->
                <div class="float-end px-1 brds-2 " style="background-color:white;border:1px solid #C5C5C5;margin-top:7px;margin-right:10px;">
                    <img src="/cms-assets/images/navbar-topbar/search.png" alt="" style="height:15px;">
                    <input type="text" class="float-end border-0" style="font-size:12px;height:24px;" v-model="searchValue" placeholder="Search for a team">
                </div>
            </div>
            <div class="d-flex justify-content-between px-3 pt-3" style="width:100%;min-height:60px;">
                <div>
                    <button class="prim_btn py-1 px-3 rounded-1 float-start ms-2" style="font-size:13px;" @click="newTeam()">New</button>
                    <button @click="deleteTeamMember" class="py-1 px-3 rounded-1 float-start ms-2 position-relative" style="height:28px;width:26px;border:1px solid #C5C5C5;background-color:white;"><img src="/cms-assets/images/teams/Group57966.png" alt="" style="position:absolute;top:6px;left:11px;"></button>
                    <!-- <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                        <button class="three-dots2 tsl" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item" @click="resetPassword=true">Reset Password</button></li>
                            <li><button class="dropdown-item">Assign to a location</button></li>
                            <li><button class="dropdown-item">Change role</button></li>
                            <li><button class="dropdown-item">Reassign all clients to another trainer</button></li>
                            <li><button class="dropdown-item">Move all Master Programs/Workouts to another trainer</button></li>
                        </ul>
                    </div> -->
                </div>
                <!-- <div>
                    <select class="brds-1" style="height:28px;width:170px;border-color:#EDEDED;font-size:12px;">
                        <option>Name</option>
                    </select>
                </div> -->
            </div>
            <div class="mx-auto" style="width:97%;max-height:370px;border:1px solid #E7E7E7;overflow-y:auto;">
                <Vue3EasyDataTable v-if="summary && items!=[]"
                    :headers="headers"
                    :items="items"
                    :search-field="searchField"
                    :search-value="searchValue"
                    v-model:items-selected="itemsSelected"
                    >
                    <template #item-name="item">
                        <img :src="item.userdetails.picture" style="height:30px;width:30px;border-radius:15px;">
                        <button @click="getTeamMemberDetail(item.id)" style="border:none;background-color:transparent;">{{item.full_name}}</button>
                    </template>
                </Vue3EasyDataTable>
            </div>
        </div>
    </div>
    <div v-if="resetPassword" class="my-popup-component">
        <div class="brds-4 position-relative" style="min-height:430px;width:740px;background-color:white;text-align:center;">
            <button class="trans_btn position-absolute" @click="resetPassword=false"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mb-0 mx-auto" style="font-size:37px;line-height:40px; width:560px;margin-top:70px;">You are about to reset the passwords for the selected trainers</p>
            <p class="mt-4 mb-0 mx-auto" style="font-size:16px;width:420px;color:#B1B0B0;">Do you want to leave "random"? Because you're the last member,leaving will also delete this group</p>
            <button class="brds-2 mt-4" style="width:250px;height:53px;background-color:#F2A18C;border:none;font-size:21px;">Reset Password</button>
        </div>
    </div>
    <newTeamMember v-if="newTeamMemberShow"/>
    <teamPopup v-if="teamMembersProfile" :user_id="idForDetails"/>
    <div v-if="showdetails" class="my-popup-component" @click.self="showdetails=false">
        <div class="brds-4 position-relative pb-3" style="height:90vh;width:80%;background-color:white;overflow:auto;">
        </div>
        <div v-if="reassignClients" class="my-popup-component">
            <div class="brds-5 pb-3 position-relative" style="height:75vh;width:50%;background-color:white;text-align:center;overflow:auto;">
                <button class="trans_btn position-absolute" @click="Reassign()"
                    style="right:15px;top:10px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <p class="mb-0 mt-3" style="font-size:37px;">Reassign All Clients</p>
                <p class="mb-0 mt-2 mx-auto" style="font-size:16px;color:#B1B0B0;width:350px">All clients under the selected trainers will be reassigned to the trainer selected below.</p>
                <p class="mb-0 mt-2 mx-auto" style="font-size:16px;color:#B1B0B0;width:300px">Select the trainer you want to reassign the clients to</p>
                <div class="mx-auto brds-3 mt-2 d-flex justify-content-around flex-wrap" style="min-height:95px;width:90%;background-color:#F7F7F7;align-items:center;">
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                </div>
                <p class="mb-0 mt-2 mx-auto" style="font-size:16px;color:#B1B0B0;width:300px">Select the trainer you want to reassign the clients to</p>
                <div class="mx-auto brds-3 mt-2 d-flex justify-content-around flex-wrap" style="min-height:95px;width:90%;background-color:#F7F7F7;align-items:center;">
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                </div>
                <button class="brds-2 border-0 mt-3 prim_bg" style="width:183px;height:39px;font-size:16px;">Reassign</button>
            </div>
        </div>
        <!-- <div v-if="condition" class="my-popup-component">
            <div class="brds-5 pt-2" style="height:70vh;width:50%;background-color:white;text-align:center;overflow:auto;">
                <p class="mb-0 mt-4 mx-auto" style="font-size:37px;width:500px;line-height:40px">Move All Master Programs and Workouts</p>
                <p class="mb-0 mt-2 mx-auto" style="font-size:16px;width:480px;color:#B1B0B0;">Select the trainer you want to transfer all of these Master Programs and Workouts to. Once moved, they will appear in the trainer's "Personal" folder.</p>
                <p class="mb-0 mt-3 mx-auto" style="font-size:16px;width:300px;color:#B1B0B0;">Select the trainer you want to reassign the clients to</p>
                <div class="mx-auto brds-3 mt-2 d-flex justify-content-around flex-wrap" style="min-height:95px;width:90%;background-color:#F7F7F7;align-items:center;">
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                    <select class="brds-2 float-end px-3" style="height:48px;width:270px;font-size:17px;color:#C5C5C5;border:1px solid #C5C5C5;">
                        <option>12 week HTP</option>
                    </select>
                </div>
                <button class="brds-2 border-0 mt-3 prim_bg" style="width:183px;height:39px;font-size:16px;">Move</button>
            </div>
        </div> -->
    </div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
import DataTable from 'datatables.net-vue3';
import DatePicker from 'vue-datepicker-next';
import 'vue-datepicker-next/index.css';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import newTeamMember from '../components/team/newTeamMember';
import teamPopup from '../components/team/teamPopup';
import { ref } from "vue";
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { DataTable, Loader, Inform, DatePicker, Vue3EasyDataTable, newTeamMember, teamPopup },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            summary: true,
            searchValue: '',
            resetPassword: false,
            showdetails: false,
            teamMembersProfile: false,
            newTeamMemberShow: false,
            assignedLocations: false,
            reassignClients: false,
            pageLoading: false,
            loaderText: '',
            modalTitle: '',
            modalDetail: '',
            informModal: false,
            time2: null,
            confirmPassword: null,
            userData: null,
            postData: {
                img: null,
                firstName: null,
                lastName: null,
                role: null,
                email: null,
                password: null,
                phoneNo: null,
                country: null,
                skype: null,
                gender: null,
                dob: null,
                weightUnits: null,
                distanceUnits: null,
                bodystatUnits: null,
            },
            imageError: false,
            teamDetail: null,
            image_URL: null,
            idForDetails: null,
            itemsSelected: [],
            searchField: ref("name"),
            headers: [
                { text: "Team Member", value: "name", sortable: true },
                { text: "Role", value: "role", sortable: true },
                { text: "Gender", value: "userdetails.gender", sortable: true },
                { text: "Country", value: "userdetails.country", sortable: true },
            ],
            items: []
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getTeam();
    },
    methods: {
        getTeam() {
            this.loaderText = 'Fetching';
            this.pageLoading = true;
            axios.get(config.baseApiUrl + 'all-team', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.teamDetail = res.data.data;
                    this.items = this.teamDetail;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = "Error!";
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        deleteTeamMember() {
            if (this.itemsSelected.length < 1) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No Team member selected';
                this.informModal = true;
                return
            }
            let idsToDelete = [];
            for (let index = 0; index < this.itemsSelected.length; index++) {
                idsToDelete[index] = this.itemsSelected[index].id;
            }
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.get(config.baseApiUrl + 'remove-team-member/' + JSON.stringify(idsToDelete), this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = 'Team Member Deleted Successfully';
                    this.informModal = true;
                    this.getTeam();
                    this.itemsSelected = [];
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
            })
        },
        getTeamMemberDetail(m) {
            this.idForDetails = m;
            this.openDetails();
        },
        openDetails() {
            this.teamMembersProfile = !this.teamMembersProfile;
        },
        newTeam() {
            this.newTeamMemberShow = !this.newTeamMemberShow;
        },
        checkImage() {
            this.postData.img = this.$refs.selectedImage.files[0];
            this.image_URL = window.URL.createObjectURL(this.postData.img);
            if (!this.postData.img.type.includes("image")) {
                this.imageError = true
            }
        },
        save() {
            let error = false;
            if (this.postData.img == null || this.imageError == true) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select an Image';
                this.informModal = true;
                error = true;
            }
            if (this.postData.password != this.confirmPassword) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please enter same password in both fields';
                this.informModal = true;
                error = true;
            }
            if (this.postData.password == null || this.postData.password == '' || this.confirmPassword == null || this.confirmPassword == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please enter password in both password fields';
                this.informModal = true;
                error = true;
            }
            if (this.postData.email != null || this.postData.email == '') {
                var validRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
                if (!this.postData.email.match(validRegex)) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Not a valid Email';
                    this.informModal = true;
                    error = true;
                }
            }
            if (this.postData.firstName == null || this.postData.firstName == '' || this.postData.lastName == null ||
                this.postData.lastName == '' || this.postData.role == null || this.postData.email == null || this.postData.email == '' ||
                this.postData.phoneNo == null || this.postData.phoneNo == '' || this.postData.country == null || this.postData.country == '' ||
                this.postData.gender == null || this.postData.dob == null || this.postData.dob == '' || this.postData.weightUnits == null ||
                this.postData.distanceUnits == null || this.postData.bodystatUnits == null) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please fill all the fields with *'
                this.informModal = true;
                error = true;
            }
            if (error == true) {
                return
            }
            else {
                let fd = new FormData();
                fd.append('first_name', this.postData.firstName);
                fd.append('last_name', this.postData.lastName);
                fd.append('role', this.postData.role);
                fd.append('email', this.postData.email);
                fd.append('password', this.postData.password);
                fd.append('phone', this.postData.phoneNo);
                fd.append('country', this.postData.country);
                fd.append('gender', this.postData.gender);
                fd.append('dob', this.postData.dob);
                fd.append('picture', this.postData.img);
                fd.append('weight_unit', this.postData.weightUnits);
                fd.append('distance_unit', this.postData.distanceUnits);
                fd.append('body_stat_unit', this.postData.bodystatUnits);
                this.pageLoading = true;
                this.loaderText = 'Loading';
                axios.post(config.baseApiUrl + 'create-team-member', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Team member created successfully';
                        this.informModal = true;
                        this.getTeam();
                        this.newTeam();
                        this.resetPostData();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
            }
        },
        resetPostData() {
            this.postData.img = null;
            this.postData.firstName = null;
            this.postData.lastName = null;
            this.postData.role = null;
            this.postData.email = null;
            this.postData.password = null;
            this.postData.phoneNo = null;
            this.postData.country = null;
            this.postData.skype = null;
            this.postData.gender = null;
            this.postData.dob = null;
            this.postData.weightUnits = null;
            this.postData.distanceUnits = null;
            this.postData.bodystatUnits = null;
            this.confirmPassword = null;
        },
        profile() {
            this.editTeamMember = false;
            this.teamMembersProfile = true;
            this.assignedLocations = false;
        },
        edit() {
            this.teamMembersProfile = false;
            this.editTeamMember = true;
        },
        closeEdit() {
            this.editTeamMember = false;
            this.teamMembersProfile = true;
        },
        locations() {
            this.assignedLocations = true;
            this.teamMembersProfile = false;
            this.editTeamMember = false;
        },
        Reassign() {
            this.reassignClients = !this.reassignClients;
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style scoped>
@import 'datatables.net-dt';

.three-dots2 {
    padding: 0px;
    height: 25px;
    border: none;
    background-color: inherit;
    text-align: center;
    float: right;
    width: 25px;
}

.three-dots2:after {
    cursor: pointer;
    content: '\2807';
    padding-left: 5px;
}

.client-dots {
    height: 60px;
    width: 60px;
    border: none;
    position: absolute;
    top: 10px;
    right: 20px;
    border-radius: 30px;
    background-color: #F2A18C;
}

.client-dots::after {
    content: '\2026';
    font-size: 35px;
    color: white;
    position: absolute;
    top: -5px;
    right: 19px;
}

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

.active {
    background-color: #F2A18C !important;
    border: none !important;
}
</style>
