<template>
    <div class="my-popup-component" @click.self="$emit('cancel')">
        <div class="brds-2 p-4 bg-white text-start" style="width: 520px; max-width: 92vw;">
            <button type="button" class="trans_btn float-end" @click="$emit('cancel')" style="font-size: 22px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 class="fw-bold mb-3">Send voice message</h5>
            <p class="text-muted mb-3" style="font-size: 13px;">Choose who should receive this voice message.</p>

            <label class="form-label mb-1" style="font-size: 13px;">Send to</label>
            <select v-model="targetType" class="form-select mb-3" style="font-size: 13px;">
                <option v-if="currentChatId" value="current_chat">Current client chat</option>
                <option v-if="currentGroupId" value="current_group">Current group</option>
                <option value="client">Another client (1:1)</option>
                <option value="group">A group</option>
                <option value="program">Program subscribers</option>
            </select>

            <div v-if="targetType === 'client'" class="mb-3">
                <label class="form-label mb-1" style="font-size: 13px;">Client</label>
                <select v-model="selectedChatId" class="form-select" style="font-size: 13px;">
                    <option value="">Select client</option>
                    <option v-for="chat in chats" :key="chat.id" :value="chat.id">{{ chat.user_name }}</option>
                </select>
            </div>

            <div v-if="targetType === 'group'" class="mb-3">
                <label class="form-label mb-1" style="font-size: 13px;">Group</label>
                <select v-model="selectedGroupId" class="form-select" style="font-size: 13px;">
                    <option value="">Select group</option>
                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                </select>
            </div>

            <div v-if="targetType === 'program'" class="mb-3">
                <label class="form-label mb-1" style="font-size: 13px;">Program</label>
                <select v-model="selectedProgramId" class="form-select" style="font-size: 13px;" @change="loadProgramSubscribers">
                    <option value="">Select program</option>
                    <option v-for="program in programs" :key="program.id" :value="program.id">{{ program.title }}</option>
                </select>
                <p v-if="programSubscriberCount !== null" class="mb-0 mt-2" style="font-size: 12px; color: #707070;">
                    {{ programSubscriberCount }} active subscriber(s) will receive this voice message.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button type="button" class="prim_btn py-2 px-3 brds-1" style="font-size: 13px;" :disabled="!canContinue" @click="$emit('record', buildTarget())">
                    Record voice
                </button>
                <button type="button" class="prim_btn py-2 px-3 brds-1" style="font-size: 13px; background-color: #343434;" :disabled="!canContinue" @click="$emit('upload', buildTarget())">
                    Upload audio file
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';

export default {
    props: {
        pageMode: {
            type: String,
            default: 'messages',
        },
        currentChatId: {
            type: [Number, String],
            default: null,
        },
        currentGroupId: {
            type: [Number, String],
            default: null,
        },
    },
    emits: ['cancel', 'record', 'upload'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token'),
                },
            },
            targetType: 'current_chat',
            selectedChatId: '',
            selectedGroupId: '',
            selectedProgramId: '',
            programSubscriberIds: [],
            programSubscriberCount: null,
            chats: [],
            groups: [],
            programs: [],
        };
    },
    computed: {
        canContinue() {
            if (this.targetType === 'current_chat') {
                return !!this.currentChatId;
            }
            if (this.targetType === 'current_group') {
                return !!this.currentGroupId;
            }
            if (this.targetType === 'client') {
                return !!this.selectedChatId;
            }
            if (this.targetType === 'group') {
                return !!this.selectedGroupId;
            }
            if (this.targetType === 'program') {
                return this.programSubscriberIds.length > 0;
            }
            return false;
        },
    },
    mounted() {
        if (this.pageMode === 'groups' && this.currentGroupId) {
            this.targetType = 'current_group';
        } else if (this.currentChatId) {
            this.targetType = 'current_chat';
        } else if (this.pageMode === 'groups') {
            this.targetType = 'group';
        } else {
            this.targetType = 'client';
        }
        this.loadOptions();
    },
    methods: {
        loadOptions() {
            axios.get(config.baseApiUrl + 'all-chats', this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.chats = res.data.data || [];
                }
            }).catch(() => {});
            axios.get(config.baseApiUrl + 'my-groups', this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.groups = res.data.data || [];
                }
            }).catch(() => {});
            axios.get(config.baseApiUrl + 'get-all-programs', this.apiConfig).then((res) => {
                if (res.data.status) {
                    this.programs = res.data.data || [];
                }
            }).catch(() => {});
        },
        loadProgramSubscribers() {
            this.programSubscriberIds = [];
            this.programSubscriberCount = null;
            if (!this.selectedProgramId) {
                return;
            }
            axios.get(config.baseApiUrl + 'program-subscriber-ids/' + this.selectedProgramId, this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.programSubscriberIds = res.data.data || [];
                        this.programSubscriberCount = this.programSubscriberIds.length;
                    }
                })
                .catch(() => {});
        },
        buildTarget() {
            if (this.targetType === 'current_chat') {
                return { type: 'chat', chatId: this.currentChatId };
            }
            if (this.targetType === 'current_group') {
                return { type: 'group', groupId: this.currentGroupId };
            }
            if (this.targetType === 'client') {
                return { type: 'chat', chatId: this.selectedChatId };
            }
            if (this.targetType === 'group') {
                return { type: 'group', groupId: this.selectedGroupId };
            }
            return { type: 'program', userIds: this.programSubscriberIds };
        },
    },
};
</script>
