<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="my-popup-component" @click.self="quitComponent">
        <div class="w-80 brds-3 bg-white position-relative" style="height:80vh;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="col-11 mx-auto d-flex justify-content-around mt-3">
                <h3 class="mb-0 mt-2 fw-bold">Consultation Form Question</h3>
            </div>
            <div class="col-11 mx-auto d-flex justify-content-start mt-3">
                <div class="fw-bold">Question Order: <input type="number" class="fw-light ms-2 brds-2 text-center" style="border:1px solid #C5C5C5;width:50px;" v-model="questionData.order"></div>
            </div>
            <div class="col-11 mx-auto d-flex justify-content-between mt-3">
                <div class="col-2 d-flex flex-column justify-content-around fw-bold">Question Statement:</div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">English</p>
                    <textarea class="col-12 brds-2" v-model="questionData.question_en" style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">Arabic</p>
                    <textarea class="col-12 brds-2" v-model="questionData.question_ar" style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
            </div>
            <div class="col-11 mt-3 mx-auto" style="height:30px;">
                <p class="fw-bold mb-0 col-3 float-start">Type:</p>
                <select v-if="questionData.type!=='select'" class="brds-2" style="border:1px solid #C5C5C5;" v-model="questionData.type">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="single_choice">Single Choice</option>
                    <option value="fill_blank">Fill the Blank</option>
                </select>
                <p v-else class="text-muted">{{questionData.type}}</p>
            </div>
            <div class="col-11 mt-3 mx-auto" style="height:30px;">
                <p class="mb-0 col-3 float-start fw-bold">Tag:</p>
                <div class="col-9 float-start">
                    <div class="float-start">
                        <input type="radio" id="answer" v-model="questionData.tag" name="fav_language" value="ANSWER">
                        <label class="ms-1" for="answer">Yes</label><br>
                    </div>
                    <div class="float-start ms-2">
                        <input type="radio" id="no" v-model="questionData.tag" name="fav_language" value="NO">
                        <label class="ms-1" for="no">No</label><br>
                    </div>
                </div>
            </div>
            <div class="col-11 mx-auto d-flex justify-content-between mt-3" style="height:120px;">
                <div class="col-2 d-flex flex-column justify-content-around fw-bold">Note:</div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">English</p>
                    <textarea class="col-12 brds-2 p-2" v-model="questionData.note_en"  style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">Arabic</p>
                    <textarea class="col-12 brds-2 p-2" v-model="questionData.note_ar"  style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
            </div>
            <div v-if="questionData.type!=='fill_blank'&&questionData.type!=='select'" class="col-11 mx-auto pb-3" style="min-height:30px">
                <div class="text-end mt-2 position-relative" style="height:30px;">
                    <button @click="addOption()" class="prim_bg rounded-circle border-0 position-absolute" style="height:25px;width:25px;right:-30px;">+</button>
                </div>
                <div v-for="(item, index) in questionData.options_en" :key="index" class=" d-flex justify-content-between my-1 position-relative" style="height:30px;">
                    <p class="mb-0 col-2 fw-bold">Option{{index+1}}:</p>
                    <input type="text" class="brds-2 col-4 px-2" v-model="questionData.options_en[index]" style="border:1px solid #C5C5C5;">
                    <input type="text" class="brds-2 col-4 px-2" v-model="questionData.options_ar[index]" style="border:1px solid #C5C5C5;">
                    <button @click="removeOption(index)" class="prim_bg rounded-circle border-0 position-absolute" style="height:25px;width:25px;right:-30px;">-</button>
                </div>
            </div>
            <div class="col-11 mx-auto mb-3" style="min-height:30px">
                <div class="text-center">
                    <button @click="updateQuestion" class="px-3 py-1 brds-2 prim_bg border-0 me-1">Update</button>
                    <button @click="deleteQuestion" class="px-3 py-1 brds-2 prim_bg border-0 ms-1">Delete</button>
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
    props: ['QuestionDetails'],
    components: { Loader, Inform },
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
            questionData: {},
        }
    },
    mounted() {
        this.questionData = JSON.parse(this.QuestionDetails);
    },
    methods: {
        quitComponent() {
            this.$parent.hideDetails();
        },
        acknowledged() {
            this.informModal = false;
        },
        removeOption(m) {
            this.questionData.options_en.splice(m, 1);
            this.questionData.options_ar.splice(m, 1);
        },
        addOption() {
            if(this.questionData.options_en.length>29)
            return;
            this.questionData.options_en.push(null);
            this.questionData.options_ar.push(null);
        },
        updateQuestion() {
            if (this.questionData.order < 1 || this.questionData.order == null) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Order cannot be less than one or null';
                this.informModal = true;
                return
            }
            if (this.questionData.question_en == '' || this.questionData.question_en == null || this.questionData.question_ar == '' || this.questionData.question_ar == null) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Question Field is required in both Arabic and English';
                this.informModal = true;
                return
            }
            const postData = {
                'qId': this.questionData.id,
                'type': this.questionData.type,
                'tag': this.questionData.tag,
                'question_en': this.questionData.question_en,
                'question_ar': this.questionData.question_ar,
                'note_en': this.questionData.note_en,
                'note_ar': this.questionData.note_ar,
                'options': [],
                'order': this.questionData.order,
            }
            for (let index = 0; index < this.questionData.options_en.length; index++) {
                let temp = {
                    "en": this.questionData.options_en[index],
                    "ar": this.questionData.options_ar[index]
                }
                if (postData.type !== 'fill_blank') {
                    if (temp.ar == null || temp.ar == '' || temp.en == null || temp.en == '') {
                        this.modalTitle = 'Error';
                        this.modalDetail = 'Option are required in both English and Arabic';
                        this.informModal = true;
                        return
                    }
                }
                postData.options.push(temp)
            }
            if (postData.type !== 'fill_blank' && postData.type !== 'select') {
                if (postData.options.length < 2) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Add minimun 2 option for the question';
                    this.informModal = true;
                    return
                }
            }
            this.pageLoading = true;
            this.loaderText = 'Updating';
            axios.post(config.baseApiUrl + 'update-question', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$parent.hideDetails(1);
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        acknowledged() {
            this.informModal = false;
        },
        deleteQuestion() {
            this.pageLoading = true;
            this.loaderText = 'Deleting';
            axios.get(config.baseApiUrl + 'delete-question/' + this.questionData.id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$parent.hideDetails(1);
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        }
    }
}
</script>
<style lang="">

</style>
