<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <div class="my-popup-component" @click.self="quitComponent">
        <div class="bg-white px-3 py-5 brds-5 position-relative col-11 col-md-8 col-lg-6">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="h-100 px-2 px-md-5">
                <p>Select Workouts to Import:</p>
                <div class="gray_bg p-3 brds-2">
                    <p class="mb-1">Selected: {{noOfWrks}}</p>
                    <div class="border brds-2 bg-white" style="height:250px; overflow-y:auto">
                        <div class="my-2 mx-3" v-for="(item,index) in workouts">
                            <input type="checkbox" class="form-check-input" :id="'workout'+index" :value="item.id" v-model="selectedWrks">
                            <label class="ps-3 w-80 pointer" :for="'workout'+index">
                                <span>{{item.title}}</span>
                                <span class="float-end">{{item.type}}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="prim_btn brds-2 py-2 px-4 fw-bold" @click="importSelected()">Import</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import Loader from '../../components/loader.vue';
export default {
    props: ['phase_id', 'language_type'],
    components: { Inform, Confirm, Loader },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            workouts: [],
            selectedWrks: [],
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
        }
    },
    mounted() {
        this.pageLoading = true;
        this.loaderText = 'Fetching'
        axios.get(config.baseApiUrl + 'all-workouts-list?lang=' + this.language_type, this.apiConfig).then(res => {
            this.pageLoading = false;
            if (res.data.status)
                this.workouts = res.data.data;
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                console.log(res.data.message);
                this.informModal = true;
            }
        }).catch(er => {
            this.pageLoading = false;
            this.modalTitle = 'Error!';
            this.modalDetail = 'Something Went Wrong';
            console.log(er.error);
            this.informModal = true;
        });
    },
    methods: {
        acknowledged() {
            this.informModal = false;
        },
        importSelected() {
            if (this.selectedWrks.length == 0) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No Workout Selected.';
                this.informModal = true;
                return;
            }
            const postData = {
                'program_phase_id': this.phase_id,
                'workout_ids': this.selectedWrks
            };
            this.pageLoading = true;
            this.loaderText = 'Uploading';
            axios.post(config.baseApiUrl + 'add-phase-workout', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    // this.$parent.closeAll();
                    this.$parent.closeImportWorkout(res.data.message);
                    this.$parent.fetchPhaseDetail(null);
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = 'Something Went Wrong';
                console.log("Error in importing workouts", er.message);
                this.informModal = true
            });
        },
        quitComponent() {
            this.$parent.closeImportWorkout();
        }
    },
    computed: {
        noOfWrks() {
            return this.selectedWrks.length;
        }
    }
}
</script>
<style lang="">

</style>
