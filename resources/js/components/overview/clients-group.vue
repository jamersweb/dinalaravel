<template class="w-100 mt-2" lang="">
    <div class="d-flex justify-content-between mb-1">
        <div class="users ps-5 d-flex align-items-center" style="height:40px;">
            <img v-if="clientsData&&clientsData.length<5" @click="toClientScreen" v-for="(item, index) in clientsData" :key="index" :src="item.picture" alt="" class="img-fluid me-3 rounded-circle" style="height:40px;width:40px;" :title="item.name+' '+item.Lastname">
            <div v-if="clientsData&&clientsData.length>4">
                <img @click="toClientScreen" :src="clientsData[0].picture" alt="" class="img-fluid me-3 rounded-circle float-start" style="height:40px;width:40px;" :title="clientsData[0].name+' '+clientsData[0].Lastname">
                <img @click="toClientScreen" :src="clientsData[1].picture" alt="" class="img-fluid me-3 rounded-circle float-start" style="height:40px;width:40px;" :title="clientsData[1].name+' '+clientsData[1].Lastname">
                <img @click="toClientScreen" :src="clientsData[2].picture" alt="" class="img-fluid me-3 rounded-circle float-start" style="height:40px;width:40px;" :title="clientsData[2].name+' '+clientsData[2].Lastname">
                <div @click="toClientScreen" class="position-relative float-start">
                    <img src="/cms-assets/images/Clients/RepeatGrid3.png" alt="" class="img-fluid rounded-circle" style="height:40px;width:40px;">
                    <p class="mb-0 position-absolute w-100 h-100" style="color:white;padding-top:19%;text-align:center;top:0;left:0;">+{{clientsData.length-4}}</p>
                </div>
            </div>
            <p v-if="clientsData&&clientsData.length<1" class="mb-0" style="font-size:12px;">--No clients to display</p>
        </div>
        <div class="d-flex align-items-center">
            <button v-if="clientsData&&clientsData.length>4" @click="toClientScreen" class="prim_btn prim_btn_sm prim_btn_rnd">View All</button>
        </div>

    </div>
    <p class="d-none">{{clientIds}}</p>
</template>
<script>
export default {
    props: ['clientsData', 'clientType'],
    data() {
        return {
            clientIds: [],
        }
    },
    methods: {
        toClientScreen() {
            this.clientIds = [];
            for (let index = 0; index < this.clientsData.length; index++) {
                this.clientIds.push(this.clientsData[index].user_id)
            }
            let userIds = JSON.stringify(this.clientIds);
            userIds = window.btoa(userIds);
            this.$router.push('/cms/clients?users=' + userIds);
        }
    }
}
</script>
<style lang="">

</style>
