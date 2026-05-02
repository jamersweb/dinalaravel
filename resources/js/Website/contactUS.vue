<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div style="overflow:hidden;" :class="{fixedposition:position}">
        <Header :title3="'CONTACT US'"/>
        <div class="row position-relative">
            <img src="/cms-assets/images/mainWebsite/Group16757.png" class="position-absolute" style="height:150px;width:200px;top:35px;left:-50px;" alt="">
            <img src="/cms-assets/images/mainWebsite/Group16757.png" class="position-absolute" style="height:150px;width:200px;bottom:50px;right:-50px;" alt="">
            <div class="col-12 px-4 col-sm-10 col-md-8 col-xl-6 py-4 my-4 mx-auto position-relative" style="min-height:450px;">
                <img src="/cms-assets/images/mainWebsite/exercise.png" class="img-fluid float-end col-12 col-sm-11 col-md-10" alt="">
                <div class="position-absolute tsl text-center brds-2 contact">
                    <p class="mb-0 text-center fw-bold text-dark mt-1 mt-sm-2 mt-md-4 f-40">GET IN TOUCH</p>
                    <div class="col-11 mx-auto tsl  brds-2 px-2 mt-2 f-20">
                        <input v-model="postData.name" type="text" class="col-12 border-0" style="height:50px;background-color:transparent;" placeholder="Name">
                    </div>
                    <div class="col-11 mx-auto tsl  brds-2 px-2 mt-2 f-20">
                        <input v-model="postData.email" type="text" class="col-12 border-0" style="height:50px;background-color:transparent;" placeholder="Email">
                    </div>
                    <div class="col-11 mx-auto tsl  brds-2 px-2 mt-2 f-20">
                        <textarea v-model="postData.message" type="text" class="col-12 border-0 f-20" style="height:100px;" placeholder="Message"></textarea>
                    </div>
                    <div class="col-11 mb-2 mx-auto">
                        <button @click="sendMail" class="col-11 mt-3 border-0 brds-2 py-2 text-white f-30" style="background-color:#10161B;">SUBMIT</button>
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
            postData: {
                name: null,
                email: null,
                message: null,
            },
            position: false,
        }
    },
    mounted() {
        this.scrollToTop();
    },
    methods: {
        scrollToTop() {
            window.scrollTo(0, 0)
        },
        changePosition() {
            this.position = !this.position;
        },
        sendMail() {
            let error = false;
            if (this.postData.email == null || this.postData.email == '' || this.postData.name == null || this.postData.name == '' || this.postData.message == null || this.postData.message == '') {
                this.modalTitle = 'Error!';
                this.modalDetail = 'All the fields are required please fill them all';
                this.informModal = true;
                return
            }
            if (this.postData.email != null || this.postData.email == '') {
                var validRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
                if (!this.postData.email.match(validRegex)) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Not a valid Email';
                    this.informModal = true;
                    error = true
                }
            }
            if (error == true) {
                return
            }
            else {
                this.pageLoading = true;
                this.loaderText = 'Sending';
                axios.post('https://fwd.senarios.co/api/send-email-to-admin', this.postData).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.modalTitle = 'Done';
                        this.modalDetail = 'Email send successfully';
                        this.informModal = true;
                        this.postData.email = null;
                        this.postData.name = null;
                        this.postData.message = null;
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
            }
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.contact {
    width: 400px;
    min-height: 350px;
    background-color: white;
    left: -120px;
    bottom: 0px;
}

.main {
    text-align: none;
}

.fixedposition {
    position: fixed;
}

@media screen and (max-width: 770px) {
    .contact {
        left: -30px;
        width: 380px;
    }
}

@media screen and (max-width: 575px) {
    .contact {
        width: 350px;
        left: 20px;
        bottom: -10px;
    }
}

@media screen and (max-width: 380px) {
    .contact {
        width: 230px;
    }
}
</style>
