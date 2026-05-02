<template lang="">
    <div class="item d-flex justify-content-between p-2 mt-2" v-for="items in usersData" :key="items">
        <div class="d-flex">
            <img v-if="items.image" :src="items.image"
                alt="" class="img-fluid rounded-circle px-2"
                style="height: 35px;width:50px;">
            <img v-else src="/cms-assets/images/navbar-topbar/profile.png"
                alt="" class="img-fluid rounded-circle px-2"
                style="height: 35px;width:50px;">
            <span class="d-flex align-items-center fw-bold">{{items.full_name}}</span>
        </div>
        <div class="d-flex align-items-center">
            <button @click="viewDetail(items.id)" class="viewbtn px-3">View</button>
        </div>
    </div>
</template>
<script>
export default {
    props: ['usersData'],
    methods: {
        viewDetail(m) {
            this.$parent.ClientPopup(m);
        }
    }
}
</script>
<style scoped>
.viewbtn {
    background-color: #F2A18C;
    border: 1px solid rgb(221, 221, 221);
    color: white;
    font-weight: bold;
    font-size: small;
    border-radius: 5px;
}
</style>
