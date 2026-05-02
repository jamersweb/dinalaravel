<template lang="">
    <div class="my-popup-component" style="z-index:9999999 !important">
        <div class="brds-5 bg-white p-5 position-relative col-11 col-md-6 col-xl-4">
            <button class="trans_btn position-absolute" @click="respond(0)"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="text-center">
                <h1>{{msgTitle}}</h1>
                <p class="mb-0">{{msgDetail}}</p>
            </div>
            <div class="mt-3 text-center">
                <button @click="respond(1)" class="prim_btn mx-4 py-1 brds-2 px-4">Confirm</button>
                <button @click="respond(0)" class="scnd_btn mx-4 py-1 brds-2 px-4">Cancel</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props : ['msgTitle','msgDetail'],
    methods: {
        respond(value){
            this.$parent.confirmationResponse(value);
        }
    },
}
</script>
<style lang="">
    
</style>