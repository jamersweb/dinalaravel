<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div v-if="newPodcast" @click.self="quitComponent" class="my-popup-component">
        <div class="brds-2 col-11 col-sm-10 col-md-8 col-xl-7 col-lg-6 position-relative" style="height:60vh;background-color:white;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mb-0 mt-4" style="text-align:center;font-size:25px;">Add Podcast</p>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">Title:</p>
                <input v-model="postData.name" type="text" class="brds-2 col-8 px-2 tsl border-0" placeholder="Enter title for the podcast" name="" id="">
            </div>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">Description:</p>
                <textarea v-model="postData.description" class="brds-2 col-8 px-2 tsl border-0" placeholder="Enter description for the Podcast"></textarea>
            </div>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">File:</p>
                <div class="brds-2 col-8 px-2 position-relative tsl" style="height:80px;">
                    <p v-if="postData.file==null">Drag and drop a Audio file here.</p>
                    <p v-else>{{postData.file.name}}</p>
                    <input ref="selectedAudio" @change="getAudio()" type="file" accept="audio/*" placeholder="Enter title for the podcast" style="position:absolute;left:0;top:0;height:100%;width:100%;opacity:0;" name="" id="">
                </div>
            </div>
            <div class="col-10 mx-auto my-3" style="text-align:center;">
                <button @click="createPodcast" class="prim_bg px-4 border-0 brds-1" style="font-size:18px;width:130px;">Save</button>
            </div>
        </div>
    </div>
    <div v-if="editPodcastShow" @click.self="quitComponent" class="my-popup-component">
        <div class="brds-2 col-11 col-sm-10 col-md-8 col-xl-7 col-lg-6 position-relative" style="height:60vh;background-color:white;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="mb-0 mt-4" style="text-align:center;font-size:25px;">Add Podcast</p>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">Title:</p>
                <input v-model="postData.name" type="text" class="brds-2 col-8 px-2 tsl border-0" placeholder="Enter title for the podcast" name="" id="">
            </div>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">Description:</p>
                <textarea v-model="postData.description" class="brds-2 col-8 px-2 tsl border-0" placeholder="Enter description for the Podcast"></textarea>
            </div>
            <div class="col-10 mx-auto my-3 d-flex flex-wrap" style="justify-content:space-between;">
                <p class="mb-0 me-2" style="font-size:20px;">File:</p>
                <div class="brds-2 col-8 px-2 position-relative tsl" style="height:80px;">
                    <p>Drag and drop a Audio file here.</p>
                    <input ref="selectedAudio" @change="getAudio()" type="file" accept="audio/*" placeholder="Enter title for the podcast" style="position:absolute;left:0;top:0;height:100%;width:100%;opacity:0;" name="" id="">
                </div>
            </div>
            <div class="col-10 mx-auto my-3" style="text-align:center;">
                <button @click="savePodcast" class="prim_bg px-4 border-0 brds-1" style="font-size:18px;width:130px;">Save</button>
            </div>
        </div>
    </div>
    <div class="w-100 brds-3 position-relative" style="height:calc(100vh - 125px);overflow-y:auto;border:1px solid #C5C5C5;">
        <div class="w-100" style="border-top-right-radius:10px;border-top-left-radius:10px;background-color:#E7E7E7;height:50px;">
            <p class="ms-4 my-0 pt-1" style="font-size:26px;font-weight:bold;margin-top:7px;">Podcast</p>
        </div>
        <div class="w-100 px-4 py-2" style="height:50px">
            <button @click="newPodcast=true" class="prim_bg px-4 py-1 border-0 brds-1 float-start" style="font-size:15px;">New</button>
            <button class="btn3 tsl position-relative" type="button" data-bs-toggle="dropdown" >
                <img src="/cms-assets/images/master-libraries/three-dots.png" style="width:40%;" alt="">
            </button>
            <ul class="dropdown-menu tsl">
                <li><button @click="editPodcast" class="dropdown-item">Edit</button></li>
                <li><button @click="deletePodcast" class="dropdown-item">Delete</button></li>
            </ul>
        </div>
        <div class="w-100" style="height:calc(100% - 105px);overflow-y:auto;">
            <p v-if="allPodcast.length<1" class="f-20 fw-bold col-12" style="text-align:center;">No Podcast to Display</p>
            <div v-for="(item, index) in allPodcast" :key="index" class="row mx-auto px-4 py-3 my-1 brds-2 position-relative" style="background-color:#E4E4E4;width:95%;">
                <div class="float-start p-0 position-relative" style="width:150px;">
                    <input v-model="selectedPodcast" type="checkbox" :value="item.id" class="form-check-input position-absolute" style="top:-15px;left:-18px;" name="" id="">
                    <img src="/cms-assets/images/mainWebsite/thumbnail.png" class="img-fluid" style="width:150px;" alt="">
                </div>
                <div class="float-start" style="width:calc(100% - 150px)">
                    <p class="my-1" style="font-size:15px;">{{item.time}}</p>
                    <p class="my-1" style="font-size:15px;text-transform:capitalize;">{{item.name}}</p>
                    <p class="my-1" style="font-size:15px;color:#C5C5C5;">{{item.description}}</p>
                    <audio controls :id="index" @play="pauseOthers(index)" class="col-10 mt-1" style="height:30px;">
                        <source :src="item.file" type="">
                    </audio>
                </div>
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
            apiConfig2: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            newPodcast: false,
            editPodcastShow: false,
            selectedPodcast: [],
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            allPodcast: [],
            audioError: false,
            postData: {
                id: null,
                name: null,
                description: null,
                file: null,
            }
        }
    },
    mounted() {
        this.getAllPodcast();
        this.$emit('adminCheckEvent');
    },
    methods: {
        pauseOthers(ele) {
            let audio = document.getElementsByTagName('audio');
            for (let index = 0; index < audio.length; index++) {
                if (index !== ele) {
                    audio[index].pause();
                }
            }
        },
        savePodcast() {
            if (this.audioError == true) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Selected File must be an audio';
                this.informModal = true;
                return
            }
            if (this.postData.name == null || this.postData.name == '' || this.postData.description == null || this.postData.description == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All the fields are required please fill them all';
                this.informModal = true;
            }
            else {
                let fd = new FormData();
                fd.append('id', this.postData.id);
                fd.append('name', this.postData.name);
                fd.append('description', this.postData.description);
                if (this.postData.file !== null) {
                    fd.append('file', this.postData.file);
                }
                this.pageLoading = true;
                this.loaderText = 'Uploading';
                axios.post(config.baseApiUrl + 'update-podcast', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Podcast updated successfully';
                        this.informModal = true;
                        this.postData.name = null;
                        this.postData.description = null;
                        this.postData.file = null;
                        this.newPodcast = false;
                        this.editPodcastShow = false;
                        this.selectedPodcast = [];
                        this.getAllPodcast();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.messages;
                    this.informModal = true;
                })
            }
        },
        editPodcast() {
            if (this.selectedPodcast.length < 1) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Select a Podcast';
                this.informModal = true;
                return
            }
            if (this.selectedPodcast.length > 1) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Only one Podcast can be edited once';
                this.informModal = true;
                return
            }
            else {
                for (let index = 0; index < this.allPodcast.length; index++) {
                    if (this.allPodcast[index].id == this.selectedPodcast[0]) {
                        this.postData.name = this.allPodcast[index].name;
                        this.postData.description = this.allPodcast[index].description;
                        this.postData.id = this.allPodcast[index].id;
                    }
                }
                this.editPodcastShow = true;
            }
        },
        deletePodcast() {
            if (this.selectedPodcast.length < 1) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Select Podcast to Delete';
                this.informModal = true;
            }
            else {
                let postData = {
                    ids: this.selectedPodcast
                };
                this.pageLoading = true;
                this.loaderText = 'Deleting';
                axios.post(config.baseApiUrl + 'delete-podcast', postData, this.apiConfig2).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done!';
                        this.modalDetail = 'Podcast deleted successfully';
                        this.informModal = true;
                        this.getAllPodcast();
                        this.selectedPodcast = [];
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
        quitComponent() {
            this.postData.name = null;
            this.postData.description = null;
            this.postData.file = null;
            this.newPodcast = false;
            this.editPodcastShow = false;
        },
        getAudio() {
            this.postData.file = this.$refs.selectedAudio.files[0];
            if (!this.postData.file.type.includes("audio")) {
                this.audioError = true;
            }
            else {
                this.audioError = false;
            }
            console.log(this.postData.file);
            console.log(this.$refs.selectedAudio);
        },
        getAllPodcast() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-all-podcasts', this.apiConfig2).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allPodcast = res.data.data;
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
        createPodcast() {
            if (this.audioError == true) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Selected File must be an audio';
                this.informModal = true;
                return
            }
            if (this.postData.name == null || this.postData.name == '' || this.postData.description == null || this.postData.description == '' || this.postData.file == null) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All the fields are required please fill them all';
                this.informModal = true;
            }
            else {
                let fd = new FormData();
                fd.append('name', this.postData.name);
                fd.append('description', this.postData.description);
                fd.append('file', this.postData.file);
                this.pageLoading = true;
                this.loaderText = 'Uploading';
                axios.post(config.baseApiUrl + 'create-podcast', fd, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Podcast created successfully';
                        this.informModal = true;
                        this.postData.name = null;
                        this.postData.description = null;
                        this.postData.file = null;
                        this.newPodcast = false;
                        this.getAllPodcast();
                    }
                    else {
                        this.modalTitle = 'Error!';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.messages;
                    this.informModal = true;
                })
            }
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.btn3 {
    background-color: #FFFFFF;
    border: none;
    width: 20px;
    height: 25px;
    float: left;
    margin: 3px 0px 0px 10px;
    border-radius: 3px;
}

.three-dots:after {
    cursor: pointer;
    content: '\2807';
    font-size: 18px;
    padding: 0px 0px 0px 0px;
}
</style>
