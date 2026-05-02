<template lang="">
    <div class="d-flex justify-content-between p-2 item mb-2">
        <div class="d-flex w-80">
            <div style="width:20%;">
                <img v-if="adminActivityData.userImage!=null" :src="adminActivityData.userImage" alt="" class="img-fluid rounded-circle" style="height:55px;width:55px;" >
                <img v-else src="/cms-assets/images/navbar-topbar/profile.png" alt="" class="img-fluid rounded-circle" style="height:55px;width:55px;">
            </div>
            <div class="px-2" style="width:75%;">
                <p class="h7 mb-1"><strong>{{adminActivityData.userName}}</strong></p>
                <p style="font-size:x-small" class="muted-text mb-0">{{adminActivityData.content}}</p>
            </div>
        </div>
        <div class="d-flex flex-column w-20">
            <!-- <i class="fa-solid fa-ellipsis ms-auto"></i> -->
            <span class="mt-2" style="font-size:9px;">{{adminActivityData.timeDiff}}</span>
        </div>
    </div>
</template>
<script>

export default {
    props: ['adminActivityData'],
    data() {
        return {
            Activitiesarray: []
        }
    },
}
</script>
<style lang="">

</style>
