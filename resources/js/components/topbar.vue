<template lang="">
    <div style="padding-top: 1em !important;
    background-color: #fff;
    padding-left: 0.6em;
    padding-right: 0.6em;">
        <div class="top_bar w-100" style="background-color: #eeeeee ;border-radius:1rem; padding-top: 18px;
    padding-left: 25px;
    padding-right: 18px;
    padding-bottom: 18px;
    margin-bottom: 15px;">
            <div class="d-flex bar">
                <div class="w-100">
                    <div class="position-relative w-100">
                        <input @focusin="clientsDiv=true" @focusout="clientsDiv=false" type="text" v-model="search" placeholder="Find a Client" style="min-width:280px;">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute">
                        <div v-show="clientsDiv" class="position-absolute brds-3 shd_card p-0" style="top:50px ;background-color:white;max-height:200px;width:100%;z-index:9999!important;overflow-y:auto;">
                            <div class="w-100 d-flex justify-content-between client px-3 py-1" style="height:30px;" @mousedown="openClientProfile(items.id)" v-for="(items, index) in filteredClient" :key="index">
                                <p>{{items.full_name}}</p>
                                <p>{{items.subscription}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center mx-3 mt-2 mt-sm-0" style="position: relative;">
                    <div class="d-flex border-end border-secondary">
                        <div class="bg-white rounded-circle border me-2 position-relative" style="padding: 8px 11px;width:45px;height:45px">
                            <span v-if="unread_count!==0&&unread_count!==null" class="position-absolute prim_bg rounded-circle" style="top:-4px;right:-4px;height:18px;width:18px;color:white;text-align:center;font-size:12px;">{{unread_count}}</span>
                            <img @click="toggleNotify" src="/cms-assets/images/navbar-topbar/NotificationBell.png" alt="noti" class="img-fluid" style="cursor: pointer;">
                        </div>
                    </div>
                    <div v-if="Notificationpop">

                        <div class="notify">
                            <svg
                            @click="CloseNotify"
                            style="
                            position: absolute;
                            top: 6px;
                            right: 18px;
                            width: 12px;
                            cursor: pointer;
                            "
                            xmlns="http://www.w3.org/2000/svg"
                            width="19.828"
                            height="19.828"
                            viewBox="0 0 19.828 19.828"
                        >
                            <g
                                id="Group_55866"
                                data-name="Group 55866"
                                transform="translate(-952.586 -160.586)"
                            >
                                <line
                                    id="Line_10"
                                    data-name="Line 10"
                                    x1="17"
                                    y2="17"
                                    transform="translate(954 162)"
                                    fill="none"
                                    stroke="#707070"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                                <line
                                    id="Line_11"
                                    data-name="Line 11"
                                    x2="17"
                                    y2="17"
                                    transform="translate(954 162)"
                                    fill="none"
                                    stroke="#707070"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </g>
                        </svg>
                            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #707070; margin-top: 20px;">
                                <p style="font-size: 8px; font-weight: bold; margin-bottom: 10px;">View</p>
                                <p @click="markAllRead" style="font-size: 8px; font-weight: bold; margin-bottom: 10px;cursor:pointer;">Mark all read</p>
                            </div>
                            <div style="overflow: auto;">
                                <div v-for="item of NotificationArray" :class="{'unread': item.status==0}" style="display: flex; margin-top: 15px; border: 1px solid #EDEDED; padding: 5px; border-radius: 8px;">
                                   <div class="col-9" style="display: flex;">
                                       <img :src="item.userImage" alt="" class="img-fluid rounded-circle" style="height:25px;width:25px; margin-right: 5px;">
                                       <div>
                                           <p style="margin-bottom: 0px;font-weight: bold;" class="h8">{{item.title}}</p>
                                           <p style="width: 75%;" class="h8">{{item.content}}</p>
                                       </div>
                                   </div>
                                   <div class="col-3 d-flex flex-column">
                                       <!-- <i class="fa-solid fa-ellipsis ms-auto"></i> -->
                                       <span class="h8">{{item.timeDiff}}</span>
                                   </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex position-relative justify-content-center">
                        <div class="prof-img" style="width:45px;height:45px; margin-right: 10px;margin-left: 10px;">
                            <img :src="logInProps.image" style="width:40px;height:40px;border-radius:50%;margin-right: 15px;">
                        </div>
                        <div class="dropdown">
                            <div class="prof-name" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" >
                                <h3 class="d-flex align-items-center mb-0 profile-name-heading">
                                    <strong class="profile-name-text">{{logInProps.name}}</strong>
                                    <i class="fa-solid fa-angle-down mt-2" style="font-size: 15px;margin-left: 10px;padding-top: 2px;"></i>
                                </h3>
                                <p class="mb-0 profile-role">{{logInProps.role}}</p>
                            </div>
                            <!-- <button class="py-1 border-0" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            </button> -->
                            <ul class="tsl dropdown-menu border-0" aria-labelledby="dropdownMenuButton1">
                                <li class="py-2"><button class="dropdown-item" style="text-align: center;" @click="this.$router.push('/cms/profile')"><img src="/cms-assets/images/G55730.png" alt="" class="img-fluid me-3" style="max-height:25px">Profile</button></li>
                                <li class="py-2"><button class="dropdown-item" style="text-align: center;" @click="logout"><img src="/cms-assets/images/G55730.png" alt="" class="img-fluid me-3" style="max-height:25px">Logout</button></li>
                            </ul>
                        </div>
                        <!-- <div class="position-absolute shd_card prof-opt" v-show="profMenu">
                            <div>
                                <button @click="this.$router.push('/cms/profile')"><img src="/cms-assets/images/Group55752.png" alt="" class="img-fluid me-3" style="max-height:25px">Profile</button>
                                <button @click="logout"><img src="/cms-assets/images/G55730.png" alt="" class="img-fluid me-3" style="max-height:25px">Logout</button>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
        <clientPopup v-if="clientPopupComponent" :idForDetails="clientId" :logInDetails="logInProps"/>
    </div>
<div v-if="Notificationpop" @click="Notificationpop=false" class="position-absolute" style="height:100vh;width:100vw;top:0;right:0;"></div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import clientPopup from '../components/clients/clientPopup.vue'
export default {
    components: { clientPopup },
    props: ['logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            profMenu: false,
            Notificationpop: false,
            NotificationArray: [],
            unread_count: null,
            clientsDiv: false,
            clientPopupComponent: false,
            clientId: null,
            users: [],
            search: '',
        }
    },
    computed: {
        filteredClient() {
            return this.users.filter((items) => {
                return items.full_name.toLowerCase().includes(this.search.toLowerCase());
            });
        }
    },
    mounted() {
        this.getAdminNotification();
        this.getClients();
        this.timer();
    },
    methods: {
        markAllRead() {
            console.log(this.NotificationArray);
            for (let index = 0; index < this.NotificationArray.length; index++) {
                this.NotificationArray[index].status = 1;
            }
        },
        getAdminNotification() {
            const token = config.storage.getItem('fwd_session_token');
            if (!token) return;
            axios.get(config.baseApiUrl + 'admin-notifications', this.apiConfig).then(res => {
                this.NotificationArray = res.data.data.notifications;
                this.unread_count = res.data.data.unread_count;
            }).catch(res => {

            });
        },
        timer() {
            setTimeout(() => {
                this.getAdminNotification();
                this.timer();
            }, 60000);
        },
        openClientProfile(m) {
            this.clientId = m;
            this.ClientPopup();
        },
        ClientPopup() {
            this.clientPopupComponent = !this.clientPopupComponent;
        },
        toggleNotify() {
            this.unread_count = 0;
            this.Notificationpop = !this.Notificationpop;
        },
        CloseNotify() {
            this.Notificationpop = false;
        },
        toggleProfMenu() {
            this.profMenu = !this.profMenu;
        },
        logout() {
            const token = config.storage.getItem('fwd_session_token');
            config.storage.removeItem('fwd_session_token');
            axios.get(config.baseApiUrl + 'logout', {
                headers: {
                    Authorization: 'Bearer ' + token
                }
            });
            this.$router.push('/cms/login');
        },
        getClients() {
            if (!config.storage.getItem('fwd_session_token')) return;
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'active-clients-list', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status)
                    this.users = res.data.data;
                else {
                    console.log(res.data.error);
                }
            }).catch(er => {
                console.log(er.message);
            });
        },
    },
}
</script>
<style scoped>
.unread {
    background-color: rgb(240, 240, 240);
    position: relative;
}

.unread::before {
    content: '';
    width: 10px;
    height: 10px;
    background-color: rgb(250, 0, 0);
    border-radius: 50%;
    position: absolute;
    top: 8px;
    right: 4px;
}

input {
    background-color: white;
    border: 1px solid rgb(167, 166, 166);
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    border-radius: 10px;
    width: 100%;
}

input:focus {
    outline: none;
}

img.position-absolute {
    max-height: 17px;
    top: 12px;
    left: 10px;
}

.prof-opt button {
    background: transparent;
    border: none;
    color: black;
    padding: 5px 10px;
    margin-bottom: 10px;
}

.prof-opt button:hover {
    color: #f2a18c;
}

.prof-opt {
    width: 170px;
    top: 50px;
    right: -10px;
}

.profile-name-heading {
    font-size: 0.75rem;
    line-height: 1.1;
}

.profile-name-text {
    display: inline-block;
    max-width: 170px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-role {
    color: #000;
    font-size: 10px;
    line-height: 1.1;
    margin-top: 2px;
}

.notify {
    position: absolute;
    top: 48px;
    background-color: #fff;
    padding: 17px;
    right: 200px;
    box-shadow: 0 0 10px 0px #f2a18c;
    width: 350px;
    border-radius: 16px;
    overflow: auto;
    height: 500px;
    z-index: 9;
}

.bar {
    flex-wrap: nowrap;
}

.client:hover {
    background-color: #F5F5F5;
    cursor: pointer;
}

@media screen and (max-width: 580px) {
    .bar {
        flex-wrap: wrap;
    }
}
</style>
