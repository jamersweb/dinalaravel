<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div @click.self="quitComponent" class="my-popup-component">
        <div class="main-box position-relative">
            <div @click.self="showDaySelect=false" v-if="showDaySelect" style="height:100vh;width:100vw;background-color:transparent;position:absolute;top:0;left:0;z-index:9999"></div>
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 style="font-size:37px;margin-top:20px;margin-bottom:0px;color:#343434;">Add Habit</h5>
            <p style="font-size:16px;color:#B1B0B0;">Habit to add</p>
            <div class="mx-auto brds-3 p-3" style="width:90%;min-height:410px;background-color:#EEEEEE;padding-top:0px;">
                <div class="brds-1 position-relative float-start" style="width:49%;height:375px;overflow-y:auto;">
                    <div class="dropdown position-relative">
                        <div @click="showSelect=!showSelect" style="max-width:270px;width:100%;height:49px;position:relative;margin-left:3px;cursor:pointer;">
                            <p style="width:100%;height:100%;border:1px solid #C5C5C5;font-size:12px;border-radius:5px;background-color:white;text-align:start;padding-top:15px;padding-left:15px;">{{habitBtnValue}}</p>
                            <img src="/cms-assets/images/Clients/downarrow.png" alt="" style="position:absolute;top:46%;height:6px;right:10px;">
                        </div>
                        <div v-if="showSelect" class="tsl py-2" style="max-width:270px;width:100%;min-height:150px;border-radius:10px;border:none;background-color:white;position:absolute;top:44px;left:3px;z-index:999;">
                            <div v-for="(item, index1) in habitDetails" :key="index1" style="width:100%;height:30px;position:relative;padding:6px;cursor:pointer;">
                                <p v-if="item.habits.length>0" @click="showSubSelect[index1]=!showSubSelect[index1]" class="dropdownp" style="font-size:12px;">{{item.name}}</p>
                                <p v-else class="dropdownp" style="font-size:12px;">{{item.name}}</p>
                                <img v-if="item.habits.length>0" src="/cms-assets/images/Clients/rightarrow.png" alt="" style="position:absolute;top:10px;height:10px;right:15px;">
                                <div v-if="showSubSelect[index1]" class="tsl py-2" style="width:80%;position:absolute;min-height:50px;background-color:white;border-radius:5px;top:23px;left:12%;z-index:9999;">
                                    <p @click="addHabit(index1,index),habitBtnValue=item.name" v-for="(items, index) in item.habits" :key="index" class="dropdownp" style="font-size:10px;width:100%;margin:0px;height:22px;padding-top:5px;">{{items.title}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="condition">
                        <p style="font-size:12px;color:#343434;margin:8px 0px 8px 0px;text-align:start;">Name of the habit</p>
                        <input class="custom-input brds-1 ps-3" type="text" placeholder="Like:'Eat Omega 3 Oils'">
                    </div>
                    <!-- <div class="position-relative">
                        <div style="width:100%;min-height:40px">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Number of meals</p>
                            <div class="dropdown position-relative float-start ms-2 mt-2 p-0">
                                <button class="btn btn-secondary dropdown-toggle3 brds-1 pt-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:10px;color:#B1B0B0;background-color:white;height:25px;border:1px solid #B1B0B0;width:45px;text-align:start;">
                                    All
                                </button>
                                <ul class="dropdown-menu" style="width:auto">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 5px;text-align:start;float:left;"> meal</p>
                        </div>
                        <div class="mx-auto" style="width:100%;height:2px;background-color:#3434345e;position:absolute;top:40px;"></div>
                        <div style="width:100%;min-height:40px">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Show Hand/Fist Portion Guide</p>
                            <label class="switch" style="float:left;top:14px;left:10px;">
                                <input type="checkbox">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <div style="width:160px;min-height:40px;float:left;">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Protein</p>
                            <div class="dropdown position-relative float-start ms-2 mt-2 p-0">
                                <button class="btn btn-secondary dropdown-toggle3 brds-1 pt-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:10px;color:#B1B0B0;background-color:white;height:25px;border:1px solid #B1B0B0;width:45px;text-align:start;">
                                    1
                                </button>
                                <ul class="dropdown-menu" style="width:auto">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                            <p style="font-size:12px;color:#343434;margin:13px 0px 0px 5px;text-align:start;float:left;">Palms</p>
                        </div>
                        <div style="width:160px;min-height:40px;float:left;">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Protein</p>
                            <div class="dropdown position-relative float-start ms-2 mt-2 p-0">
                                <button class="btn btn-secondary dropdown-toggle3 brds-1 pt-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:10px;color:#B1B0B0;background-color:white;height:25px;border:1px solid #B1B0B0;width:45px;text-align:start;">
                                    1
                                </button>
                                <ul class="dropdown-menu" style="width:auto">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                            <p style="font-size:12px;color:#343434;margin:13px 0px 0px 5px;text-align:start;float:left;">Palms</p>
                        </div>
                        <div style="width:160px;min-height:40px;float:left;">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Protein</p>
                            <div class="dropdown position-relative float-start ms-2 mt-2 p-0">
                                <button class="btn btn-secondary dropdown-toggle3 brds-1 pt-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:10px;color:#B1B0B0;background-color:white;height:25px;border:1px solid #B1B0B0;width:45px;text-align:start;">
                                    1
                                </button>
                                <ul class="dropdown-menu" style="width:auto">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                            <p style="font-size:12px;color:#343434;margin:13px 0px 0px 5px;text-align:start;float:left;">Palms</p>
                        </div>
                        <div style="width:160px;min-height:40px;float:left;">
                            <p style="font-size:12px;color:#343434;margin:13px 0px 8px 0px;text-align:start;float:left;">Protein</p>
                            <div class="dropdown position-relative float-start ms-2 mt-2 p-0">
                                <button class="btn btn-secondary dropdown-toggle3 brds-1 pt-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size:10px;color:#B1B0B0;background-color:white;height:25px;border:1px solid #B1B0B0;width:45px;text-align:start;">
                                    1
                                </button>
                                <ul class="dropdown-menu" style="width:auto">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                </ul>
                            </div>
                            <p style="font-size:12px;color:#343434;margin:13px 0px 0px 5px;text-align:start;float:left;">Palms</p>
                        </div>
                    </div> -->
                    <div v-if="selectedHabit!=null" class="float-start">
                        <p style="font-size:12px;color:#343434;margin:8px 0px 8px 0px;text-align:start;">When to practice habit</p>
                        <div style="height: 35px;width: 49%;font-size: 10px;float:left;border: 1px solid #B1B0B0;color: #B1B0B0;background-color:white;border-radius:5px;">
                            <Datepicker v-model="dateEntered" :startDate="minDate" :minDate="minDate" :format="format" autoApply ignoreTimeValidation :enableTimePicker="false" />
                        </div>
                        <div class="dropdown position-relative float-end" style="width:50%;height:35px;">
                            <select v-model="selectedWeek" style="font-size:9px;color:#C5C5C5;height:100%;width:100%;border:1px solid #B1B0B0;border-radius:5px;">
                                <option selected disabled value="0">Select Week</option>
                                <option value="1">1 week</option>
                                <option value="2">2 week</option>
                                <option value="3">3 week</option>
                                <option value="4">4 week</option>
                                <option value="5">5 week</option>
                                <option value="6">6 week</option>
                                <option value="7">7 week</option>
                                <option value="8">8 week</option>
                                <option value="9">9 week</option>
                                <option value="10">10 week</option>
                            </select>
                        </div>
                        <p style="font-size:12px;color:#343434;margin:45px 0px 8px 0px;text-align:start;">Days to practice</p>
                        <div class="dropdown position-relative float-start" style="width:50%;height:35px;">
                            <div @click="showDaySelect=!showDaySelect" style="width:100%;height:100%;border:1px solid #B1B0B0;border-radius:5px;position:relative;background-color:white;cursor:pointer;">
                                <p style="font-size:9px;color:#C5C5C5;margin-bottom:0px;width:100%;height:100%;text-align:start;padding-top:10px;padding-left:5px;">Select Days</p>
                                <img src="/cms-assets/images/Clients/downarrow.png" alt="" style="position:absolute;top:40%;height:6px;right:10px;">
                            </div>
                            <div v-if="showDaySelect" style="position:absolute;width:100%;background-color:white;border: 1px solid #C5C5C5;border-radius:5px;top:30px;z-index:999999">
                                <p @click="addDay(0)" :class="{activeday: daysCheck[0]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Monday</p>
                                <p @click="addDay(1)" :class="{activeday: daysCheck[1]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Tuesday</p>
                                <p @click="addDay(2)" :class="{activeday: daysCheck[2]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Wednesday</p>
                                <p @click="addDay(3)" :class="{activeday: daysCheck[3]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Thursday</p>
                                <p @click="addDay(4)" :class="{activeday: daysCheck[4]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Friday</p>
                                <p @click="addDay(5)" :class="{activeday: daysCheck[5]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Saturday</p>
                                <p @click="addDay(6)" :class="{activeday: daysCheck[6]}" style="margin:0px;font-size:10px;width:100%;color:#C5C5C5;cursor:pointer;" >Sunday</p>
                            </div>
                        </div>
                        <button @click="addHabitToUsers()" class="prim_bg mt-4 brds-2 float-start" style="height:40px;width:180px;font-size:16px;border:none;position:absolute;bottom:0px;left:25%;">Add</button>
                    </div>
                </div>
                <div class="brds-1 position-relative float-end" style="width:49%;height:375px;border:1px solid #C5C5C5;">
                    <p v-if="selectedHabit==null" style="font-size:16px;color:#B1B0B0;width:100%;text-align:center;margin-top:50%;">No habit selected</p>
                    <h5 v-if="selectedHabit!=null" style="font-size:37px;margin-top:5px;margin-bottom:10px;color:#343434;">{{selectedHabit.title}}</h5>
                    <p class="mx-auto" v-if="selectedHabit!=null" style="overflow-y:auto;width:95%;height:250px;background-color:white;border:1px solid #C5C5C5;border-radius:5px;text-align:start;font-size:15px;padding:10px;">{{selectedHabit.content}}</p>
                    <div v-if="condition">
                        <img src="/images/Group55904.png" alt="Error" style="height:50px;width:45px;position:absolute;top:10px;left:15px;">
                        <p style="font-size:20px;position:absolute;top:20px;left:60px;">Custom Habit</p>
                        <div class="mx-auto line"></div>
                    </div>
                    <div v-if="condition" class="w-90 mx-auto">
                        <p style="font-size:20px;">Eat Protein</p>
                        <img style="height:60px;width:60px;" src="/images/Group55904.png" alt="Error">
                        <p style="font-size:12px;color:#343434">1 palm</p>
                        <p style="font-size:16px;color:#343434">This habit focuses on having clients consume protein with each of their meals</p>
                    </div>
                    <div v-if="condition" class="w-90 mx-auto">
                        <p style="font-size:20px;">Following portion guides</p>
                        <div class="w-50 float-start">
                            <p style="font-size:12px;color:#343434;margin:0px;">Protein</p>
                            <img style="height:60px;width:60px;" src="/images/Group55904.png" alt="Error">
                            <p style="font-size:12px;color:#343434;margin:0px;">1 palm</p>
                        </div>
                        <div class="w-50 float-start">
                            <p style="font-size:12px;color:#343434;margin:0px;">Protein</p>
                            <img style="height:60px;width:60px;" src="/images/Group55904.png" alt="Error">
                            <p style="font-size:12px;color:#343434;margin:0px;">1 palm</p>
                        </div>
                        <div class="w-50 float-start">
                            <p style="font-size:12px;color:#343434;margin:0px;">Protein</p>
                            <img style="height:60px;width:60px;" src="/images/Group55904.png" alt="Error">
                            <p style="font-size:12px;color:#343434;margin:0px;">1 palm</p>
                        </div>
                        <div class="w-50 float-start">
                            <p style="font-size:12px;color:#343434;margin:0px;">Protein</p>
                            <img style="height:60px;width:60px;" src="/images/Group55904.png" alt="Error">
                            <p style="font-size:12px;color:#343434;margin:0px;">1 palm</p>
                        </div>
                        <p style="font-size:16px;color:#343434">This habit focuses on having clients practice following portion guides for their meals</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import config from '../../config';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
export default {
    props: ['selectedUsers'],
    components: { Loader, Inform, Datepicker },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            habitBtnValue: 'Select a habit',
            showSelect: false,
            showSubSelect: [],
            showDaySelect: false,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            habitDetails: null,
            selectedHabit: null,
            selectedDays: [],
            dateEntered: null,
            minDate: null,
            selectedWeek: 0,
            daysCheck: [false, false, false, false, false, false, false],
        }
    },
    mounted() {
        let today = new Date();
        today.setDate(today.getDate());
        this.minDate = today
        this.pageLoading = true;
        this.loaderText = 'Fetching';
        axios.get(config.baseApiUrl + 'all-folders-with-habits', this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status) {
                this.habitDetails = res.data.data;
                for (let index = 0; index < this.habitDetails.length; index++) {
                    this.showSubSelect[index] = false;
                }
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }
        }).catch(er => {
            this.modalTitle = 'Error!';
            this.modalDetail = er;
            this.informModal = true;
        })
    },
    methods: {
        addHabit(m, n) {
            this.selectedHabit = this.habitDetails[m].habits[n];
            this.showSelect = false;
            this.showSubSelect[m] = false;
        },
        addHabitToUsers() {
            if (this.selectedDays.length > 0 && this.selectedHabit != null && this.dateEntered != null && this.dateEntered != '' && this.selectedWeek != 0) {
                let postData = {
                    habit_id: this.selectedHabit.id,
                    user_ids: this.selectedUsers,
                    weeks: this.selectedWeek,
                    week_days: this.selectedDays,
                    start_date: this.dateEntered,
                }
                this.pageLoading = true;
                this.loaderText = 'Uploading';
                axios.post(config.baseApiUrl + 'add-habit-to-users', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$parent.CloseaddHabitPopup(1);
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
                        this.modalDetail = er;
                        this.informModal = true;
                    })

            }
            else {
                this.modalTitle = 'Error';
                this.modalDetail = 'All fields are required please fill them all';
                this.informModal = true;
            }
        },
        addDay(m) {
            if (m == 0) {
                this.daysCheck[0] = !this.daysCheck[0];
                let index = this.selectedDays.indexOf("Monday");
                if (index == -1) {
                    this.selectedDays.push("Monday");
                    this.daysCheck[0] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[0] = false;
                }
            }
            if (m == 1) {
                this.daysCheck[1] = !this.daysCheck[1];
                let index = this.selectedDays.indexOf("Tuesday");
                if (index == -1) {
                    this.selectedDays.push("Tuesday");
                    this.daysCheck[1] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[1] = false;
                }
            }
            if (m == 2) {
                this.daysCheck[2] = !this.daysCheck[2];
                let index = this.selectedDays.indexOf("Wednesday");
                if (index == -1) {
                    this.selectedDays.push("Wednesday");
                    this.daysCheck[2] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[2] = false;
                }
            }
            if (m == 3) {
                this.daysCheck[3] = !this.daysCheck[3];
                let index = this.selectedDays.indexOf("Thursday");
                if (index == -1) {
                    this.selectedDays.push("Thursday");
                    this.daysCheck[3] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[3] = false;
                }
            }
            if (m == 4) {
                this.daysCheck[4] = !this.daysCheck[4];
                let index = this.selectedDays.indexOf("Friday");
                if (index == -1) {
                    this.selectedDays.push("Friday");
                    this.daysCheck[4] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[4] = false;
                }
            }
            if (m == 5) {
                this.daysCheck[5] = !this.daysCheck[5];
                let index = this.selectedDays.indexOf("Saturday");
                if (index == -1) {
                    this.selectedDays.push("Saturday");
                    this.daysCheck[5] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[5] = false;
                }
            }
            if (m == 6) {
                this.daysCheck[6] = !this.daysCheck[6];
                let index = this.selectedDays.indexOf("Sunday");
                if (index == -1) {
                    this.selectedDays.push("Sunday");
                    this.daysCheck[6] = true;
                }
                else {
                    this.selectedDays.splice(index, 1);
                    this.daysCheck[6] = false;
                }
            }
        },
        quitComponent() {
            this.$parent.CloseaddHabitPopup();
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style scoped>
.main-box {
    width: 60%;
    height: 90%;
    border-radius: 15px;
    background-color: #FFFFFF;
    text-align: center;
    overflow-y: auto;
    padding-bottom: 30px;
}

.dropdown-toggle1::after {
    position: absolute;
    right: 10px;
    top: 15px;
    font-weight: bold;
    font-size: 15px;
    content: '\02C5';
}

.custom-input {
    height: 50px;
    width: 100%;
    font-size: 13px;
    border: 1px solid #B1B0B0;
}

.custom-input::placeholder {
    font-size: 13px;
    color: #B1B0B0;
}

.custom-input2 {
    height: 30px;
    width: 49%;
    font-size: 10px;
    padding-left: 10px;
    border: 1px solid #B1B0B0;
    color: #B1B0B0;
}

.custom-input::placeholder {
    font-size: 12px;
    color: #B1B0B0;
}

.dropdown-toggle2::after {
    position: absolute;
    right: 10px;
    top: 5px;
    font-weight: bold;
    content: "\02C5";
    font-size: 15px;
}

.line {
    width: 98%;
    height: 2px;
    background-color: #3434345e;
    margin-top: 65px;
}

.dropdownp:hover {
    background-color: #DFE0E2 !important;
}

.dropdown-toggle3::after {
    position: absolute;
    right: 5px;
    top: 3px;
    content: "\02C5";
    font-size: 13px;
    font-weight: bold;
}

.activeday {
    background-color: #F2A18C !important;
    color: black !important;
}
</style>
