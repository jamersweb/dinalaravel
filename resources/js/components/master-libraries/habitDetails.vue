<template lang="">
    <div class="my-popup-component" @click.self="quithabitcomponent">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <div class="addhabitcard2 position-relative">
            <div v-if="habitDetails!=null" class="card">
                <h4 class="addhabith4">{{habitDetails.title}}</h4>
                <div class="inpaddhabit3">
                    <h5 class="addhabith5">Lesson Content</h5>
                    <div class="addhabitinp3">
                        <div class="addhabitinp4" >{{habitDetails.content}}</div>
                    </div>
                </div>
                <div class="div-btn3">
                    <button type="button" class="btn3" @click="quithabitcomponent" >Ok</button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import axios from 'axios';
    import config from '../../config';
    import Loader from '../../components/loader.vue';
export default {
    name:'habitDetails',
    components:{Loader},
    data(){
        return{
            apiConfig: {
                headers: {
                    Authorization: 'Bearer '+config.storage.getItem('fwd_session_token')
                }
            },
            habitDetails:null,
            pageLoading:false,
            loaderText:'',
        }
    },
    props:[
            'habitid',
    ],
    mounted(){
        this.getHabitDetails();
    },
    methods:{
        quithabitcomponent(){
            this.$parent.popuphabit();
        },
        getHabitDetails(){
            this.pageLoading=true;
            this.loaderText='Loading',
            axios.get(config.baseApiUrl+'get-habit-detail/'+this.habitid,this.apiConfig)
            .then(res => {
                this.pageLoading=false;
                if(res.data.status)
                this.habitDetails=res.data.data
                else{
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Something went wrong";
                    this.informModal = true;
                    console.log("Error: fetching habit ",res.data.message);
                }
            }).catch( err =>{
                this.pageLoading=false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: fetching habit ",err.error);
            });
        },
    }
}
</script>
<style scoped>
    .btn-close{
    float: right;
    margin-right: 20px;
    opacity: 1;
  }
  .card{
    width: 100%;
    height: 97%;
    text-align: center;
    border: none;
    border-radius: 35px;
    overflow-y: auto;
  }
.addhabitcard2{
    height: 70%;
    width: 45vw;
    padding-top: 0px;
    padding-bottom: 0px;
    background-color: #FFFFFF;
    border-radius: 35px;
    opacity: 1;
    text-align: center;
    padding: 20px;
  }
  .div-btn3{
    width: 100%;
    height: 40px;
    position: absolute;
    bottom: 0px;
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
    height: 180px;
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
    height: 100px;
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
