<template lang="">
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <div class="my-popup-component" @click.self="quitComponent">
        <div class="col-11 position-relative" style="height:90vh;background-color:white;border-radius:20px;padding-top:30px;padding-left:10px;padding-right:10px;">
            <div class="col-5 float-start position-relative" style="height:50px;">
                <h4>Program Library</h4>
            </div>
            <div class="col-7 float-end" style="height:50px;">
                <button class="trans_btn float-end" @click="quitComponent" style="right:15px;top:10px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <button v-if="!showSearch" @click="searchBar" style="font-size:20px;margin-top:3px;border:none;float:right;background-color:transparent;">
                    <i class="fa-solid fa-magnifying-glass" ></i>
                </button>
                <div v-else class="col-9 float-end" style="height:50px;background-color:#F7F7F7;border:none;border-radius:5px;padding:5px;">
                    <div class="col-11 mx-auto" style="height:30px;border:1px solid #C5C5C5;margin-top:5px;border-radius:5px;padding:3px">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input class="inp1 col-10" type="text" placeholder="Find a program" v-model="search">
                    </div>
                </div>
            </div>
            <div class="col-12 float-start" style="height:calc(100% - 60px);overflow-y:auto;">
                <div class="col-12 row">
                    <div class="col-3 ps-3 float-left" v-for="(prog, index) in filteredPrograms" :key="prog.id" style="height:320px;margin-top:10px;">
                        <div class="tsl p-1 brds-2">
                            <div class="mx-auto p-1" style="height:170px;border-radius:10px;">
                                <img v-if="prog.image!=null" :src="prog.image" alt="" class="img-fluid" style="width:280px;height:180px;">
                                <img v-else src="/images/download1.png" alt="" class="img-fluid" >
                            </div>
                            <p class="mt-3 mb-1 ms-2" style="font-size:15px;font-weight:bold;">{{prog.title}}</p>
                            <p class="mt-1 mb-1 ms-2" style="font-size:9px;font-weight:bold;">0 feet taps hit</p>
                            <p class="mt-1 mb-1 ms-2" style="font-size:8px;">0 feet taps hit</p>
                            <p class="mt-1 mb-1 ms-2" style="font-size:9px;font-weight:bold;">0 feet taps hit</p>
                            <p class="mt-1 mb-1 ms-2" style="font-size:8px;">0 feet taps hit</p>
                        </div>
                    </div>
                </div>
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
    name: 'browseExercise',
    components: { Loader, Inform, Confirm },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            showSearch: false,
            detailOpen: [],
            programs: [],
            pageLoading: false,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            search: "",
        }
    },
    mounted() {
        this.getAllPrograms();
    },
    computed: {
        filteredPrograms() {
            return this.programs.filter((prog) => {
                return prog.title.toLowerCase().includes(this.search.toLowerCase());
            });
        }
    },
    methods: {
        searchBar() {
            this.showSearch = true;
        },
        quitComponent() {
            this.$parent.toogleBrowseLibrary();
        },
        getAllPrograms() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'get-all-programs', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.programs = res.data.data;
                    for (let index = 0; index < this.programs.length; index++) {
                        this.detailOpen.push(false);
                    }
                }
                else {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error in get all programs", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = res.data.message;
                this.informModal = true;
                console.log("Error in get all program", er.error);
            });
        },
    }
}
</script>
<style scoped>
.inp1 {
    border: none;
    background-color: transparent;
    font-size: 10px;
    height: 20px;
}

.inp1::placeholder {
    font-size: 10px;
    color: #343434;
}
</style>
