<template lang="">
    <div class="d-flex py-3 px-2 chat-item" style="position :relative;">
        <div class="w-20 px-1 text-center">
            <img :src="chatData.image" alt="" class="rounded-circle" style="height:35px;width:35px">
        </div>
        <div  @click="openChat(chatData)" class="w-80 pointer">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="h7 mb-0">
                        <strong class="text-capitalize">{{chatData.user_name}}</strong>
                        <span class="ms-3 badge bg-danger rounded-circle" v-if="chatData.unread!==0&&badgeShow==true">{{chatData.unread}}</span>
                    </p>
                    <p v-if="chatData.last_type==='text'" class="h8 mb-0 text-muted">{{chatData.last_message}}</p>
                    <p v-else class="h8 mb-0 text-muted text-capitalize">- {{chatData.last_type}}</p>
                </div>
            </div>
        </div>
        <div class="ms-2 dropdown position-absolute" style="right:5px;">
            <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="tsl dropdown-menu border-0">
                <!-- <li><button class="dropdown-item">Archive</button></li> -->
                <li><button class="dropdown-item" @click.self="deleteChat(chatData.id)">Delete</button></li>
                <li v-if="chatData.status=='active'"><button class="dropdown-item" @click.self="archiveChat(chatData.id)">Archive</button></li>
                <li v-else><button class="dropdown-item" @click.self="unarchiveChat(chatData.id)">Unarchive</button></li>
            </ul>
        </div>
    </div>
</template>
<script>
export default {
    props: ['chatData'],
    data() {
        return {
            dropdown: false,
            badgeShow: true,
        }
    },
    methods: {
        openChat(chatData) {
            this.badgeShow = false;
            this.$parent.openChat(chatData)
        },
        deleteChat(id) {
            this.$parent.deleteChat(id);
        },
        archiveChat(id) {
            this.$parent.archiveChat(id);
        },
        unarchiveChat(id) {
            this.$parent.unarchiveChat(id);
        }
    },
}
</script>
<style scoped>
.chat-item {
    border-bottom: 1px solid rgb(192, 192, 192);
}
</style>
