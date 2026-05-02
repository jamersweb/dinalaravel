<template lang="">
    <div class="my-popup-component" @click.self="quitComponent">
        <div class="position-relative w-80 brds-4 py-3 px-4" style="height:90vh;overflow-y:auto;background-color:white;">
            <button class="trans_btn position-absolute" @click="quitComponent" style="right:10px;top:10px;font-size:25px;z-index:9;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="my-3">Automated In-app Messages</h3>
            <div v-if="allMessages" v-for="(item, index) in allMessages" :key="index" class="col-12 mt-0 float-start" style="height:45px;">
                <div class="col-12" style="height:2px;background-color:#c5c5c5;"></div>
                <div class="col-4 my-2 float-start d-flex flex-wrap justify-content-between">
                    <!-- <p>Instant</p> -->
                    <p style="text-transform:capitalize;">{{item.send_on}}</p>
                </div>
                <div class="col-6 my-2 float-end text-end">
                    <div class="form-check form-switch float-end">
                        <input @click="changeStatus(item.id)" v-if="item.status==0" class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault">
                        <input @click="changeStatus(item.id)" v-else class="form-check-input" type="checkbox" role="switch" id="flexSwitchCheckDefault" checked>
                    </div>
                    <button @click="showCustomizeMessage(item)" class="float-end border-0" style="background-color:transparent;color:#F2A18C">Customizable</button>
                </div>
            </div>
            <div class="col-12 float-start" style="height:2px;background-color:#c5c5c5;"></div>
        </div>
    </div>
    <customizeAutomatedMessage v-if="customizeMessagePopup" :autoMessage="item"/>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
</template>
<script>
import customizeAutomatedMessage from './customizeAutomatedMessage.vue';
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { customizeAutomatedMessage, Loader, Inform },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            customizeMessagePopup: false,
            allMessages: null,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            item: null,
            content: null,
            contentType: null
        }
    },
    mounted() {
        this.getAllMessages();
    },
    methods: {
        getAllMessages() {
            this.allMessages = null;
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'auto-messages', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.allMessages = res.data.data;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        changeStatus(id) {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'switch-auto-msg-status/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.getAllMessages();
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        showCustomizeMessage(m) {
            this.item = m;
            this.customizeMessagePopup = !this.customizeMessagePopup;
        },
        quitComponent() {
            this.$parent.showautoMessagePopup();
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style lang="">

</style>
