<template lang="">
    <div class="my-popup-component" @click.self="done(0)"  style="z-index:9999999 !important">
        <div class="brds-5 bg-white p-5 position-relative col-11 col-md-6 col-xl-4">
            <button class="trans_btn position-absolute" @click="done(0)"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="text-center">
                <h2><strong>{{msgTitle}}</strong></h2>
                <!-- <p class="mb-0">{{msgDetail}}</p> -->
            </div>
            <div class="mt-3 gray_bg brds-2 p-2 text-center">
                <input type="text" class="theme-input w-100 brds-2" v-model="inputData" :placeholder="msgDetail">
                <p class="mb-0 text-danger" v-show="emptyError">* Please fill this field.</p>
            </div>
            <div class="mt-3 text-center">
                <button @click="done(1)" class="prim_btn mx-4 px-4 py-1 brds-2">Done</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props : ['msgTitle','msgDetail','renameValue'],
    data() {
        return {
            inputData: this.renameValue,
            emptyError: false
        }
    },
    methods: {
        done(value){
            if(value==1){
                if(this.inputData==null || this.inputData==''){
                    this.emptyError = true;
                    return;
                } else {
                    this.$parent.inputResponse(this.inputData);
                    return;
                }
            }
            this.$parent.inputResponse(null);
            return;
        }
    },
}
</script>
<style lang="">

</style>
