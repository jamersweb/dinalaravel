<template lang="">
    <div class="d-flex py-3 px-2 chat-item" style="position :relative;">
        <div class="w-20 px-1 text-center">
            <img :src="groupInfo.image" alt="" class="rounded-circle" style="height:35px;width:35px">
        </div>
        <div @click="openGroup(groupInfo)" class="w-80 pointer">
            <div class="d-flex justify-content-between">
                <div>
                    <p class="h7 mb-0">
                        <strong class="text-capitalize">{{groupInfo.name}}</strong>
                        <span class="ms-3 badge bg-danger rounded-circle" v-if="groupInfo.unread!==0&&badgeShow==true">{{groupInfo.unread}}</span>
                    </p>
                    <p v-if="groupInfo.last_type==='text'" class="h8 mb-0 text-muted">{{groupInfo.last_message}}</p>
                    <p v-else class="h8 mb-0 text-muted text-capitalize">- {{groupInfo.last_type}}</p>
                </div>
            </div>
        </div>
        <div class="ms-2 dropdown mt-2">
            <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
            <ul class="tsl dropdown-menu border-0">
                <li v-if="groupInfo.label=='all'"><button class="dropdown-item" @click="changeType(groupInfo.id)">Add to My Group</button></li>
                <li v-else><button class="dropdown-item" @click="changeType(groupInfo.id)">Remove from My Group</button></li>
                <li><button class="dropdown-item" @click="rename(groupInfo.id,groupInfo.name)">Rename</button></li>
                <li><button class="dropdown-item" @click="addMembers(groupInfo.id)">Add Members</button></li>
                <li><button class="dropdown-item" @click="leaveGroup(groupInfo.id,groupInfo.name)">Leave</button></li>
                <li><button class="dropdown-item" @click="deleteGroup(groupInfo.id,groupInfo.name)">Delete</button></li>
            </ul>
        </div>
    </div>
</template>
<script>
export default {
    props: ['groupInfo'],
    data() {
        return {
            dropdown: false,
            badgeShow: true,
        }
    },
    methods: {
        openGroup(groupData) {
            this.badgeShow = false;
            this.$parent.openGroup(groupData);
        },
        changeType(id) {
            this.$parent.changeGroupType(id);
        },
        leaveGroup(id, name) {
            this.$parent.leaveGroup(id, 'confirm', name);
        },
        rename(id, name) {
            this.$parent.renameGroup(id, null, name);
        },
        addMembers(id) {
            this.$parent.addMembers(null, id);
        },
        deleteGroup(id, name) {
            this.$parent.deleteGroup(id, 'confirm', name);
        }
    },
}
</script>
<style scoped>
.chat-item {
    border-bottom: 1px solid rgb(192, 192, 192);
}
</style>
