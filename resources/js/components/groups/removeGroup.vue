<template lang="">
    <div class="my-popup-component">
        <div class="brds-4 w-40 position-relative" style="height:45vh;overflow-y:auto;text-align:center;background-color:white;">
            <button  @click="quitComponent" class="trans_btn position-absolute"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0 mt-4" style="font-size:37px;">{{msgTitle}}</p>
            <p class="m-0 my-2 w-60 mx-auto" style="font-size:16px;color:#B1B0B0;">{{msgDetail}}</p>
            <div class="w-100 my-2">
                <button @click="ok" style="width:250px;height:53px;" class="prim_bg brds-3 border-0">{{btnValue1}}</button>
            </div>
            <div class="w-100 my-2">
                <button @click="quitComponent" style="width:250px;height:53px;border:1px solid #606060;background-color:white;" class="brds-3">{{btnValue2}}</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['msgTitle', 'msgDetail', 'btnValue1', 'btnValue2'],
    methods: {
        ok() {
            this.$parent.confirmationResponse(1);
        },
        quitComponent() {
            this.$parent.quitDelete();
        }
    }
}
</script>
<style lang="">

</style>
