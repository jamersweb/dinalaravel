<template lang="">
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="my-popup-component" @click.self="quitComponent" style="z-index:99999 !important">
        <div class="bg-white px-3 py-5 brds-5 position-relative col-11 col-md-8 col-lg-6">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="h-100 px-2 px-md-5">
                <p class="mb-2">Click on a Program to subscribe:</p>
                <div class="position-relative my-2">
                    <input class="searchinput" type="search" placeholder="Search Users" v-model="search"/>
                    <img class="searchab" src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" />
                </div>
                <div class="gray_bg p-3 brds-2">
                    <div class="border brds-2 bg-white" style="height:250px; overflow-y:auto">
                        <div @click="programSelected(item.id,index)" class="my-2 mx-3" v-for="(item,index) in filteredPrograms">
                            <span>{{item.title}}</span>
                            <span class="float-end">{{item.weeks}} weeks</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Loader from '../../components/loader.vue';
import Confirm from '../../components/confirm.vue';
import Inform from '../../components/inform.vue';
import config from '../../config';
export default {
    components: { Loader, Confirm, Inform },
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
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            search: "",
            allPrograms: [],
            programId: null,
        }
    },
    computed: {
        filteredPrograms() {
            return this.allPrograms.filter((items) => {
                return items.title.toLowerCase().includes(this.search.toLowerCase());
            })
        }
    },
    mounted() {
        this.pageLoading = true;
        this.loaderText = 'Fetching';
        axios.get(config.baseApiUrl + 'get-all-programs', this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.allPrograms = res.data.data;
            }
            else {
                this.modalTitle = 'Error';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Error';
            this.modalDetail = er;
            this.informModal = true;
        })
    },
    methods: {
        programSelected(m, n) {
            this.programId = m;
            this.modalTitle = 'Are you sure?';
            this.modalDetail = 'Assign ' + this.filteredPrograms[n].title + ' to slected clients';
            this.confirmModal = true;
        },
        confirmationResponse(value) {
            if (value == 1) {
                this.$parent.subscribeProgram(this.programId);
            }
            else {
                this.$parent.subscribeProgramPopup();
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        quitComponent() {
            this.$parent.subscribeProgramPopup();
        }
    }
}
</script>
<style scoped>
.searchinput {
    border: 1px solid #c5c5c5;
    border-radius: 11px;
    padding: 8px;
    width: 100%;
    padding-left: 25px;
    margin-left: 0px;
    font-size: 14px;
}

.searchab {
    position: absolute;
    width: 15px;
    top: 12px;
    left: 8px;
}
</style>
