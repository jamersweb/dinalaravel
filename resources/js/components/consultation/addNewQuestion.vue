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
            <div class="col-11 mx-auto d-flex justify-content-between mt-3">
                <div class="fw-bold"><p class="mb-0">Question Order: <span class="fw-light">{{questionData.order}}</span></p></div>
            </div>
            <div class="col-11 mx-auto d-flex justify-content-between mt-3">
                <div class="col-2 d-flex flex-column justify-content-around fw-bold">Question Statement:</div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">English</p>
                    <textarea class="col-12 brds-2" v-model="questionData.question_en" placeholder="Enter Question in English" style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">Arabic</p>
                    <textarea class="col-12 brds-2" v-model="questionData.question_ar" placeholder="Enter Question in Arabic" style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
            </div>
            <div class="col-11 mt-3 mx-auto" style="height:30px;">
                <p class="fw-bold mb-0 col-3 float-start">Type:</p>
                <select class="brds-2" style="border:1px solid #C5C5C5;" v-model="questionData.type">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="single_choice">Single Choice</option>
                    <option value="fill_blank">Fill the Blank</option>
                </select>
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
                    <textarea class="col-12 brds-2 p-2" v-model="questionData.note_en" placeholder="Enter Note in English"  style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
                <div class="col-4">
                    <p class="col-12 mb-1 fw-bold">Arabic</p>
                    <textarea class="col-12 brds-2 p-2" v-model="questionData.note_ar" placeholder="Enter Note in Arabic"  style="height:80px;border:1px solid #C5C5C5;"></textarea>
                </div>
            </div>
            <div v-if="questionData.type!=='fill_blank'" class="col-11 mx-auto pb-3" style="min-height:30px">
                <div class="text-end mt-2 position-relative" style="height:30px;">
                    <button @click="addOption()" class="prim_bg rounded-circle border-0 position-absolute" style="height:25px;width:25px;right:-30px;">+</button>
                </div>
                <div v-for="(item, index) in questionData.options" :key="index" class=" d-flex justify-content-between my-1 position-relative" style="height:30px;">
                    <p class="mb-0 col-2 fw-bold">Option{{index+1}}:</p>
                    <input type="text" class="brds-2 col-4 px-2" placeholder="Enter Option in English" v-model="item.en" style="border:1px solid #C5C5C5;">
                    <input type="text" class="brds-2 col-4 px-2" placeholder="Enter Option in Arabic" v-model="item.ar" style="border:1px solid #C5C5C5;">
                    <button @click="removeOption(index)" class="prim_bg rounded-circle border-0 position-absolute" style="height:25px;width:25px;right:-30px;">-</button>
                </div>
            </div>
            <div class="col-11 mx-auto mb-3" style="min-height:30px">
                <div class="text-center">
                    <button @click="createQuestion" class="px-3 py-1 brds-2 prim_bg border-0 me-1">Save</button>
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
    props: ['totalQuestions'],
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
            questionData: {
                'order': null,
                'question_en': null,
                'question_ar': null,
                'type': 'multiple_choice',
                'tag': 'NO',
                'note_en': null,
                'note_an': null,
                'options': [
                    {
                        'en': null,
                        'ar': null,
                    },
                    {
                        'en': null,
                        'ar': null,
                    },
                ]
            },
        }
    },
    mounted() {
        this.questionData.order = this.totalQuestions + 1;
    },
    methods: {
        quitComponent() {
            this.$parent.hideAddNewQuestion();
        },
        acknowledged() {
            this.informModal = false;
        },
        removeOption(m) {
            this.questionData.options.splice(m, 1);
        },
        addOption() {
            if(this.questionData.options.length>29)
            return;
            let temp = {
                'en': null,
                'ar': null,
            }
            this.questionData.options.push(temp);
        },
        createQuestion() {
            if (this.questionData.question_en == '' || this.questionData.question_en == null || this.questionData.question_ar == '' || this.questionData.question_ar == null) {
                this.modalTitle = 'Error';
                this.modalDetail = 'Question Field is required in both Arabic and English';
                this.informModal = true;
                return
            }
            if (this.questionData.type !== 'fill_blank') {
                if (this.questionData.options.length < 2) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Add minimun 2 option for the question';
                    this.informModal = true;
                    return
                }
                for (let index = 0; index < this.questionData.options.length; index++) {
                    if (this.questionData.options[index].en == null || this.questionData.options[index].en == '' || this.questionData.options[index].ar == null || this.questionData.options[index].ar == '') {
                        this.modalTitle = 'Error';
                        this.modalDetail = 'Option are required in both English and Arabic';
                        this.informModal = true;
                    }
                }
            }
            this.pageLoading = true;
            this.loaderText = 'Creating';
            axios.post(config.baseApiUrl + 'create-question', this.questionData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$parent.hideAddNewQuestion(1);
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
