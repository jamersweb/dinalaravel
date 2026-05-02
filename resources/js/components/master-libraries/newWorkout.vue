<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Loader v-if="pageLoading" loadingText="Saving" />
        <div v-if="!showTags" class="main-box position-relative px-2">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:35px;top:15px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="row justify-content-center">
                <div class="col-md-8 col-xl-6">
                    <h2 class="text-center"><strong>Build New Workout</strong></h2>
                    <div class="w-100 wrk_name">
                        <input @input="this.nameError = false" v-model="name" type="text" placeholder="Workout name like, day 1 abs">
                    </div>
                    <span style="color:red" v-if="nameError">*Please Enter Name of your Workout</span>
                    <div class="w-100 wrk_name mt-2">
                        <input v-model="contentCode" type="text" placeholder="Content code (optional, e.g. WR-023)">
                    </div>
                    <!-- <div class="text-center mt-2">
                        <p class="text-muted">Type of Workout</p>
                        <select v-model="type">
                            <option value="regular" selected>Regular</option>
                            <option value="circuit">Circuit</option>
                            <option value="interval">Interval</option>
                        </select>
                    </div> -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-muted">Tags:</h5>
                        </div>
                        <div class="d-flex flex-wrap brds-1 p-2 mt-2 border" style="max-height:100px;overflow-y:auto">
                            <span v-for="tag in selectedTags" class="px-2 py-1 prim_bg mx-2 brds-1 my-1">{{tag}}</span>
                            <button class="scnd_btn px-4 py-1 brds-2 my-1 mx-2" @click="assignTagsShow">Add +</button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5 class="text-muted">Language:</h5>
                        <select v-model="language" class="w-100 px-3 py-3 brds-1 border">
                            <option value="en">English</option>
                            <option value="ar">Arabic</option>
                            <option value="no">No Audio</option>
                        </select>
                    </div>
                        <span style="color:red" v-if="typeError">*Please select valid type</span>
                    <div class="text-center mt-4">
                        <button @click="create()" class="prim_btn prim_btn_rnd px-4">Start Building</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <assignTags v-if="showTags" :tagType="'workout'" :prefilledTags="tags"/>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import assignTags from '../clients/assignTags.vue';
export default {
    components: { Loader, Inform, assignTags },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            name: null,
            contentCode: '',
            type: 'regular',
            language: 'en',
            nameError: false,
            typeError: false,
            tags: [],
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            showTags: false,
            selectedTags: []
        }
    },
    methods: {
        assignTagsShow() {
            this.showTags = !this.showTags;
        },
        quitComponent() {
            this.$parent.toggleNewWorkout();
        },
        assignTags(tags) {
            this.showTags = false;
            this.selectedTags = [];
            this.tags = [];
            tags.forEach(element => {
                this.selectedTags.push(element.tagName);
                this.tags.push(element.tagId);
            });
        },
        create() {
            if (this.name == null || this.name == '') {
                this.nameError = true;
                return;
            }
            if (this.type != 'regular' && this.type != 'circuit' && this.type != 'interval') {
                this.typeError = true;
                return;
            }
            const postData = {
                'title': this.name,
                'type': this.type,
                'language': this.language,
                'tags': this.tags,
                'content_code': (this.contentCode || '').trim(),
            }
            this.pageLoading = true;
            axios.post(config.baseApiUrl + 'create-workout', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$parent.toNextStep(res.data.data);
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Creating workout error";
                    this.informModal = true;
                    console.log("Creating workout error", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = "Creating workout error";
                this.informModal = true;
                console.log("Creating workout error", er);
            });
        },
        acknowledged() {
            this.informModal = false;
        },
    },
}
</script>
<style scoped>
.main-box {
    background-color: white;
    border-radius: 30px;
    padding-top: 100px;
    padding-bottom: 100px;
    width: 60%;
    max-height: 90%;
    overflow-y: auto;
    overflow-x: hidden;
}

@media only screen and (max-width: 650px) {
    .main-box {
        width: 85% !important;
    }
}

.wrk_name {
    background-color: rgb(240, 240, 240);
    border-radius: 10px;
    padding: 7px;
}

.wrk_name input {
    width: 100%;
    border: 1px solid rgb(182, 182, 182);
    padding: 10px;
    border-radius: 5px;
}
</style>
