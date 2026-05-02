<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <div class="my-popup-component" @click.self="quitComponent" style="z-index:99999 !important">
        <div class="bg-white px-3 py-3 brds-5 position-relative col-11 col-md-8 col-lg-6" style="height:70vh">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="h-100 px-2 px-md-5" style="overflow-y:auto;">
                <p class="mb-2">Select Exercise to Assign:</p>
                <div class="position-relative my-2">
                    <input class="searchinput" type="search" placeholder="Search Exercise" v-model="search"/>
                    <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" />
                </div>
                <div class="gray_bg p-3 brds-2">
                    <p class="mb-1">Selected: {{noOfExer}}</p>
                    <div class="border brds-2 bg-white" style="height:250px; overflow-y:auto">
                        <div class="my-2 ps-3 w-100" style="min-height:30px;" v-for="(item,index) in filteredExer">
                            <input type="checkbox" class="form-check-input float-start" :id="'user'+index" :value="item.id" v-model="selectedexer">
                            <label class="ps-3 w-90 pointer d-flex flex-wrap float-start" style="justify-content:space-between;" :for="'user'+index">
                                <div class="col-6" style="max-height:30px;;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{item.title}}</div>
                                <div class="col-3" style="max-height:30px;;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{item.language}}</div>
                            </label>
                            <br>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="prim_btn brds-2 py-2 px-4 fw-bold" @click="exerSelected()">Assign</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import Loader from '../../components/loader.vue';
export default {
    props: ['prefill', 'language'],
    components: { Inform, Confirm, Loader },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            exercises: [],
            selectedexer: [],
            informModal: false,
            confirmModal: false,
            pageLoading: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            search: "",
            exerName: [],
        }
    },
    computed: {
        filteredExer() {
            return this.exercises.filter((items) => {
                return items.title.toLowerCase().includes(this.search.toLowerCase());
            })
        },
        noOfExer() {
            return this.selectedexer.length;
        }
    },
    mounted() {
        if (this.prefill !== null) {
            this.selectedexer = this.prefill;
        }
        this.pageLoading = true;
        this.loaderText = 'Fetching';
        axios.get(config.baseApiUrl + 'get-all-exercises?lang=' + this.language, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.exercises = res.data.data;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Failed!';
            this.modalDetail = er.message;
            this.informModal = true;
        });
    },
    methods: {
        exerSelected() {
            this.selectedexer.forEach(element => {
                this.exercises.forEach(element2 => {
                    if (element == element2.id) {
                        this.exerName.push(element2.title)
                    }
                });
            });
            this.$parent.assignAlternativeExercises(this.selectedexer, this.exerName);
        },
        acknowledged() {
            this.informModal = false;
        },
        quitComponent() {
            this.$parent.toggleExerComponent();
        }
    },
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
