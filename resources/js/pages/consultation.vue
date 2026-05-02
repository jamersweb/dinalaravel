<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="col-12 brds-3 position-relative" style="height:calc(100vh - 125px);border:1px solid #C5C5C5;">
        <div class="w-100 d-flex justify-content-between align-items-center" style="border-top-right-radius:14px;border-top-left-radius:14px;background-color:#E7E7E7;height:50px;">
            <p class="ms-4 h-100 mb-0" style="font-size:26px;font-weight:bold;margin-top:7px;">Consultation Form</p>
            <button @click="showAddNewQuestion" class="prim_bg px-4 py-1 brds-2 border-0 fw-bold me-3" style="height:30px;">Add New</button>
        </div>
        <div class="col-12 px-3" style="height:calc(100% - 50px);overflow-y:auto;">
            <div class="col-12" v-for="(item, index) in consultationForm" :key="index">
                <div v-if="index!==0" class="col-12" style="height:1px;background-color:#C5C5C5;"></div>
                <div class="col-12 py-2 d-flex justify-content-around">
                    {{index+1}}.<p class="col-10 mb-0">
                        <div class="col-9 float-start" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{item.question_en}}</div>
                        <span class="text-muted col-3 float-start">({{item.type}})</span>
                    </p>
                    <button @click="showDetails(item)" class="prim_bg px-4 brds-2 border-0">View</button>
                </div>
            </div>
        </div>
    </div>
    <ConsultationDetail v-if="viewDetail" :QuestionDetails="JSON.stringify(detail)"/>
    <AddNewQuestion v-if="newQuestionPopup" :totalQuestions="consultationForm.length"/>
</template>
<script>
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
import ConsultationDetail from "../components/consultation/consultationDetail.vue";
import AddNewQuestion from '../components/consultation/addNewQuestion.vue';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { Loader, Inform, ConsultationDetail, AddNewQuestion },
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
            consultationForm: null,
            viewDetail: false,
            detail: null,
            newQuestionPopup: false,
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getConsultationForm();
    },
    methods: {
        showAddNewQuestion() {
            this.newQuestionPopup = true;
        },
        hideAddNewQuestion(m) {
            if (m == 1) {
                this.getConsultationForm();
                this.newQuestionPopup = false;
            }
            this.newQuestionPopup = false;
        },
        getConsultationForm() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'consultation-form', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.consultationForm = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er.message;
                    this.informModal = true;
                })
        },
        showDetails(m) {
            this.detail = m;
            this.viewDetail = true;
        },
        hideDetails(m) {
            if (m == 1) {
                this.getConsultationForm();
                this.viewDetail = false;
            }
            this.viewDetail = false;
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>

</style>
