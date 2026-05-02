<template lang="">
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <div class="my-popup-component">
        <div class="brds-4 position-relative pb-4" style="min-height:330px;width:50%;background-color:white;text-align:center;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0 mt-3" style="font-size:37px;">Add New Group</p>
            <p class="m-0 my-1" style="font-size:16px;color:#B1B0B0;">Basic members cannot be added to groups</p>
            <div class="mx-auto my-2 w-40 d-flex flex-wrap justify-content-center" style="height:50px;position:relative;">
                <button class="float-start mt-2 prim_bg border-0 px-4 py-1 brds-2">
                    Upload Image
                    <input @change="getImage" ref="selectedImage" type="file" style="width: 53%;position: absolute;opacity: 0;top: 7px;left: 39px;height: 43px;" accept="image/*">
                </button>
                <img class="float-start ms-3" style="height:50px;width:50px;border-radius:30px;" v-if="imageURL!==null" :src="imageURL" alt="">
            </div>
            <div class="w-80 brds-1 mx-auto" style="height:80px;background-color:#F7F7F7;">
                <input v-model="groupName" @input="nameError=false" class="w-90 brds-1 px-3" style="height:50px;border:1px solid #C5C5C5;margin-top:15px;" type="text" placeholder="Name of group like 'Beginner'">
            </div>
            <p v-show="nameError" class="text-danger my-2">*please enter a valid name.</p>
            <p class="m-0 my-2" style="font-size:16px;color:#B1B0B0;">Who can post?</p>
            <select v-model="access" class="brds-1 px-3" style="width:230px;height:35px;border:1px solid #C5C5C5;color:#C5C5C5;" name="" id="">
                <option value="members">Anyone</option>
                <option value="admins">Admin</option>
            </select>
            <div class="w-100">
                <button class="prim_bg px-4 py-1 brds-2 mt-3 border-0" @click="createGroup()">Add New</button>
            </div>
        </div>
    </div>
</template>
<script>
import Inform from '../inform.vue';
export default {
    components: { Inform },
    data() {
        return {
            groupName: '',
            access: 'members',
            nameError: false,
            image: null,
            imageError: false,
            imageURL: null,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
        }
    },
    methods: {
        quitComponent() {
            this.$parent.openNewGroup();
        },
        getImage() {
            this.imageError = false;
            if(this.$refs.selectedImage.files[0]!=null){
                this.image = this.$refs.selectedImage.files[0];
                console.log("image: ",this.image);
                if (!this.image.type.includes("image")) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Please select an image file';
                    this.informModal = true;
                    this.imageError = true;
                }
                else {
                    this.imageError = false;
                    this.imageURL = URL.createObjectURL(this.image);
                }
            }
        },
        createGroup() {
            if (this.groupName.trim() === '') {
                this.nameError = true;
                return;
            }
            if (this.image == null || this.imageError == true) {
                this.modalTitle = 'Error';
                this.modalDetail = 'No image selected';
                this.informModal = true;
                return
            }
            this.$parent.createNewGroup(this.groupName, this.access, this.image);
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>

</style>
