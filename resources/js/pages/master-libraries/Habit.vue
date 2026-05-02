<template>
    <div class="divfull">
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <getInput v-if="getInputModal" :msgTitle="modalTitle" :msgDetail="modalDetail" :renameValue="modalValue" />
        <div class="navside">
            <div class="navcnt">
                <p class="navsidep">Master Habits</p>
                <button class=" btn1 tsl rounded-3" @click="addfldr()">Add Folder</button>
                <div class="nofldrhab p-4" v-if="habits == null || habits.length == 0">
                    <h5>No Habits Folder </h5>
                </div>
                <div class="navfldrs" :class="{ active: fldrid == item.id }" v-for="item in habits" v-bind:key="item">
                    <button class="navfldrsul" @click="cardrep(item.id)">{{ item.name }}</button>
                    <div class="dropdown">
                        <button class="three-dots2 position-relative" data-bs-toggle="dropdown">
                            <img src="/cms-assets/images/master-libraries/three-dots.png" style="height:30%" alt="">
                        </button>
                        <ul class="dropdown-menu tsl">
                            <li><button class="dropdown-item"
                                    @click="showRenamePopup(item.id, item.name)">Rename</button></li>
                            <li><button class="dropdown-item"
                                    @click="showConfirmModal(item.id, 'folder')">Delete</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="moveDiv" style="width: 100vw;height: 100vh;position: absolute;top: 0;left: 0;z-index: 998;"
            @click="moveDiv = false"></div>
        <div class="main">
            <div class="topbar">
                <!-- <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" /> -->
                <!-- <input class="form-check-input tsl" type="checkbox" value=""> -->
                <button v-if="showContent" type="button" class="btn2" @click="show1">New</button>
                <button @click.self="moveDiv = !moveDiv" v-if="showContent && habits.length > 1"
                    class="btn3 tsl position-relative">Move
                    <img @click.self="moveDiv = !moveDiv" src="/cms-assets/images/Clients/downarrow.png"
                        style="height: 6px;position: absolute;right: 3px;top: 10px;" alt="">
                    <div v-if="moveDiv"
                        style="width: 100px;height: 100px;overflow-y: auto;position: absolute;background-color: white;z-index: 999;"
                        class="tsl">
                        <div v-for="(item, index) in habits" :key="index">
                            <p class="w-100 m-0 py-1" v-if="item.id !== fldrid" @click="moveHabit(item.id)"
                                style="cursor: pointer;">{{ item.name }}</p>
                        </div>
                    </div>
                </button>
                <button v-if="showContent" class="btn3 tsl " type="button"
                    @click="showConfirmModal(null, 'habit')">Delete</button>
                <select class="inpname" id="">
                    <option>Name</option>
                </select>
            </div>
            <div class="content">
                <h3 v-if="!showContent" style="text-align: center;margin-top: 30px;">Select a habit folder</h3>
                <h3 v-if="(showHabits == null || showHabits.length == 0) && showContent"
                    style="text-align: center;margin-top: 30px;">No habit added in this folder</h3>
                <div class="mainhbt tsh position-relative" v-for="items in showHabits" :key="items">
                    <input type="checkbox" class="form-check-input" :value="items.id" v-model="deletionIds">
                    <div class="img">
                        <img class="img1" src="../../../../public/images/Group74377.png" alt="error">
                    </div>
                    <div class="info">
                        <img class="img2" src="../../../../public/images/Group57573.png" alt="error">
                        <h6 class="habittitle" @click="popuphabit(items.id)">{{ items.title }}</h6>
                        <p class="p1">{{ items.date }}</p>
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
    </div>
    <habitDetails v-if="habitPopup" :habitid="habitid" />
    <addHabitFldr v-if="showAddFldr" />
    <createHabit v-if="showAddFldr2" :fldrid="fldrid" />
</template>

<script>
import axios from 'axios';
import config from '../../config';
import selectName from '../../components/master-libraries/selectName';
import addHabitFldr from '../../components/master-libraries/addHabitFldr';
import createHabit from '../../components/master-libraries/createHabit';
import habitDetails from '../../components/master-libraries/habitDetails';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import GetInput from '../../components/getInput.vue';

export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: {
        selectName,
        addHabitFldr,
        createHabit,
        habitDetails,
        Loader,
        Inform,
        Confirm,
        GetInput,
    },
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            showContent: false,
            repnmbr: null,
            showAddFldr: false,
            showAddFldr2: false,
            habits: null,
            fldrid: null,
            habitid: null,
            showHabits: null,
            habitPopup: false,
            moveDiv: false,
            fldrDelete: {
                folder_id: null,
            },
            fldrRename: {
                id: null,
                name: null,
            },
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            getInputModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            deleteOption: '',
            deletionIds: [],
            modalValue: null,
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getAllHabitFldrs();
    },
    methods: {
        showConfirmModal(y, m) {
            this.fldrDelete.folder_id = y;
            this.deleteOption = m;
            if (this.deletionIds.length == 0 && this.deleteOption == 'habit') {
                this.modalTitle = 'No habit selected!';
                this.modalDetail = 'Please select a habit first';
                this.informModal = true;
                return;
            }
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Do you want to delete?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 1) {
                if (this.deleteOption == 'folder') {
                    this.deleteFolder();
                }
                else if (this.deleteOption == 'habit') {
                    this.deleteHabit();
                }
            }
            else {
                return;
            }
        },
        insertIdArray(id) {
            const index = this.deletionIds.indexOf(id)
            if (index == -1)
                this.deletionIds.push(id);
            else
                this.deletionIds.splice(index, 1);
        },
        moveHabit(m) {
            let postData = {
                habit_ids: this.deletionIds,
                folder_id: m
            }
            if (this.deletionIds.length == 0) {
                this.modalTitle = 'Error';
                this.modalDetail = 'No habit selected';
                this.informModal = true;
                return
            }
            else {
                this.pageLoading = true;
                this.loaderText = 'Moving';
                axios.post(config.baseApiUrl + 'move-habit-to-folder', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.getAllHabits();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.message;
                    this.informModal = true;
                })
            }
        },
        getAllHabitFldrs() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-habit-folders', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status)
                    this.habits = res.data.data
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: fetching habit folders ", res.data.message);
                }
            }).catch(err => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: fetching habit folders ", err.error);
            });
        },
        popuphabit(m) {
            this.habitid = m;
            this.habitPopup = !this.habitPopup;
        },
        getAllHabits() {
            this.pageLoading = true;
            this.loaderText = 'Loading',
                axios.get(config.baseApiUrl + 'get-folder-habits/' + this.fldrid, this.apiConfig)
                    .then(res => {
                        this.pageLoading = false;
                        if (res.data.status)
                            this.showHabits = res.data.data
                        else {
                            this.modalTitle = 'Error!';
                            this.modalDetail = "Something went wrong";
                            this.informModal = true;
                            console.log("Error: fetching habits ", res.data.message);
                        }
                    }).catch(err => {
                        this.pageLoading = false;
                        this.modalTitle = 'Failed!';
                        this.modalDetail = "Something went wrong";
                        this.informModal = true;
                        console.log("Error: fetching habits ", err.error);
                    });
        },
        deleteFolder() {
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.post(config.baseApiUrl + 'delete-habit-folder', this.fldrDelete, this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                        this.getAllHabitFldrs();
                    } else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                        console.log("Error: deleting habit folder ", res.data.message);
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Failed!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: deleting habit folder", er.error);
                });
        },
        deleteHabit() {
            const postData = {
                'ids': this.deletionIds
            };
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.post(config.baseApiUrl + 'delete-habit', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                this.deletionIds = [];
                postData.ids = [];
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = "Habit Deleted Successfully";
                    this.informModal = true;
                    this.getAllHabits();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in deleting habit", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error deleting habit: ", er);
            });
        },
        renameHabitFolder(name) {
            this.fldrRename.name = name;
            console.log(this.fldrRename.id);
            this.pageLoading = true;
            this.loaderText = 'Renaming';
            axios.post(config.baseApiUrl + 'rename-habit-folder', this.fldrRename, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = "Habit Renamed Successfully";
                    this.informModal = true;
                    this.getAllHabitFldrs();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in renaming habit", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error renaming habit: ", er);
            });
        },
        showRenamePopup(id, m) {
            this.modalTitle = 'Rename';
            this.modalValue = m;
            this.fldrRename.id = id;
            this.getInputModal = true;
        },
        inputResponse(value) {
            this.getInputModal = false;
            if (value != null) {
                this.renameHabitFolder(value);
            }
        },
        cardrep(n) {
            this.fldrid = n;
            this.showContent = true;
            this.getAllHabits();
        },
        addfldr() {
            this.showAddFldr = !this.showAddFldr;
        },
        addhabit() {
            this.showAddFldr2 = !this.showAddFldr2;
        },
        acknowledged() {
            this.informModal = false;
        },
        show1() {
            if (this.showContent == true) {
                this.addhabit();
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = "Please select a habit folder first";
                this.informModal = true;
            }
        },
    }
}
</script>

<style scoped>
html {
    margin-right: 0px;
    padding-right: 0px;
}

body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding-right: 0px;
}

.divfull {
    width: 100%;
    margin: 0;
    position: relative;
    padding-right: 0px;
    height: calc(100vh - 125px);
    overflow: hidden;
    border-radius: 13px;
    border: 1px solid #E7E7E7;

}

.btn2 {
    background-color: #F2A18C;
    border: none;
    width: 120px;
    height: 25px;
    font-size: 13px;
    font-weight: 100;
    float: left;
    padding: 0;
    margin: 10px 0px 0px 8px;
    border-radius: 3px;
}

.btn2:hover {
    background-color: black;
    color: #F2A18C;
}

.btn3 {
    background-color: #FFFFFF;
    border: none;
    width: 70px;
    font-size: 13px;
    height: 25px;
    float: left;
    padding: 0px 5px 0px 0px;
    margin: 10px 0px 0px 8px;
    border-radius: 3px;
}

.form-check-input {
    position: absolute;
    top: 5px;
    right: 10px;
}

.inpname {
    width: 35%;
    background-color: inherit;
    font-size: 12px;
    color: #343434;
    border-color: #7a7979;
    border-radius: 10px;
    float: right;
    margin: 10px 8px 0px 0px;
    height: 25px;
}

.navside {
    width: 269px;
    border-top-left-radius: 13px;
    border-bottom-left-radius: 13px;
    height: 100%;
    margin: 0px;
    padding: 0px;
    background-color: #F7F7F7;
    float: left;
}

.dropdown-menu {
    border: none;
}

.navcnt {
    float: left;
    height: 100%;
    width: inherit;
    overflow-y: auto;
    padding-bottom: 20px;
}

.navsidep {
    padding: 0;
    font-size: 17px;
    margin: 10px 0px 10px 30px;
    color: #343434;
}

.btn1 {
    margin-left: 30px;
    font-size: 13px;
    height: 50px;
    width: 80%;
    padding: 0;
    border: none;
    cursor: pointer;
    background-color: #FFFFFF;
    margin-bottom: 15px;
}

.navfldrs {
    width: 100%;
    height: 50px;
    float: left;
    border: 1px solid #E7E7E7;
    cursor: pointer;
}

.navfldrsul {
    width: 90%;
    height: 48px;
    border: none;
    background-color: inherit;
    padding-left: 27px;
    text-align: start;
    float: left;
}

.three-dots2 {
    padding: 0px;
    height: 50px;
    border: none;
    background-color: inherit;
    text-align: center;
    float: right;
    width: 10%;
}

/* .three-dots2:after {
    cursor: pointer;
    content: '\2807';
} */

.active {
    background-color: #EEEEEE;
}

.main {
    height: 100%;
    width: calc(100% - 270px);
    margin-left: 0px;
    left: 268px;
    float: left;
    border: 1px solid #E7E7E7;
    border-top-right-radius: 13px;
    border-bottom-right-radius: 13px;
    padding-bottom: 35px;
}

.topbar {
    width: 100%;
    height: 45px;
    background-color: #EEEEEE;
    border-top-right-radius: 10px;
}

.content {
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: #FFFFFF;
    padding-bottom: 20px;
}

.space1 {
    width: 100%;
    height: 20px;
}

.foot {
    width: 100%;
    height: 40px;
    float: left;
}

.itms {
    height: 30px;
    width: 120px;
    position: relative;
    top: 15px;
    left: 10px;
    float: left;
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

.p1 {
    font-size: 8px;
}

.pginul1 {
    padding-top: 3px;
    height: 25px;
    width: 110px;
    padding-left: 0;
}

.pginli {
    float: left;
    list-style: none;
    margin-left: 9px;
    font-size: 11px;
}

.addfldr {
    height: 100vh;
    width: 100vw;
    position: fixed;
    background-color: #11111198;
}

.downarrow:after {
    content: '\2304';
    margin-left: 0px;
    margin-top: -2px;
    float: right;
}

.img {
    height: auto;
    width: 20px;
    margin-left: 15px;
    float: left;
}

.img1 {
    height: 90px;
}

.info {
    height: 150px;
    width: 120px;
    margin-left: 5px;
    text-align: center;
    float: left;
}

.img2 {
    height: 40px;
    margin-right: 10px;
    margin-top: 20px;
}

h6 {
    font-size: 13px;
    margin-block-start: 0;
    margin-block-end: 0;
}

.p1 {
    font-size: 8px;
}

.habittitle {
    font-size: 16px;
}

.mainhbt {
    height: 150px;
    width: 200px;
    cursor: pointer;
    margin-top: 30px;
    margin-left: 5%;
    float: left;
    border: none;
    border-radius: 10px;
}

@media screen and (max-width: 1290px) {
    .mainhbt {
        margin-left: 13%;
    }
}

@media screen and (max-width: 1120px) {
    .mainhbt {
        margin-left: 8%;
    }
}

@media screen and (max-width: 1050px) {
    .mainhbt {
        margin-left: 15%;
    }
}

@media screen and (max-width: 849px) {
    .mainhbt {
        margin-left: 10%;
    }
}
</style>
