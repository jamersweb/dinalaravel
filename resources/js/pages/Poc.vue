<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div class="w-100 brds-3 position-relative" style="height:calc(100vh - 125px);overflow-y:auto;border:1px solid #C5C5C5;">
        <div class="w-100" style="border-top-right-radius:10px;border-top-left-radius:10px;background-color:#E7E7E7;height:50px;">
            <p class="ms-4 my-0 pt-1" style="font-size:26px;font-weight:bold;margin-top:7px;">Translate Arabic to English</p>
        </div>
        <div class="col-11 mx-auto mt-4 text-center">
            <textarea v-model="enteredText" class="brds-2 tsl px-3 py-1" style="width:90%;height:80px;border:none;" placeholder="Enter text in Arabic to translate"></textarea>
            <textarea v-model="translatedText" readonly class="brds-2 tsl mt-3 px-3 py-1" style="width:90%;height:80px;border:none;" placeholder="Translated text"></textarea>
            <button @click="getTranslation()" class="px-4 py-1 brds-2 prim_bg border-0 my-3">Translate</button>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
export default {
    components: { Loader, Inform },
    props: ['logInProps'],
    data() {
        return {
            enteredText: null,
            translatedText: null,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
        }
    },
    methods: {
        getTranslation() {
            if (this.enteredText == null || this.enteredText == '') {
                this.modalTitle = 'Error';
                this.modalDetail = 'Enter the text to translate';
                this.informModal = true;
                return
            }
            this.pageLoading = true;
            this.loaderText = 'Translating';
            axios.post('https://translation.googleapis.com/language/translate/v2?q=' + this.enteredText + '&target=en&model=base&key=AIzaSyBs6h0kjPsmjGNNAYbe6-ImZLoznzbNQz4').then(res => {
                console.log(res);
                this.pageLoading = false;
                if (res.status) {
                    this.translatedText = res.data.data.translations[0].translatedText;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.statusText;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style lang="">

</style>
