<template lang="">
    <div class="my-popup-component" @click.self="quitComponent()">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <div class="main-box w-80 p-4 position-relative">
            <h1 class="text-center fw-bold">Add Tags</h1>
            <button class="trans_btn position-absolute" @click="quitComponent()"
                style="right:20px;top:30px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="tags-body mt-4" style="overflow:auto;height:75%">
                <div class="d-flex justify-content-between">
                    <div class="tag-list px-2" style="min-width:180px" v-for="item in tags">
                        <h5 class="fw-bold wb-all">{{item.tagType}}</h5>
                        <div>
                            <div class="" v-for="tag in item.tagList">
                                <input class="me-2 form-check-input" type="checkbox" :value="tag.id" :id="'check'+tag.id" v-model="checkedTags">
                                <label class="d-inline wb-all" :for="'check'+tag.id">{{tag.name}}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="prim_btn fw-bold px-5" style="border-radius:10px" @click="addTags">Add</button>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
export default {
    components: { Loader, Inform, Confirm },
    props: ['prefilledtags'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            tags: [],
            checkedTags: [],
            checkedTagsNames: [],
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    mounted() {
        this.getAllTags();
        if (this.prefilledtags !== undefined) {
            this.checkedTags = this.prefilledtags;
        }
    },
    methods: {
        showConfirmModal() {
            this.modalDetail = 'This Cannot be Undone.'
            this.modalTitle = 'Do you want to quit?'
            this.confirmModal = true;
        },
        confirmationResponse(res) {
            this.confirmModal = false;
            if (res == 0)
                return;
            this.quitComponent();
        },
        quitComponent() {
            this.$parent.toggleTagsComponent();
        },
        getAllTags() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-tags?category=exercise', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.tags = res.data.data;
                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: fetching exercise tags ", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: fetching exercise tags ", er);
            });
        },
        addTags() {
            this.checkedTags.forEach(element => {
                this.tags.forEach(element2 => {
                    element2.tagList.forEach(element3 => {
                        if (element3.id == element) {
                            this.checkedTagsNames.push(element3.name)
                        }
                    });
                });
            });
            this.$parent.selectedTags(this.checkedTags, this.checkedTagsNames);
        },
        acknowledged() {
            this.informModal = false;
        },
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
