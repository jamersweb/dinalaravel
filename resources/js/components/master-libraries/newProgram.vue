<template lang="">
    <div class="my-popup-component" @click.self="showConfirmModal">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <div v-if="!showTags" class="row justify-content-center w-100" style="height:80%">
            <div class="col-11 col-md-8 col-xl-6 h-100">
                <div class="main-box p-2 p-md-4 position-relative h-100">
                    <button class="trans_btn position-absolute" @click="showConfirmModal"
                        style="right:15px;top:10px;font-size:25px">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                    <div class="py-5 mt-4 text-center" style="overflow-y:auto;height:90%;">
                        <h3><strong>Build a new master program</strong></h3>
                        <p class="text-muted">Please choose the type of program</p>
                        <select v-model="postData.type" class="theme-select col-100 col-sm-8 col-xl-6 brds-1">
                            <option value="predefined" selected>Pre-Define</option>
                            <option value="custom">Custom</option>
                        </select>
                        <div class="mt-4 mx-auto gray_bg p-3 brds-2 col-12 col-sm-10 col-lg-8">
                            <p class="fw-bold mb-2">Please enter name of program</p>
                            <input v-model="postData.title" @input="titleError = false" type="text" class="theme-input w-80 brds-1" placeholder="type name here">
                            <p class="text-danger mb-0" v-show="titleError">* Please enter name</p>
                            <p class="fw-bold mb-2 mt-3">Content code (optional, e.g. PR-001)</p>
                            <input v-model="postData.content_code" type="text" class="theme-input w-80 brds-1" placeholder="leave empty to assign later via command">
                            <p class="fw-bold mb-2 mt-3">Program cover image</p>
                            <input type="file" accept="image/*" class="theme-input w-80 brds-1" @change="selectProgramImage">
                            <img v-if="programImagePreview" :src="programImagePreview" alt="Program cover preview" class="mt-3 brds-1"
                                style="width:160px;height:110px;object-fit:cover;">
                        </div>
                        <p class="text-muted mb-1 mt-4">Please choose the level of program</p>
                        <select v-model="postData.level" class="theme-select col-100 col-sm-8 col-xl-6 brds-1">
                            <option value="beginner" selected>Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="expert">Expert</option>
                        </select>
                        <div class="mt-4 mx-auto gray_bg p-3 brds-2 col-12 col-sm-10 col-lg-8">
                            <p class="fw-bold mb-2 text-center">Select Duration</p>
                            <div class="d-flex justify-content-around flex-wrap">
                                <div class="d-flex align-items-center">
                                    <p class="mb-0 me-2">Phases: </p>
                                    <select v-model="postData.phases" class="theme-select brds-1 px-1 h7" style="width:100px">
                                        <option value="1" selected>1 Phase</option>
                                        <option value="2">2 Phases</option>
                                        <option value="3">3 Phases</option>
                                        <option value="4">4 Phases</option>
                                        <option value="5">5 Phases</option>
                                        <option value="6">6 Phases</option>
                                        <option value="7">7 Phases</option>
                                        <option value="8">8 Phases</option>
                                        <option value="9">9 Phases</option>
                                        <option value="10">10 Phases</option>
                                    </select>
                                </div>
                                <div class="d-flex align-items-center mt-2 mt-sm-0">
                                    <p class="mb-0 me-2">Weeks: </p>
                                    <select v-model="postData.weeks_per_phase" class="theme-select brds-1 px-1 h7" style="width:100px">
                                        <option value="1" selected>1 Week</option>
                                        <option value="2">2 Weeks</option>
                                        <option value="3">3 Weeks</option>
                                        <option value="4">4 Weeks</option>
                                        <option value="5">5 Weeks</option>
                                        <option value="6">6 Weeks</option>
                                        <option value="7">7 Weeks</option>
                                        <option value="8">8 Weeks</option>
                                        <option value="9">9 Weeks</option>
                                        <option value="10">10 Weeks</option>
                                    </select>
                                </div>
                            </div>
                            <p class="mt-2 mb-0">Total: {{totalWeeks}} Weeks</p>
                        </div>
                        <div class="mt-4 mx-auto p-3 brds-2 col-12 col-sm-10 col-lg-8">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 text-muted">Tags:</h5>
                            </div>
                            <div class="d-flex flex-wrap brds-1 p-2 mt-2 border">
                                <span v-for="tag in selectedTags" class="px-2 py-1 prim_bg mx-2 brds-1 my-1">{{tag}}</span>
                                <button class="scnd_btn px-4 py-1 brds-2 my-1 mx-2" @click="assignTagsShow">Add +</button>
                            </div>
                        </div>
                        <p class="text-muted my-1">Please select the Language of program</p>
                        <select v-model="postData.language" class="theme-select col-100 col-sm-8 col-xl-6 brds-1">
                            <option value="en">English</option>
                            <option value="ar">Arabic</option>
                            <option value="no">No Audio</option>
                        </select>
                        <div class="mt-3">
                            <button class="prim_btn px-5 brds-2" @click="createProgram()">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <assignTags v-if="showTags" :tagType="'program'" :prefilledTags="postData.tags"/>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import assignTags from '../clients/assignTags.vue';
export default {
    components: { Loader, Inform, Confirm, assignTags },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            postData: {
                title: null,
                type: 'predefined',
                level: 'beginner',
                phases: 1,
                weeks_per_phase: 1,
                language: 'en',
                tags: [],
                content_code: '',
            },
            titleError: false,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            showTags: false,
            selectedTags: [],
            programImageFile: null,
            programImagePreview: null,
        }
    },
    methods: {
        selectProgramImage(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            this.programImageFile = file;
            this.programImagePreview = file ? URL.createObjectURL(file) : null;
        },
        assignTags(tags) {
            this.showTags = false;
            this.selectedTags = [];
            this.postData.tags = [];
            tags.forEach(element => {
                this.selectedTags.push(element.tagName);
                this.postData.tags.push(element.tagId);
            });
        },
        assignTagsShow() {
            this.showTags = !this.showTags;
        },
        showConfirmModal() {
            this.modalDetail = 'Your progress will be lost.'
            this.modalTitle = 'Are you sure?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.quitComponent();
        },
        quitComponent() {
            this.$parent.toggleNewProgram();
        },
        createProgram() {
            if (this.postData.title == null || this.postData.title == '') {
                this.titleError = true;
                return;
            }
            this.pageLoading = true;
            this.loaderText = 'Uploading'
            const fd = new FormData();
            fd.append('title', this.postData.title);
            fd.append('type', this.postData.type);
            fd.append('level', this.postData.level);
            fd.append('phases', this.postData.phases);
            fd.append('weeks_per_phase', this.postData.weeks_per_phase);
            fd.append('language', this.postData.language);
            fd.append('content_code', this.postData.content_code || '');
            this.postData.tags.forEach(tagId => fd.append('tags[]', tagId));
            if (this.programImageFile) {
                fd.append('image', this.programImageFile);
            }
            axios.post(config.baseApiUrl + 'create-new-program', fd, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    // this.modalTitle = 'Done!';
                    // this.modalDetail = "Program Created";
                    // this.informModal = true;
                    this.$parent.getAllPrograms();
                    this.quitComponent();
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in uploading new program ", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error in uploading new program ", er.error);
            });
        },
        acknowledged() {
            this.informModal = false;
        },
    },
    computed: {
        totalWeeks() {
            return this.postData.phases * this.postData.weeks_per_phase;
        }
    }
}
</script>
<style scoped>
.main-box {
    background-color: white;
    border-radius: 30px;
    height: 90%;
}
</style>
