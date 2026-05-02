<template lang="">
    <div class="login_cont">
        <div class="logo-img">
            <img src="/cms-assets/images/login/logo.png" alt="" class="img-fluid">
        </div>
        <div class="lgn-form">
            <div v-if="errors" style="margin-bottom: -25px;margin-top: 25px;" >
                <p style="font-size: 14px; font-weight: normal; margin-bottom: 0px; color:#6A6A6A;">*{{this.errorContent}} </p>
            </div>
            <form>
                <div class="w-100 position-relative mt-5">
                    <input type="email" style="font-size: 20; font-weight:600;" placeholder=" " v-model="email" v-on:input="hideMailError">
                    <p style="font-size: 17px;">Email Address</p>
                    <img src="/cms-assets/images/login/mail.png" alt="" style="position: absolute;max-height: 17px;top:20px;left:28px;">
                </div>
                <div class="mt-1" v-if="mailErr">
                    <p class="text-danger h7 mb-0 ms-2">*Email is required</p>
                </div>
                <div class="w-100 position-relative mt-4">
                    <input type="password" style="font-size: 20; font-weight:600;" required v-model="password" v-on:input="hidePassError">
                    <p style="font-size: 17px;">Password</p>
                    <img src="/cms-assets/images/login/lock.png" alt="" style="position: absolute;max-height: 20px;top:17px;left:28px;">
                </div>
                <div class="mt-1" v-if="passErr">
                    <p class="text-danger h7 mb-0 ms-2">*Password is required</p>
                </div>
                <div class="mt-3 w-100 d-flex justify-content-end">
                    <router-link to="/cms/forgot-password" style="font-size: 19px; margin-right: 20px;" class="text-dark fw-bold">Forgot Password?</router-link>
                </div>
                <div class="mt-4 text-center mb-5">
                    <button class="position-relative" @click="login" :disabled="loading">
                        <span v-if="!loading">Log In</span>
                        <i v-else class="fa-solid fa-spinner fa-spin"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../config';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    name: 'Login',
    data() {
        return {
            errors: false,
            errorContent: "",
            mailErr: false,
            passErr: false,
            loading: false,
            errortext: false,
            email: null,
            password: null,
        }
    },
    methods: {
        hideMailError() {
            this.mailErr = false;
        },
        hidePassError() {
            this.passErr = false;
        },
        login(e) {
            e.preventDefault();
            if (this.email == null || this.email == "") {
                this.mailErr = true;
            }
            if (this.password == null || this.password == "") {
                this.passErr = true;
            }
            if (this.mailErr == true || this.passErr == true) {
                return
            }
            this.loading = true;
            axios.post(config.baseApiUrl + 'login', {
                email: this.email,
                password: this.password,
            }).then(res => {
                this.loading = false;
                let response = res.data;
                if (response.status == false) {
                    this.errors = true;
                    this.errorContent = response.message;
                }
                else {
                    config.storage.setItem('fwd_session_token', response.login_token);
                    this.$emit('showBarsEvent');
                    this.$router.push('/cms/overview');
                }
            }).catch(err => {
                this.loading = false;
                this.errors = true;
                this.errortext = true;
                this.errorContent = 'Something Went Wrong.';
                console.log("Error: ", err.message);
            });
        },
        loggedIn() {
            const token = config.storage.getItem('fwd_session_token');
            if (token != null) {
                this.$router.push('/cms/overview');
                return true;
            }
            return false;
        }
    },
    mounted() {
        if (!this.loggedIn()) {
            this.$emit('hideBarsEvent');
        }
    },
}
</script>
<style scoped>
.loaderOn {
    display: flex !important;
    background-color: #f2a18c;
}

.login_cont {
    background-image: url('/cms-assets/images/login/bgrnd2.JPG');
    background-size: cover;
    width: 100%;
    min-height: 100vh;
    height: 100%;
    position: relative;
}

.logo-img {
    position: absolute;
    top: 60px;
    left: 80px;
    width: 180px;
    height: fit-content;
}

.lgn-form {
    background-color: white;
    border-radius: 1rem;
    width: 400px;
    position: absolute;
    right: 100px;
    top: 80px;
    padding: 20px;
}

@media screen and (max-width:720px) {
    .lgn-form {
        width: 90% !important;
        top: 150px !important;
        left: 5% !important;
    }

    .logo-img {
        top: 40px !important;
        left: 35% !important;
    }
}

.lgn-form input {
    border: 0.5px solid rgb(194, 194, 194);
    border-radius: 2rem;
    padding: 15px;
    padding-left: 75px;
    width: 100%;
}

.lgn-form input:focus {
    outline: none;
}

.lgn-form .position-relative p {
    position: absolute;
    color: gray;
    top: 17px;
    left: 65px;
    pointer-events: none;
    background-color: white;
    padding-right: 10px;
    padding-left: 10px;
}

.lgn-form button {
    background-color: #f2a18c;
    color: white;
    font-weight: bold;
    font-size: 25px;
    border: none;
    padding: 15px;
    margin-top: 15px;
    height: 75px;
    width: 90%;
    border-radius: 10px;
}

.lgn-form button:hover {
    background-color: #f88c71;
}

.lgn-form input:focus+p {
    top: -10px !important;
    left: 30px !important
}

.lgn-form input[type="password"]:valid+p {
    top: -10px !important;
    left: 30px !important
}

.lgn-form input[type="email"]:not(:placeholder-shown)+p {
    top: -10px !important;
    left: 30px !important
}
</style>
