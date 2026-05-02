<template lang="">
    <div class="my-popup-component" @click.self="showConfirmModal">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <div class="addhabitcard2 position-relative">
                <button @click="showConfirmModal" class="btn-close" aria-label="Close"></button>
            <div class="card">
                <h4 class="addhabith4">Add a New Master Habit</h4>
                <p class="paddfldr2">Habit Name</p>
                <div class="inpaddhabit2">
                    <input type="text" @input="this.titleError=false" placeholder="Habit Name" class="inphbtname" v-model="habitData.title" >
                    <span v-if="titleError" class="text-danger">*Please type name of your Habit</span>
                </div>
                <div class="inpaddhabit3">
                    <h5 class="addhabith5">Lesson Content</h5>
                    <div class="addhabitinp3">
                        <textarea class="addhabitinp4" @input="this.contentError=false" v-model="habitData.content" ></textarea>
                        <span v-if="contentError" class="text-danger">*Please type content of your Habit</span>
                    </div>
                </div>
                <div class="div-btn3">
                    <button type="button" class="btn3" @click="addHabit" >Add</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
export default {
    components:{Loader,Confirm,Inform},
name:'createHabit',
data(){
    return{
        apiConfig: {
            headers: {
                    Authorization: 'Bearer '+config.storage.getItem('fwd_session_token')
            }
        },
        habitData:{
            folder_id:this.fldrid,
            title:null,
            content:null
        },
        titleError:false,
        contentError:false,
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
        this.modalDetail = 'All of your progrees will be lost';
        this.modalTitle = 'Do you want to quit?';
        this.confirmModal = true;
    },
    confirmationResponse(res){
        this.confirmModal = false;
        if(res==0)
        return;
        this.quitComponent();
    },
    quitComponent(){
        this.$parent.addhabit();
    },
    addHabit(){
        if(this.habitData.title == null || this.habitData.title ==''){
                this.titleError = true;
                return;
        }
        if(this.habitData.content == null || this.habitData.content ==''){
                this.contentError = true;
                return;
        }
        this.pageLoading=true;
        this.loaderText='Uploading';
        axios.post(config.baseApiUrl+'create-habit',this.habitData,this.apiConfig)
        .then(res => {
            this.pageLoading=false;
            if(res.data.status){
                this.$parent.getAllHabits();
                this.quitComponent();
            } else {
                this.modalTitle = 'Error!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: posting habit ",res.data.message);
            }
        }).catch(er => {
            this.pageLoading=false;
            this.modalTitle = 'Failed!';
            this.modalDetail = "Something went wrong";
            this.informModal = true;
            console.log("Error: posting habit ",er.error);
        });
    }
},
props:[
    'fldrid'
]
}
</script>
<style scoped>
  .btn-close{
    float: right;
    position: absolute;
    top: 15px;
    right: 25px;
    opacity: 1;
  }
  .card{
    width: 100%;
    height: 97%;
    margin-top: 20px;
    text-align: center;
    border: none;
    border-radius: 35px;
    overflow-y: auto;
  }
.addhabitcard2{
    height: 90%;
    width: 45vw;
    padding-top: 0px;
    padding-bottom: 0px;
    background-color: #FFFFFF;
    border-radius: 35px;
    opacity: 1;
    text-align: center;
    padding: 30px;
  }
  .div-btn3{
    width: 100%;
    height: 40px;
  }
  .btn3{
    background-color:  #F2A18C;
    border: none;
    width: 183px;
    height: 39px;
    font-size: 16px;
    font-weight: 100;
    padding: 0;
    margin-top: 5px;
    margin-right: auto;
    margin-left: auto;
    border-radius: 8px;
  }
  .addhabith4{
    width: 100%;
    font-size: 30px;
    height: 44px;
    margin-top: 5px;
    margin-bottom: 5px;
  }
  .paddfldr2{
    color: #B1B0B0;
    font-size: 16px;
    margin: 0;
  }
  .addhabith5{
    font-size: 24px;
    margin-bottom: 5px;
  }
  .inpaddhabit2{
    width: 80%;
    height: 95px;
    margin-left: 10%;
    text-align: center;
    background-color: #F7F7F7;
    border-radius: 15px;
  }
  .inphbtname{
    width: 92%;
    padding-left: 3%;
    color: #C5C5C5;
    border: 1px solid #C5C5C5;
    background-color: #FFFFFF;
    border-radius: 10px;
    text-align: start;
    font-size: 17px;
    height: 40px;
    margin-top: 23px;
  }
  .inphbtname::placeholder{
    color: #C5C5C5;
    font-size: 17px;
  }
  .inpaddhabit3{
    width: 80%;
    height: 280px;
    margin-left: 10%;
    margin-top: 20px;
    padding-top: 10px;
    padding-bottom: 20px;
    text-align: center;
    background-color: #F7F7F7;
    border-radius: 15px;
  }
  .addhabitinp3{
    width: 97%;
    margin-left: 1.5%;
    color: #B1B0B0;
    background-color: #FFFFFF;
    border: 1px solid #C5C5C5;
    border-radius: 10px;
    height: 200px;
    padding: auto;
  }
  .addhabitinp4{
    margin-top: 0px;
    border-radius: 10px;
    resize: none;
    text-align: start;
    width: 100%;
    border: none;
    outline: none;
    height: 99%;
    padding: 5px;
  }
</style>
