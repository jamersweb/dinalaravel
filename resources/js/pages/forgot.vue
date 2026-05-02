<template lang="">
    <div class="login_cont">
        <div class="logo-img">
            <img src="/cms-assets/images/login/logo.png" alt="" class="img-fluid">
        </div>
        <div class="lgn-form">
            <div class="mt-3 text-center">
                <h5 style="margin-bottom: 35px; font-size: 24px;"><strong><span class="px-1" style="color: #f2a18c;background-color:black">FORGOT</span> PASSWORD</strong></h5>
            </div>
            <div v-if="!otpVerified">
                <div class="mt-3 text-center" v-if="!otpSent">
                    <p class="text-muted">Enter your registered Email Address to get Email to reset your password</p>
                </div>
                <div class="mt-3 text-center" v-if="otpSent">
                    <p class="text-muted">Check your Email, we've sent an Email to given Email Address</p>
                </div>

                <div class="w-100 position-relative mt-5" v-if="!otpSent">
                    <input type="text" required v-model="emailValue" @input="hideErrors">
                    <p>Email Address</p>
                    <img src="/cms-assets/images/login/mail.png" alt="" style="position: absolute;max-height: 17px;top:20px;left:25px;">
                </div>
                <div class="w-100 d-flex justify-content-end" v-if="!otpSent">
                    <router-link to="/cms/login" style="font-size: 19px; margin-right: 20px;" class="text-dark fw-bold">Back</router-link>
                </div>
                <!-- <div class="w-100 position-relative mt-4" v-if="otpSent">
                    <input type="number" required v-model="otpValue" @input="hideErrors" class="otp-input" onkeypress="if ( isNaN( String.fromCharCode(event.keyCode) )) return false;">
                    <p>Email has been sent to your registered Email Address</p>
                    <img src="/cms-assets/images/login/lock.png" alt="" style="position: absolute;max-height: 20px;top:17px;left:15px;">
                </div> -->
                <div class="mt-1" v-if="errors">
                    <p class="text-danger">*{{errorContent}}</p>
                </div>

                <div class="mt-4 text-center mb-5" v-if="!otpSent">
                    <button @click="sendOTP">
                        <span v-if="!loading">Send</span>
                        <i v-else class="fa-solid fa-spinner fa-spin"></i>
                    </button>
                </div>
                <!-- <div class="mt-4 text-center mb-5" v-if="otpSent">
                    <button @click="verifyOTP">
                        <span v-if="!loading">Verify</span>
                        <i v-else class="fa-solid fa-spinner fa-spin"></i>
                    </button>
                </div> -->
            </div>
            <div v-if="otpVerified">
                <div class="mt-3 text-center">
                    <p class="text-muted">Email verified, Update your Password</p>
                </div>
                <div class="w-100 position-relative mt-5">
                    <input type="password" required v-model="newPass" @input="hideErrors">
                    <p>New Password</p>
                    <img src="/cms-assets/images/login/lock.png" alt="" style="position: absolute;max-height: 17px;top:20px;left:15px;">
                </div>
                <div class="w-100 position-relative mt-4">
                    <input type="password" required v-model="cnfrmPass" @input="hideErrors">
                    <p>Confirm Password</p>
                    <img src="/cms-assets/images/login/lock.png" alt="" style="position: absolute;max-height: 20px;top:17px;left:15px;">
                </div>
                <div class="mt-1" v-if="errors">
                    <p class="text-danger">*{{errorContent}}</p>
                </div>
                <div class="mt-4 text-center mb-5">
                    <button @click="updatePassword">
                        <span v-if="!loading">Update</span>
                        <i v-else class="fa-solid fa-spinner fa-spin"></i>
                    </button>
                </div>
            </div>
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
            otpSent: false,
            otpVerified: false,
            errors: false,
            errorContent: "",
            loading: false,
            emailValue: null,
            otpValue: null,
            newPass: null,
            cnfrmPass: null,
            token: null,
        }
    },
    mounted() {

        if (!this.loggedIn()) {
            this.$emit('hideBarsEvent');
            if (this.$route.query.token) {
                this.token = this.$route.query.token;
                console.log(this.token);
                this.otpVerified = true;
            }
        }
    },
    methods: {
        hideErrors() {
            this.errors = false;
        },
        sendOTP() {
            if (this.emailValue == null || this.emailValue == '') {
                this.errors = true;
                this.errorContent = "Please enter a valid email"
                return;
            }
            this.loading = true;
            axios.post(config.baseApiUrl + 'forgotPassword', {
                email: this.emailValue,
            }).then(res => {
                this.loading = false;
                if (res.data.status == 1) {
                    this.errors = false;
                    this.otpSent = true;
                }
                else {
                    this.errors = true;
                    this.errorContent = res.data.message;
                }
            }).catch(er => {
                this.loading = false;
                this.errors = true;
                this.errorContent = "Something Went Wrong";
                console.log("Error: ", er.message);
            });
        },
        verifyOTP() {
            if (this.otpValue == null || this.otpValue == "") {
                this.errors = true;
                this.errorContent = "Please enter 4 digit OTP";
                return
            }
            this.loading = true;
            axios.post(config.baseApiUrl + 'verify-otp', {
                email: this.emailValue,
                otp: this.otpValue
            }).then(res => {
                this.loading = false;
                if (res.data.status == 1) {
                    this.errors = false;
                    this.otpVerified = true;
                }
                else {
                    this.errors = true;
                    this.errorContent = res.data.message;
                }
            }).catch(er => {
                this.loading = false;
                this.errors = true;
                this.errorContent = "Something Went Wrong, Try Again";
                console.log("Error: ", er);
            });
        },
        updatePassword() {
            if (this.newPass == null || this.newPass == "" || this.cnfrmPass == null || this.cnfrmPass == "") {
                this.errors = true;
                this.errorContent = "Please fill both fields."
                return
            }
            else {
                if (this.newPass == this.cnfrmPass) {
                    this.loading = true;
                    axios.post(config.baseApiUrl + 'update-password-admin', {
                        token: this.token,
                        password: this.newPass
                    }).then(res => {
                        this.loading = false;
                        if (res.data.status == 1) {
                            this.$router.push("/cms/login");
                        }
                        else {
                            this.errors = true;
                            this.errorContent = res.data.messege;
                        }
                    }).catch(er => {
                        this.loading = false;
                        this.errors = true;
                        this.errorContent = 'Error, Something went wrong';
                        console.log("Error: ", er);
                    });
                } else {
                    this.errors = true;
                    this.errorContent = "Passwords does not match"
                    return
                }
            }
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
}
</script>
<style scoped>
.login_cont {
    background-image: url('/cms-assets/images/login/bgrnd2.JPG');
    background-size: cover;
    width: 100%;
    height: 100vh;
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
    min-height: 250px;
    right: 100px;
    top: 80px;
    padding: 20px;
}

.lgn-form input {
    border: 0.5px solid rgb(194, 194, 194);
    border-radius: 2rem;
    padding: 15px;
    padding-left: 70px;
    width: 100%;
    margin-bottom: 15px;
}

.lgn-form input:focus {
    outline: none;
}

.lgn-form .position-relative p {
    position: absolute;
    color: gray;
    top: 17px;
    left: 60px;
    pointer-events: none;
    background-color: white;
    padding-right: 10px;
    padding-left: 10px;
}

.lgn-form button {
    background-color: #f2a18c;
    color: white;
    font-weight: bold;
    font-size: 24px;
    border: none;
    padding: 15px;
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

.lgn-form input:valid+p {
    top: -10px !important;
    left: 30px !important
}

/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type=number] {
    -moz-appearance: textfield;
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
</style>
