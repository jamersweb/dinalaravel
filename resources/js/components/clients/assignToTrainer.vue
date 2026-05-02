<template lang="">
    <div class="my-popup-component">
        <div class="main-box position-relative">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 style="font-size:37px;margin-top:20px;margin-bottom:0px;color:#343434;">Assign Client To Trainer</h5>
            <p style="font-size:16px;color:#B1B0B0;">Basic members cannot be assigned to a trainer</p>
            <div class="mx-auto brds-3 px-4" style="width:80%;height:90px;background-color:#F7F7F7;padding-top:20px;">
                <div class="brds-1 position-relative float-start" style="width:49%;height:50px;border:1px solid #C5C5C5;background-color:white;">
                    <select name="" id="" style="width:100%;height:50px;border:none;color:#B1B0B0;background-color:transparent;">
                        <option value="">Fitness With Dina </option>
                    </select>
                    <!-- <input type="text" class="brds-2 float-start" style="width:calc(100% - 20px);height:37px;border:none;">
                    <img src="/images/Downarrow.png" alt="search-icon" style="width:15px;height:10px;top:15px;right:5px;" class="img-fluid position-absolute"> -->
                </div>
                <div class="brds-1 position-relative float-end" style="width:49%;height:50px;border:1px solid #C5C5C5;background-color:white;">
                    <select name="" id="" style="width:100%;height:50px;border:none;color:#B1B0B0;background-color:transparent;">
                        <option value="">Dinas online........ </option>
                    </select>
                    <!-- <input type="text" class="brds-2 float-start" style="width:calc(100% - 20px);height:37px;border:none;">
                    <img src="/images/Downarrow.png" alt="search-icon" style="width:15px;height:10px;top:15px;right:5px;" class="img-fluid position-absolute"> -->
                </div>
            </div>
            <button class="mt-3 prim_bg brds-2" style="font-size:16px;width:180px;height:40px;border:none;">Assign</button>
        </div>
    </div>
</template>
<script>
export default {
    name:'assignToTrainer',
    methods:{
        quitComponent(){
            this.$parent.assignToTrainer();
        }
    }
}
</script>
<style scoped>
.main-box{
    width: 45%;
    height: 300px;
    border-radius: 15px;
    background-color: #FFFFFF;
    text-align: center;
}
</style>
