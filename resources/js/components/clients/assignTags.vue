<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="my-popup-component">
        <div class="bg-white px-3 py-5 brds-5 position-relative col-11 col-md-8 col-lg-6" style="height:70vh;overflow-y:auto">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="h-100 px-2 px-md-5">
                <p class="mb-2">Select Tags to Assign:</p>
                <div class="position-relative my-2">
                    <input class="searchinput" type="search" placeholder="Search Tags" v-model="search"/>
                    <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" />
                </div>
                <div class="gray_bg p-3 brds-2">
                    <p class="mb-1">Selected:</p>
                    <div class="border brds-2 bg-white d-flex flex-wrap" style="max-height:250px; overflow-y:auto">
                        <div class="my-2 ps-3 col-6" style="min-height:30px;" v-for="(item,index) in filteredTags">
                            <!-- <input v-if="tagType=='client'" type="checkbox" class="form-check-input float-start" :id="'user'+index" :value="item.id" v-model="selectedTags"> -->
                            <input type="checkbox" class="form-check-input float-start" :id="'user'+index" :value="item.id" v-model="selectedTags">
                            <label class="ps-3 w-90 pointer d-flex flex-wrap float-start" style="justify-content:space-between;" :for="'user'+index">
                                <div class="col-6">{{item.name}}</div>
                            </label>
                        </div>
                        <div v-if="filteredTags.length<1">No Tags for the Search</div>
                    </div>
                </div>
                <div class="text-center mt-3 pb-3">
                    <button @click="assignTags()" class="prim_btn brds-2 py-2 px-4 fw-bold">Assign</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { Loader, Inform },
    props: ['tagType', 'id', 'prefilledTags'],
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
            tags: [],
            tagsCategory: null,
            selectedTags: [],
            search: ""
        }
    },
    computed: {
        filteredTags() {
            return this.tags.filter((items) => {
                return items.name.toLowerCase().includes(this.search.toLowerCase());
            })
        }
    },
    mounted() {
        this.tagsCategory = this.tagType;
        if (this.prefilledTags.length > 0) {
            this.selectedTags = this.prefilledTags;
        }
        this.getTags();
    },
    methods: {
        quitComponent() {
            this.$parent.assignTagsShow();
        },
        getTags() {
            this.pageLoading = true;
            this.loaderText = 'Getting Tags';
            axios.get(config.baseApiUrl + 'get-uncategorized-tags?category=' + this.tagsCategory, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.tags = res.data.data;
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
        assignTags() {
            if (this.tagType == 'client') {
                let postData = {
                    user_id: this.id,
                    tags: this.selectedTags,
                }
                this.pageLoading = true;
                this.loaderText = 'Assigning Tags';
                axios.post(config.baseApiUrl + 'client-tags-assign', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Tags assigned to client';
                        this.informModal = true;
                        this.$parent.getClientTags();
                        this.$parent.assignTagsShow();
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
            else {
                let tagsDetails = [];
                this.selectedTags.forEach(element => {
                    this.tags.forEach(element2 => {
                        if (element === element2.id) {
                            let temp = {};
                            temp.tagId = element;
                            temp.tagName = element2.name;
                            tagsDetails.push(temp);
                        }
                    });
                });
                this.$parent.assignTags(tagsDetails);
            }
        }
    }
}
</script>
<style scoped>
.searchinput {
    border: 1px solid #c5c5c5;
    border-radius: 11px;
    padding: 8px;
    width: 93%;
    padding-left: 25px;
    margin-left: 14px;
    font-size: 14px;
}

.searchab {
    position: absolute;
    width: 15px;
    top: 12px;
    left: 20px;
}
</style>
