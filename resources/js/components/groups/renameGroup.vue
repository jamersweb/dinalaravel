<template lang="">
    <div class="my-popup-component">
        <div class="brds-4 position-relative" style="height:250px;width:45%;background-color:white;text-align:center;">
            <button @click="quitComponent" class="trans_btn position-absolute"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0 mt-3" style="font-size:37px;">Rename</p>
            <div class="w-80 my-2 brds-1 mx-auto" style="height:75px;background-color:#F7F7F7;">
                <input v-model="changeName" @input="nameError = false" class="w-90 brds-1 mt-3 px-3 py-2" style="border:1px solid #C5C5C5" type="text" placeholder="Name of group like 'Beginner'">
            </div>
            <p class="mb-0 text-danger text-center" v-if="nameError">*please enter a valid name</p>
            <div class="w-100 mb-2">
                <button @click="rename" class="prim_bg w-20 px-4 py-2 brds-2 mt-3 border-0">Save</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['renameValue'],
    data() {
        return {
            changeName: '',
            nameError: false,
        }
    },
    mounted() {
        this.changeName = this.renameValue
    },
    methods: {
        rename() {
            this.changeName = this.changeName.trim();
            if(this.changeName == ""){
                this.nameError = true;
                return;
            }
            this.$parent.renameGroup(null, this.changeName, null);
        },
        quitComponent() {
            this.$parent.quitRename();
        }
    }
}
</script>
<style scoped>

</style>
