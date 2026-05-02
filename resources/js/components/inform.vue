<template lang="">
    <div class="my-popup-component" style="z-index:9999999 !important">
        <div class="brds-5 bg-white p-5 position-relative col-11 col-md-6 col-xl-4">
            <button class="trans_btn position-absolute" @click="respond()"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="text-center">
                <h1>{{msgTitle}}</h1>
                <p class="mb-0">{{msgDetail}}</p>
            </div>
            <div class="mt-3 text-center">
                <button @click="respond()" class="prim_btn mx-4 py-2 px-4 brds-2">OK</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['msgTitle', 'msgDetail'],
    methods: {
        respond() {
            let p = this.$parent;
            while (p) {
                if (typeof p.acknowledged === 'function') {
                    p.acknowledged();
                    return;
                }
                if (p.informModal !== undefined) {
                    p.informModal = false;
                    return;
                }
                p = p.$parent;
            }
        }
    },
}
</script>
<style lang="">

</style>

