<template lang="">
    <div>
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <h5 class="prim_txt mb-0" style="font-size: 16px;"><strong>Welcome Fitness With Dina</strong></h5>
        <p class="h7 mb-0" style="font-size: 12px;">How're you feeling today?</p>
        <div class="row mt-3">
            <div class="col-lg-7">
                <div class="shd_card">
                    <p style="font-size:14px;">We've auto-tagged your clients based on their needs.</p>
                    <div>
                        <img src="/cms-assets/images/Overview/Logo1.png" style="height:22px;width:22px;float:left;">
                        <p class="h7 mb-1 ms-2 float-start">Need help with setting up</p>
                        <br>
                        <ClientsGroup :clientsData="tagsBasedClients.need_help" :clientType="'pending'"/>

                        <img src="/cms-assets/images/Overview/Logo2.png" style="height:22px;width:22px;float:left;">
                        <p class="h7 mb-1 ms-2 float-start">Need new training phases</p>
                        <br>
                        <ClientsGroup :clientsData="tagsBasedClients.need_phases" :clientType="'active'"/>

                        <img src="/cms-assets/images/Overview/Logo3.png" style="height:22px;width:22px;float:left;">
                        <p class="h7 mb-1 ms-2 float-start">New exercise personal bests</p>
                        <br>
                        <ClientsGroup :clientsData="tagsBasedClients.bests" :clientType="'active'"/>

                        <img src="/cms-assets/images/Overview/Logo4.png" style="height:22px;width:22px;float:left;">
                        <p class="h7 mb-1 ms-2 float-start">Training phases ending soon, follow up</p>
                        <br>
                        <ClientsGroup :clientsData="tagsBasedClients.phases_end" :clientType="'active'"/>

                        <img src="/cms-assets/images/Overview/Logo4.png" style="height:22px;width:22px;float:left;">
                        <p class="h7 mb-1 ms-2 float-start">Needs Motivation</p>
                        <br>
                        <ClientsGroup :clientsData="tagsBasedClients.motivation" :clientType="'active'"/>

                        <div class="d-flex justify-content-between mt-4">
                            <router-link class="px-4 mx-0" to="/cms/clients">
                                <button class="bg-none border-0 fw-bold" style="font-size: 14px">
                                    View All Clients
                                </button>
                            </router-link>
                            <button @click="showautoMessagePopup" class="bg-none border-0 fw-bold">
                                <i class="fa-solid fa-gear" style="font-size:15px; padding-right: 5px;"></i>
                                Setup Auto Messages
                            </button>
                        </div>
                    </div>
                </div>
                <ChartBox v-if="businessGrowthData!=null" title='Business Growth (Past 12 Months)' sub='Clients' :data="businessGrowthData"/>
                <ChartBox v-if="signInsPerWeek!=null" title='Client Sign-ins Per Week' sub='Sign-ins' :data="signInsPerWeek"/>
                <ChartBox v-if="workoutsPerWeek!=null" title='Workouts Per Week' sub='Workouts' :data="workoutsPerWeek"/>
                <ChartBox v-if="exercisesPerWeek!=null" title='Exercise Per Week' sub='Exercise' :data="exercisesPerWeek"/>
                <!-- <ChartBox v-if="nutritionPerWeek!=null" title='Average Nutrition Compliance' sub='Nutrition' :data="nutritionPerWeek"/> -->
            </div>
            <div class="col-lg-5 mt-lg-0 mt-5">
                <div class="shd_card" style="max-height:455px;">
                    <div class="d-flex justify-content-between mt-2">
                        <p class="mb-0" style="font-size: 12px;">RECENT ACTIVITIES</p>
                        <div class="tsl" style="text-align:center;">
                            <select v-model="activityCategory" style="width:99%;text-align:center;font-size:15px;border:none;">
                                <option value="0" selected >All</option>
                                <option :value="item.id" v-for="(item, index) in categories" :key="index">{{item.name}}</option>
                            </select>
                        </div>
                        <!-- <button class="bg-none" style="border: 2px solid #F2A18C;"><i class="fa-solid fa-ellipsis-vertical"></i></button> -->
                    </div>
                    <div class="mt-4" style="max-height:380px;overflow-y:auto;">
                        <div class="mt-3" :class="{ pointer :item.activity_target!==null}" @click="showactivityPopup(item)" v-for="item in filteredActivity" :key="item">
                            <Activity :adminActivityData="item"/>
                        </div>
                    </div>
                </div>
                <div class="shd_card mt-4">
                    <div class="d-flex justify-content-between mt-2">
                        <p class="mb-0"><strong>Clients Profiles</strong></p>
                        <!-- <button class="bg-none" style="border: 2px solid #F2A18C;"><i class="fa-solid fa-ellipsis-vertical"></i></button> -->
                    </div>
                    <div class="mt-3" style="max-height:313px;overflow-y:auto;">
                        <Profile :usersData="users"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <activityPopup v-if="activityShow" :id="activity_id" :type="activity_type" :logInDetails="logInProps"/>
    <automatedMessages v-if="autoMessagePopup"/>
    <clientPopup v-if="showClientPopup" :idForDetails="clientId" :logInDetails="logInProps"/>
</template>
<script>
import axios from 'axios';
import config from '../config';
import Loader from '../components/loader.vue';
import Activity from '../components/overview/activity.vue';
import Profile from '../components/overview/client-profile.vue';
import ChartBox from '../components/overview/chart-box.vue';
import ClientsGroup from '../components/overview/clients-group.vue';
import Inform from '../components/inform.vue';
import Confirm from '../components/confirm.vue';
import activityPopup from '../components/overview/activityPopup.vue';
import automatedMessages from '../components/overview/automatedMessages.vue';
import clientPopup from '../components/clients/clientPopup.vue';

export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    components: { Activity, Profile, ChartBox, ClientsGroup, Loader, Inform, Confirm, activityPopup, automatedMessages, clientPopup },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            info: "",
            categories: [],
            users: [],
            pageLoading: false,
            loaderText: '',
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            adminActivities: [],
            filteredActivities: [],
            activityCategory: 0,
            businessGrowthData: null,
            signInsPerWeek: null,
            workoutsPerWeek: null,
            exercisesPerWeek: null,
            nutritionPerWeek: null,
            tagsBasedClients: [],
            activityShow: false,
            autoMessagePopup: false,
            activity_id: null,
            activity_type: null,
            clientId: null,
            showClientPopup: false,
            apiCalled: 0,
            componentMounted: true,
        }
    },
    computed: {
        filteredActivity() {
            return this.adminActivities.filter((items) => {
                if (this.activityCategory == 0) {
                    return items;
                }
                else {
                    return items.category_id == this.activityCategory;
                }
            });
        }
    },
    mounted() {
        this.$emit('checkWindowEvent');
        this.$emit('adminCheckEvent');
        this.getActivityCategories();
        this.getClients();
        this.getAdminActivities();
        this.getBusinessGrowth();
        this.getSignInsPerWeek();
        this.getWorkoutsPerWeek();
        this.getExercisesPerWeek();
        this.getNutritionPerWeek();
        this.getTagsBasedClients();
    },
    methods: {
        checkApiCalls() { //for checking loader
            if (this.apiCalled < 7) {
                this.pageLoading = true;
                this.apiCalled = this.apiCalled + 1;
            }
            else {
                this.pageLoading = false;
                this.componentMounted = false
            }
        },
        ClientPopup(m) {
            this.clientId = m;
            this.showClientPopup = !this.showClientPopup;
        },
        showactivityPopup(m) {
            if (m !== null) {
                if (m.activity_target !== null && (m.activity_type == 'photos' || m.activity_type == 'workout' || m.activity_type == 'meal' || m.activity_type == 'habit')) {
                    this.activity_id = m.id;
                    this.activity_type = m.activity_type;
                    this.activityShow = !this.activityShow;
                }
            }
            else
                this.activityShow = false;
        },
        showautoMessagePopup() {
            this.autoMessagePopup = !this.autoMessagePopup;
        },
        getTagsBasedClients() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'auto-tagged-clients', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.tagsBasedClients = res.data.data;
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        getActivityCategories() {
            const token = config.storage.getItem('fwd_session_token');
            this.pageLoading = true;
            axios.post(config.baseApiUrl + 'activity-categories', {}, {
                headers: {
                    Authorization: 'Bearer ' + token
                }
            }).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                this.categories = res.data.data;
            }).catch(er => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        getClients() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'active-clients-list', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status)
                    this.users = res.data.data;
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        getAdminActivities() {
            axios.get(config.baseApiUrl + 'admin-activities', this.apiConfig).then(res => {
                if (res.data.status)
                    this.adminActivities = res.data.data;
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        getBusinessGrowth() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'business-growth', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.businessGrowthData = res.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    if (this.componentMounted) {
                        this.checkApiCalls();
                    }
                    if (!this.componentMounted) {
                        this.pageLoading = false;
                    }
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        getSignInsPerWeek() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'sign-ins-per-week', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.signInsPerWeek = res.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    if (this.componentMounted) {
                        this.checkApiCalls();
                    }
                    if (!this.componentMounted) {
                        this.pageLoading = false;
                    }
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        getWorkoutsPerWeek() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'workouts-per-week', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.workoutsPerWeek = res.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    if (this.componentMounted) {
                        this.checkApiCalls();
                    }
                    if (!this.componentMounted) {
                        this.pageLoading = false;
                    }
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        getExercisesPerWeek() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'exercises-per-week', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.exercisesPerWeek = res.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    if (this.componentMounted) {
                        this.checkApiCalls();
                    }
                    if (!this.componentMounted) {
                        this.pageLoading = false;
                    }
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        getNutritionPerWeek() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'nutrition-per-week', this.apiConfig).then(res => {
                if (this.componentMounted) {
                    this.checkApiCalls();
                }
                if (!this.componentMounted) {
                    this.pageLoading = false;
                }
                if (res.data.status) {
                    this.nutritionPerWeek = res.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    if (this.componentMounted) {
                        this.checkApiCalls();
                    }
                    if (!this.componentMounted) {
                        this.pageLoading = false;
                    }
                    this.modalTitle = 'Error!';
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
<style scoped>
.item {
    border: 1px solid rgb(201, 201, 201);
    border-radius: 1rem;
}

.w-30 {
    width: 30%;
}

select {
    font-size: 10px;
    font-weight: lighter;
}

select option {
    font-size: 10px;
    font-weight: lighter;
    background-color: white !important;
}

.pointer {
    cursor: pointer;
}
</style>
