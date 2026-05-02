<template lang="">
    <div class="my-popup-component" @click.self="showConfirmModal">
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <div class="addhabitcard main-box w-80 p-2 p-md-4 position-relative">
            <div class="close-btn"><button @click="showConfirmModal" class="btn-close" aria-label="Close"></button></div>
            <h2 class="addhabith2">Add a new habit folder</h2>
            <div class="inpaddhabit">
                <input type="text" @input="this.nameError= false" placeholder="Add a name for your folder" class="addhabitinp" v-model="fldrData.name">
                <span v-if="nameError" class="text-danger">*Please type name of your folder</span>
            </div>
            <button type="button" class="btn2" @click="addFldr">Add</button>
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
    components:{Inform,Confirm,Loader},
name:'addHabitFldr',
data(){
    return{
        apiConfig: {
            headers: {
                    Authorization: 'Bearer '+config.storage.getItem('fwd_session_token')
            }
        },
        fldrData:{
            name:null
        },
        nameError:false,
        pageLoading:false,
        informModal:false,
        confirmModal:false,
        modalTitle: '',
        modalDetail: '',
        loaderText:'',
    }
},
methods:{
    showConfirmModal(){
        this.modalDetail = 'This Cannot be Undone.'
        this.modalTitle = 'Do you want to quit?'
        this.confirmModal = true;
    },
    confirmationResponse(res){
        this.confirmModal = false;
        if(res==0)
        return;
        this.quitComponent();
    },
    quitComponent(){
        this.$parent.addfldr();
    },
    addFldr(){
        if(this.fldrData.name == null || this.fldrData.name==''){
                this.nameError = true;
                return;
        }
        this.pageLoading=true;
        this.loaderText='Uploading';
        axios.post(config.baseApiUrl+'create-habit-folder',this.fldrData,this.apiConfig)
        .then(res => {
            this.pageLoading=false;
            if(res.data.status){
                this.quitComponent();
                this.$parent.getAllHabitFldrs();
            } else {
                this.modalTitle = 'Error!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: Creating habit folder ",res.data.message);
            }
        }).catch(er => {
            this.pageLoading=false;
            this.modalTitle = 'Failed!';
            this.modalDetail = "Something went wrong";
            this.informModal = true;
            console.log("Error: fetching habit folders ",res.data.message);
        });
    },
    acknowledged(){
        this.informModal = false;
    },
},
}
</script>
<style scoped>
.addhabitcard{
    height: 300px;
    width: 45vw;
    padding-top: 20px;
    background-color: #FFFFFF;
    border-radius: 35px;
    opacity: 1;
    text-align: center;
  }
  .close-btn{
    width: 100%;
    height: 25px;
  }
  .btn-close{
    float: right;
    margin-right: 20px;
    opacity: 1;
  }
  .addhabith2{
    width: 100%;
    font-weight: 400;
    margin-top: 20px;
  }
  .inpaddhabit{
    width: 80%;
    height: 100px;
    margin-left: 10%;
    padding-top: 25px;
    text-align: center;
    background-color: #F7F7F7;
    border-radius: 15px;
  }
  .addhabitinp{
    width: 90%;
    padding-left: 3%;
    color: #B1B0B0;
    border: 1px solid #C5C5C5;
    border-radius: 10px;
    height: 50px;
    padding: auto;
  }
  .btn2{
    background-color:  #F2A18C;
    border: none;
    width: 140px;
    height: 30px;
    font-size: 13px;
    font-weight: 100;
    padding: 0;
    margin: 15px 0px 0px 0px;
    border-radius: 7px;
  }
</style>
