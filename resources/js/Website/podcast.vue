<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div style="overflow:hidden;" :class="{fixedposition:position}">
        <Header :title3="'PODCAST'"/>
        <div class="row">
            <div class="col-11 px-4 px-sm-1 col-sm-10 col-md-9 py-4 my-4 mx-auto position-relative">
                <div class="col-10 col-sm-11 col-md-12 position-absolute" style="height:40px;top:-40px">
                    <div class="col-11 col-sm-10 mx-auto brds-2 px-3" style="background-color:white;box-shadow:0px 0px 2px #707070;height:40px;">
                        <img src="/cms-assets/images/mainWebsite/Component2.png" style="height:20px;width:20px;" alt="">
                        <input v-model="search" class="px-1" type="text" style="width:calc(100% - 20px);border:none;background-color:transparent;height:100%;" placeholder="Search for Podcast episode">
                    </div>
                </div>
                <div class="col-12">
                    <button @click="episode=false,filterPodCast(0)" :class="{activeBtn : !episode}" class="border-0 py-3 me-1 me-sm-2 f-30" style="background-color:transparent;color:black;">All Episodes</button>
                    <button @click="episode=true,filterPodCast(1)" :class="{activeBtn :  episode}" class="border-0 py-3 ms-1 ms-sm-2 f-30" style="background-color:transparent;color:black;">Latest Episodes</button>
                </div>
                <div class="col-12 brds-1" style="background-color:#C5C5C5;height:3px;margin-top:-4px;"></div>
                <div class="row mt-4">
                    <p v-if="!details&&filteredPodcast.length<1" class="f-20 fw-bold col-12" style="text-align:center;">No Podcast to Display</p>
                    <div v-if="!details">
                        <div class="row">
                            <div v-if="!details" v-for="(item, index) in filteredPodcast" :key="index" class="col-12 py-3 brds-2 my-1" style="background-color:#E4E4E4;">
                                <div class="mx-auto" style="width:98%;">
                                    <img src="/cms-assets/images/mainWebsite/thumbnail.png" class="col-6 col-sm-3 img-fluid float-start" alt="">
                                    <div class="col-6 ps-3 pt-1 mt-2 float-start">
                                        <p class="mb-0 f-20">{{item.time}}</p>
                                        <p class="mb-0 f-20" style="text-transform:capitalize;">{{item.name}}</p>
                                        <p class="mb-0 f-20" style="color:#8F8787;text-transform:capitalize;">{{item.description}}</p>
                                        <button @click="showDetails(allPodcast[index])" class="position-relative fw-bold px-2 py-2 mt-3" style="background-color:#10161B;color:#FC9C88">
                                            <img src="/cms-assets/images/mainWebsite/Group16257.png" class="img-fluid" style="height:25px" alt="">
                                            PLAY EPISODE
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 text-end mt-3">
                            <button v-if="previousBtn!==null" @click="prevPodcast" class="border-0 me-2" style="background-color:transparent;color:#FC9C88;font-weight:bold;">&ltPrev</button>
                            <button v-if="nextBtn!==null" @click="nextPodcast" class="border-0 ms-2" style="background-color:transparent;color:#FC9C88;font-weight:bold;">Next></button>
                        </div>
                    </div>
                    <div v-if="details" class="col-12">
                        <div class="row">
                            <div class="col-12 py-3 brds-2" style="background-color:#E4E4E4;">
                                <div class="mx-auto" style="width:98%;">
                                    <img src="/cms-assets/images/mainWebsite/thumbnail.png" class="col-6 col-sm-3 img-fluid float-sm-start" alt="">
                                    <div class="col-12 col-sm-9 px-3 pt-1 mt-2 float-sm-start">
                                        <p class="mb-0 f-20">{{detailsData.name}}</p>
                                        <audio controls class="col-12 mt-3">
                                            <source :src="detailsData.file" type="">
                                        </audio>
                                        <button @click="details=false" class="position-relative fw-bold px-2 py-2 mt-3" style="background-color:#10161B;color:#FC9C88">Close Details</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="fw-bold my-2 f-20">{{detailsData.date}}</p>
                        <p class="my-2 f-20" style="color:#C5C5C5;">{{detailsData.description}}</p>
                        <p class="my-2 f-20">Below is the link to book your free call with me</p>
                    </div>
                </div>
            </div>
        </div>
        <Footer />
    </div>
</template>
<script>
import Header from './components/header.vue';
import Footer from './components/footer.vue';
import axios from 'axios';
import config from '../config';
import Loader from "../components/loader.vue";
import Inform from "../components/inform.vue";
export default {
    components: { Header, Footer, Loader, Inform },
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: "Bearer " + config.storage.getItem("fwd_session_token"),
                },
            },
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: null,
            modalDetail: null,
            loaderText: null,
            episode: false,
            details: false,
            allPodcast: [],
            nextBtn: null,
            previousBtn: null,
            btnValue: 1,
            detailsData: null,
            search: '',
            filterValue: 'all',
            position: false,
        }
    },
    mounted() {
        this.getAllPodcast();
        this.scrollToTop();
    },
    computed: {
        filteredPodcast() {
            return this.allPodcast.filter((items) => {
                return items.name.toLowerCase().includes(this.search.toLowerCase());
            });
        },
    },
    methods: {
        scrollToTop() {
            window.scrollTo(0, 0)
        },
        changePosition() {
            this.position = !this.position;
        },
        filterPodCast(m) {
            if (m == 0) {
                this.filterValue = 'all';
                this.getAllPodcast();
            }
            if (m == 1) {
                this.filterValue = 'new';
                this.getAllPodcast();
            }
        },
        showDetails(m) {
            this.detailsData = m;
            this.details = true;
        },
        prevPodcast() {
            this.btnValue = this.btnValue - 1;
            this.getAllPodcast();
        },
        nextPodcast() {
            this.btnValue = this.btnValue + 1;
            this.getAllPodcast();
        },
        getAllPodcast() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            // axios.get('/api/get-all-podcasts?page=' + this.btnValue + '&filter=' + this.filterValue, this.apiConfig).then(res => {
            axios.get('https://fwd-dev.senarios.co/api/get-all-podcasts?page=' + this.btnValue + '&filter=' + this.filterValue, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.nextBtn = res.data.next;
                    this.previousBtn = res.data.prev;
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
    }
}
</script>
<style scoped>
.activeBtn {
    border-bottom: 5px solid #FC9C88 !important;
}

.fixedposition {
    position: fixed;
}
</style>
