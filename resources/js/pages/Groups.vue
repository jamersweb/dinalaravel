<template lang="">
    <div @click.self="memberDetail=false" v-if="memberDetail" style="height:100vh;width:100vw;background-color:transparent;position:absolute;top:0;left:0;z-index:99"></div>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Preview v-if="previewOn" :file="previewFile" :filetype="previewType"/>
    <Loader v-if="pageLoading" :loadingText="loadingText"/>
    <!-- <groupMembersVue v-if="groupMemberAdd" :groupId="idForAction"/> -->
    <!-- <GetInput v-if="false" :msgTitle="modalTitle" :msgDetail="modalDetail"/> -->
    <div style="display: flex">
        <div class="d-flex w-100 msgs-box" >
            <div class="chats-side">
                <div class="chats-head p-2">
                    <div class="position-relative">
                        <input type="text" class="w-100 ps-4" placeholder="Search Groups" v-model="search" />
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon"
                            class="img-fluid position-absolute" />
                    </div>
                    <!-- <div class="mt-2">
                        <select name="" id="">
                            <option value="">Active Messages</option>
                        </select>
                    </div> -->
                    <!-- <div class="d-flex justify-content-center w-100 mt-3 ch-opt">
                        <button @click="newChatCompVisib = true" class="prim_btn py-1 h7">New</button>
                        <button class="trans_bg py-1 h7">Archive</button>
                        <div class="dropdown">
                            <button class="trans_btn px-2 py-1 h7 border-0 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                More
                            </button>
                            <ul class="tsl dropdown-menu border-0">
                                <li><button class="dropdown-item">Mark All Read</button></li>
                                <li><button class="dropdown-item">Archive All</button></li>
                            </ul>
                        </div>
                    </div> -->
                    <div class="mt-3 position-relative" style="height:29px;">
                        <button @click="openNewGroup()" style="width:40%;font-size:12px;" class="prim_btn py-1 h7 brds-1 float-start h-100">Add Group</button>
                        <select v-model="tabFilter" style="width:59%;float:right;height:100%;padding:0px;border-radius:5px !important">
                            <option value="all" selected>All Groups</option>
                            <option value="my" >My Groups</option>
                        </select>
                    </div>
                </div>
                <div class="chats-list my-1">
                    <div v-if="groupProps.groups.length==0" class="mt-3 text-center">
                        <p>No Groups Yet</p>
                    </div>
                    <div v-else-if="filteredGroupsSearch.length<1" class="mt-3 text-center">
                        <p>No Group for the filter</p>
                    </div>
                    <div v-for="groupSingle in filteredGroupsSearch">
                        <GroupItem :groupInfo="groupSingle"/>
                    </div>
                </div>
            </div>
            <div class="convo-side">
                <div v-if="groupProps.active===null" style="overflow-y:auto;height:calc(100vh - 177px)">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div>
                            <img src="/cms-assets/images/no-message.png" alt="" class="img-fluid d-block mx-auto" style="max-height:250px">
                            <div class="mt-3 text-center">
                                <h5 class="mb-2"><strong>Select a group to view</strong></h5>
                                <p class="mb-0 text-muted">Stay connected with your clients and fellow team <br>members with one-to-one or group private messages</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="groupProps.active.members===0" class="h-100">
                    <div class="d-flex flex-column justify-content-center align-items-center h-100">
                        <div class="text-center">
                            <h3 class="mb-2 fw-bold">No Members Assigned Yet</h3>
                            <button class="prim_btn brds-2 fw-bold" @click="addMembers(null,groupProps.active.id)">Add Members</button>
                        </div>
                    </div>
                </div>
                <div v-else class="h-100">
                    <div class="convo-head p-3 d-flex justify-content-between position-relative">
                        <div class="d-flex pointer" @click="toggleMembersDetail()">
                            <div class="px-2 align-self-center">
                                <img :src="groupProps.active.image" alt="" class="rounded-circle" style="width:40px;height:40px"/>
                            </div>
                            <div>
                                <div>
                                    <p class="h7 mb-0 text-capitalize"><strong>{{groupProps.active.name}}</strong></p>
                                </div>
                                <div>
                                    <p style="color: #f2a18c" class="h8 mb-0">{{groupProps.active.members}} Members</p>
                                </div>
                            </div>
                        </div>
                        <div tabindex="0" @focusout="memberDetail=false" v-if="memberDetail" class="shd_card p-3 members-detail">
                            <!-- <p v-if="membersList.length===0" class="text-center">No Members</p> -->
                            <div v-for="member in membersList" class="d-flex justify-content">
                                <p class="text-capitalize px-2 py-1 m-0">{{member.name}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="convo-content">
                        <div class="d-flex flex-column justify-content-end h-100">
                            <div id="messageDiv" style="overflow-y: auto;" class="px-4 pb-4" ref="scrollable">
                                <MsgItem v-for="msg in groupProps.messages" :msgdet="msg"/>
                            </div>
                        </div>
                    </div>
                    <div class="convo-type" style="border-top:1px solid #dbdbdb">
                        <div class="w-70" style="border-right:1px solid #dbdbdb">
                            <textarea type="text" rows="1" class="w-100"
                            @keyup.exact.enter="checkKey('send')"
                            @keyup.exact.enter.shift="checkKey('break')"
                            placeholder="Type a message here. Shift-Enter for a new line."
                            v-model="newMessage"></textarea>
                        </div>
                        <div class="me-4 d-flex align-items-center">
                            <button class="trans_btn position-relative">
                                <img @click="toogleEmojiPicker" style="max-height:25px" src="/cms-assets/images/Shape.png" class="img-fluid">
                                <EmojiPicker v-if="emojiPicker"/>
                            </button>
                            <button @click="openImageInput()" class="trans_btn">
                                <img style="max-height:25px" src="/cms-assets/images/image.png" class="img-fluid">
                            </button>
                            <input @change="getImageFile()" type="file" accept=".jpg, .png, .jpeg" class="d-none" ref="imageFileRef">
                            <button @click="openVideoInput()" class="trans_btn">
                                <img style="max-height:25px" src="/cms-assets/images/video.png" class="img-fluid">
                            </button>
                            <input @change="getVideoFile()" type="file" accept=".mp4, .mkv, .mov, .m4v" class="d-none" ref="videoFileRef">
                            <button @click="openDocInput()" class="trans_btn">
                                <img style="max-height:25px" src="/cms-assets/images/document.png" class="img-fluid">
                            </button>
                            <input @change="getDocFile()" type="file" accept=".docx,.pdf,.txt,.doc" class="d-none" ref="docFileRef">
                            <button class="trans_btn" @click="getAudioInput()">
                                <img style="max-height:25px" src="/cms-assets/images/audio.png" class="img-fluid">
                            </button>
                            <input type="file" accept=".mp3,.m4a,.webm" class="d-none" ref="audioFileRef">
                            <button @click="sendTextMessage()" class="trans_btn">
                                <img style="max-height:35px" src="/cms-assets/images/send.png" class="img-fluid">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-if="emojiPicker" class="position-absolute" style="height: 100vh;width: 100vw;top: 0;left: 0;" @click.self="toogleEmojiPicker">
    </div>
    <createGroup v-if="showGroup"/>
    <addGroupMember v-if="groupMemberAdd" :groupId="idForAction"/>
    <renameGroup v-if="getInputOn" :renameValue="renameValue"/>
    <removeGroup v-if="deletePopup" :msgTitle="modalTitle" :msgDetail="modalDetail" :btnValue1="btn1Value" :btnValue2="btn2Value"/>
</template>
<script>
import config from "../config";
import MsgItem from "../components/groups/msg-item.vue";
import Inform from '../components/inform.vue';
import Confirm from '../components/confirm.vue';
import Loader from '../components/loader.vue';
import Preview from '../components/messages/preview.vue';
import EmojiPicker from '../components/messages/EmojiPicker.vue';
import createGroup from "../components/groups/createGroup.vue";
import addGroupMember from "../components/groups/addGroupMember.vue";
import groupSettings from "../components/groups/groupSettings.vue";
import renameGroup from "../components/groups/renameGroup.vue";
import removeGroup from "../components/groups/removeGroup.vue";
import GroupItem from "../components/groups/group-item.vue";
import axios from "axios";
import groupMembersVue from "../components/groups/groupMembers.vue";
import GetInput from "../components/getInput.vue";

//  Vue.use(AudioRecorder);
export default {
    components: {
        GroupItem, MsgItem, Inform, Confirm, Loader, Preview, groupMembersVue, GetInput,
        EmojiPicker, createGroup, addGroupMember, groupSettings, renameGroup, removeGroup
    },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent',
        'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            emojiPicker: false,
            newMessage: null,
            getInputVisi: false,
            getInputTitle: '',
            getInputDetail: '',
            firstMsgTo: '',
            previewOn: false,
            previewFile: null,
            previewType: null,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            pageLoading: false,
            loadingText: "Loading",
            search: "",
            showGroup: false,
            idForAction: null,
            actionType: '',
            groupMemberAdd: false,
            getInputOn: false,
            renameValue: null,
            btn1Value: null,
            btn2Value: null,
            deletePopup: false,
            memberDetail: false,
            membersList: [],
            tabFilter: 'all'
        };
    },
    mounted() {
        this.$emit("adminCheckEvent");
        this.$emit("getGroupsEvent", 1);
    },
    beforeUnmount() {
        this.groupProps.active = null;
    },
    computed: {
        filteredGroupsSearch() {
            return this.filterGroupsTab.filter((chatData) => {
                return chatData.name.toLowerCase().includes(this.search.toLowerCase());
            });
        },
        filterGroupsTab() {
            if (this.tabFilter == 'my') {
                return this.groupProps.groups.filter((chatData) => {
                    return chatData.label.toLowerCase().includes(this.tabFilter.toLowerCase());
                })
            }
            else {
                return this.groupProps.groups
            }
        }
    },
    methods: {
        toogleEmojiPicker() {
            this.emojiPicker = !this.emojiPicker;
        },
        changeGroupType(id) {
            this.groupProps.active = null;
            this.pageLoading = true;
            this.loadingText = 'Changing Group Type';
            axios.get(config.baseApiUrl + 'switch-group-label/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Group Type Changed Successfully';
                    this.informModal = true;
                    this.$emit("getGroupsEvent");
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        deleteMessage(id) {
            this.pageLoading = true;
            this.loadingText = 'Deleteing';
            axios.get(config.baseApiUrl + 'delete-group-message/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.$emit('getGroupsEvent');
                }
                else
                    this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er.message;
                this.informModal = true;
            });
        },
        createNewGroup(name, access, image) {
            this.loadingText = "Creating Group";
            this.pageLoading = true;
            let fd = new FormData();
            const apiCon = {
                "headers": {
                    "Authorization": "Bearer " + config.storage.getItem('fwd_session_token'),
                    "Content-Type": "multipart/form-data"
                }
            }
            fd.append('image', image);
            fd.append('name', name);
            fd.append('msg_access', access);
            axios.post(config.baseApiUrl + 'create-group', fd, apiCon).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.showGroup = false;
                    this.$emit("getGroupsEvent");
                } else {
                    this.modalTitle = 'Failed';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = 'Something Went Wrong';
                console.log("create group error: ", er.message);
                this.informModal = true;
            });
        },
        addMembers(usersArray, groupId) {
            if (usersArray === null) {
                this.idForAction = groupId;
                this.groupMemberAdd = true;
            } else {
                this.groupMemberAdd = false;
                const postData = {
                    "group_id": this.idForAction,
                    "user_ids": usersArray
                }
                this.loadingText = "Adding Members"
                this.pageLoading = true;
                axios.post(config.baseApiUrl + 'add-members', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$emit("getGroupsEvent");
                        this.groupProps.active = null;
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("create group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        updateScroll() {
            setTimeout(() => {
                let element = document.getElementById("messageDiv");
                element.scrollTop = element.scrollHeight;
            }, 300)
        },
        toggleMembersDetail() {
            this.membersList = [];
            if (!this.memberDetail) {
                this.loadingText = 'Loading';
                axios.get(config.baseApiUrl + 'group-members/' + this.groupProps.active.id, this.apiConfig).then(res => {
                    if (res.data.status)
                        this.membersList = res.data.data;
                }).catch(er => {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("group members error: ", er.message);
                    this.informModal = true;
                })
            }
            this.memberDetail = !this.memberDetail;
        },
        openGroup(group) {
            this.$emit('activeGroupEvent', group, 1);
        },
        sendTextMessage() {
            this.newMessage = this.newMessage.trim();
            if (this.newMessage == null || this.newMessage == '')
                return;
            const postData = {
                group_id: this.groupProps.active.id,
                content: this.newMessage
            }
            this.newMessage = '';
            axios.post(config.baseApiUrl + 'send-group-text-message', postData, this.apiConfig).then(res => {
                if (res.data.status)
                    this.$emit('getGroupMessagesEvent', res.data.sent_msg);
                else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            });
        },
        sendFileMessage(file) {
            this.previewOn = false;
            const apiCon = {
                "headers": {
                    "Authorization": "Bearer " + config.storage.getItem('fwd_session_token'),
                    "Content-Type": "multipart/form-data"
                }
            }
            let fd = new FormData();
            fd.append('file', file);
            fd.append('group_id', this.groupProps.active.id);
            axios.post(config.baseApiUrl + 'send-group-file-message', fd, apiCon).then(res => {
                if (res.data.status)
                    this.$emit('getGroupMessagesEvent', res.data.sent_msg);
                else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            });
        },
        openNewGroup() {
            this.showGroup = !this.showGroup;
        },
        openImageInput() {
            this.$refs.imageFileRef.click();
        },
        openVideoInput() {
            this.$refs.videoFileRef.click();
        },
        openDocInput() {
            this.$refs.docFileRef.click();
        },
        getAudioInput() {
            this.previewFile = null;
            this.previewType = "audio";
            this.previewOn = true;
        },
        getImageFile() {
            let tempFile = this.$refs.imageFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "image";
                this.previewOn = true;
            }
        },
        getVideoFile() {
            let tempFile = this.$refs.videoFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "video";
                this.previewOn = true;
            }
        },
        getDocFile() {
            let tempFile = this.$refs.docFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "document";
                this.previewOn = true;
            }
        },
        closeEmojiInput() {
            this.emojiPicker = false;
        },
        getEmojiInput(emoji) {
            if (this.newMessage == null)
                this.newMessage = '';
            this.newMessage = this.newMessage + emoji;
        },
        checkKey(eve) {
            if (eve === 'send')
                this.sendTextMessage();
        },
        closePreviewComp() {
            this.previewOn = false;
        },
        quitRename() {
            this.getInputOn = false;
        },
        renameGroup(id, newname, oldname) {
            this.groupProps.active = null;
            this.getInputOn = !this.getInputOn;
            if (newname === null) {
                this.idForAction = id;
                this.renameValue = oldname;
            } else {
                const postData = {
                    "group_id": this.idForAction,
                    "name": newname
                }
                this.pageLoading = true;
                axios.post(config.baseApiUrl + 'rename-group', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("rename group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        leaveGroup(id, type, name) {
            this.groupProps.active = null;
            this.deletePopup = !this.deletePopup;
            if (type === 'confirm') {
                this.idForAction = id;
                this.modalTitle = 'Leave and delete group?';
                this.modalDetail = 'Do you want to leave  ' + name + ' ? Because leaving will also delete this group.'
                this.btn1Value = 'Leave & Delete';
                this.btn2Value = 'Cancel';
                this.actionType = 'leaveGroup';
            } else {
                this.pageLoading = true;
                this.loadingText = 'Leaving';
                axios.get(config.baseApiUrl + 'leave-group/' + this.idForAction, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.groupProps.active = null;
                        this.deletePopup = false;
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("leave group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        quitDelete() {
            this.deletePopup = false;
        },
        deleteGroup(id, type, name) {
            this.groupProps.active = null;
            this.deletePopup = !this.deletePopup;
            if (type === 'confirm') {
                this.idForAction = id;
                this.modalTitle = 'Delete group?';
                this.modalDetail = 'This group’s message history will be deleted. This cannot be undone.';
                this.btn1Value = 'Delete';
                this.btn2Value = 'Cancel';
                this.actionType = 'deleteGroup';
            } else {
                this.pageLoading = true;
                this.loadingText = 'Deleting';
                axios.get(config.baseApiUrl + 'delete-group/' + this.idForAction, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.groupProps.active = null;
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("delete group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        confirmationResponse(resp) {
            this.confirmModal = false;
            if (resp === 1) {
                if (this.actionType === 'leaveGroup')
                    this.leaveGroup(null, 'confirmed', null);
                if (this.actionType === 'deleteGroup')
                    this.deleteGroup(null, 'confirmed', null);
            }
        },
        inputResponse(value) {
            this.getInputOn = false;
            if (value === null)
                return;
            this.renameGroup(null, value, null);
        },
    },
};
</script>
<style scoped>
textarea {
    border: none;
    padding-top: 12px;
    padding-left: 20px;
    padding-bottom: 10px;
}

.chats-side {
    background-color: #eeeeee;
    border-top-left-radius: 1rem;
    border-bottom-left-radius: 1rem;
    border-right: 1px solid rgb(228, 228, 228);
    overflow: auto;
    width: 270px;
}

.convo-side {
    width: calc(100% - 270px);
}

.none {
    display: none;
}

.msgs-box {
    border: 1px solid rgb(228, 228, 228);
    border-radius: 1rem;
    height: calc(100vh - 127px);
}

.chats-head input,
.chats-head select {
    padding: 10px;
    border-radius: 10px;
    font-size: 12px;
    border: 1px solid rgb(192, 192, 192);
    background-color: white;
    width: 100%;
    color: gray;
}

.chats-head input:focus,
.chats-head select:focus {
    outline: none;
}

.chats-head img {
    height: 12px;
    left: 7px;
    top: 13px;
}

.chats-list {
    border-top: 1px solid rgb(216, 216, 216);
}

.convo-head {
    border-bottom: 1px solid gainsboro;
}

.convo-head img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
}

.convo-content {
    height: calc(100% - 125px);
}

.convo-type {
    display: flex !important;
    justify-content: space-between !important;
    /*overflow-y: hidden;*/
    align-items: center;
}

.convo-type input {
    border: none;
    padding-top: 14px;
    padding-bottom: 14px;
    padding-left: 25px;
    width: 100%;
}

.ch-opt>* {
    border: 1px solid rgb(170, 170, 170);
    margin: 0;
    width: 100%;
}

.ch-opt>*:first-child {
    border-top-left-radius: 5px;
    border-bottom-left-radius: 5px;
    border-right: 0;
}

.ch-opt>*:last-child {
    border-top-right-radius: 5px;
    border-bottom-right-radius: 5px;
    border-left: 0;
}

.members-detail {
    position: absolute;
    left: 100px;
    top: 60px;
    width: 206px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 99;
}

.members-detail div {
    border-bottom: 1px solid rgb(197, 197, 197);
}
</style>
