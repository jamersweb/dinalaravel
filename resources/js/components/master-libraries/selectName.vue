<template>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <!-- <input class="form-check-input tsl" type="checkbox" value=""> -->
    <button type="button" class="btn1" @click="show1">New</button>
    <!-- <div class="dropdown">
        <button class="btn2 tsl dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Move
         </button>
         <ul class="dropdown-menu tsl">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
        </ul>
    </div> -->
    <button class="btn3 tsl " type="button" >Delete</button>
    <select class="inpname" id="">
        <option>Name</option>
    </select>
</template>

<script>
    import Inform from '../../components/inform.vue';
    export default{
        components:{Inform},
        name:'selectName',
        data(){
            return{
                informModal:false,
                confirmModal:false,
                modalTitle: '',
                modalDetail: '',
            }
        },
        methods:{
            show1(){
                if(this.showContent== true){
                    this.$parent.addhabit()
                }
                else{
                    this.modalTitle = 'Error!';
                    this.modalDetail = "Please select a habit folder first";
                    this.informModal = true;
                }
            },
            acknowledged(){
            this.informModal = false;
        },
        },
        props:[
            'showContent'
        ]
    }
</script>
<style scoped>
    .form-check-input{
        height: 25px;
        width: 25px;
        padding: 0;
        margin: 10px 0px 0px 8px;
    }
    .inpname{
    width: 35%;
    background-color: inherit;
    font-size: 12px;
    color: #343434;
    border-color: #7a7979;
    border-radius: 10px;
    float: right;
    margin: 10px 8px 0px 0px;
    height: 25px;
  }
  .dropdown-menu{
    border: none;
  }
    .downarrow::after{
        content: '\2304';
        margin-left: 0px;
        height: 15px;
        width: 25px;
        margin-top: -5px;
        font-size: 15px;
    }
    .btn1{
      background-color:  #F2A18C;
      border: none;
      width: 120px;
      height: 25px;
      font-size: 13px;
      font-weight: 100;
      float: left;
      padding: 0;
      margin: 10px 0px 0px 8px;
      border-radius: 3px;
    }
    .btn1:hover{
        background-color: black;
        color: #F2A18C;
    }
    .btn2{
      background-color: #FFFFFF;
      border: none;
      width: 80px;
      height: 25px;
      font-size: 13px;
      font-weight: 100;
      float: left;
      padding-right: 10px;
      margin: 10px 0px 0px 8px;
      border-radius: 3px;
    }
    .btn3{
      background-color: #FFFFFF;
      border: none;
      width: 70px;
      font-size: 13px;
      height: 25px;
      float: left;
      padding: 0px 5px 0px 0px;
      margin: 10px 0px 0px 8px;
      border-radius: 3px;
    }
    .form-check-input{
        float: left;
    }
    .dropdown{
        float: left;
    }
    .three-dots:after {
        cursor: pointer;
        content: '\2807';
        font-size: 18px;
        padding: 0px 0px 0px 0px;
    }
  @media screen and (max-width: 1090px) {
    .form-check-input{
        width: 20px;
    }
    .btn1{
        width: 60px;
    }
    .btn2{
        width: 65px;
    }
    .btn3{
        width: 20px;
    }
    .inpname{
        width: 30%;
    }
  }
</style>
