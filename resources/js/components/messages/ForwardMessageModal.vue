<template>
    <div class="my-popup-component" @click.self="$emit('cancel')">
        <div class="brds-2 p-4 bg-white text-start" style="width: 520px; max-width: 92vw;">
            <button type="button" class="trans_btn float-end" @click="$emit('cancel')" style="font-size: 22px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h5 class="fw-bold mb-3">Forward message</h5>
            <p class="text-muted mb-3" style="font-size: 13px;">
                Forward this {{ message?.msg_type || 'message' }} to another client chat or group.
            </p>

            <label class="form-label mb-1" style="font-size: 13px;">Destination</label>
            <select v-model="targetType" class="form-select mb-3" style="font-size: 13px;">
                <option value="chat">Client chat (1:1)</option>
                <option value="group">Group chat</option>
            </select>

            <div v-if="targetType === 'chat'" class="mb-3">
                <label class="form-label mb-1" style="font-size: 13px;">Client</label>
                <select v-model="selectedChatId" class="form-select" style="font-size: 13px;">
                    <option value="">Select client</option>
                    <option v-for="chat in chats" :key="chat.id" :value="chat.id">{{ chat.user_name }}</option>
                </select>
            </div>

            <div v-else class="mb-3">
                <label class="form-label mb-1" style="font-size: 13px;">Group</label>
                <select v-model="selectedGroupId" class="form-select" style="font-size: 13px;">
                    <option value="">Select group</option>
                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                </select>
            </div>

            <button type="button" class="prim_btn py-2 px-4 brds-1" style="font-size: 13px;" :disabled="!canForward || loading" @click="forwardNow">
                {{ loading ? 'Forwarding...' : 'Forward' }}
            </button>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';

export default {
    props: {
        message: {
            type: Object,
            required: true,
        },
        sourceType: {
            type: String,
            required: true,
        },
    },
    emits: ['cancel', 'forwarded'],
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token'),
                },
            },
            targetType: 'chat',
            selectedChatId: '',
            selectedGroupId: '',
            chats: [],
            groups: [],
            loading: false,
        };
    },
    computed: {
        canForward() {
            return this.targetType === 'chat' ? !!this.selectedChatId : !!this.selectedGroupId;
        },
    },
    mounted() {
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
    },
    methods: {
        forwardNow() {
            this.loading = true;
            const payload = {
                source_type: this.sourceType,
                message_id: this.message.id,
                target_type: this.targetType,
                target_id: this.targetType === 'chat' ? this.selectedChatId : this.selectedGroupId,
            };
            axios.post(config.baseApiUrl + 'forward-message', payload, this.apiConfig)
                .then((res) => {
                    this.loading = false;
                    if (res.data.status) {
                        this.$emit('forwarded', res.data);
                    } else {
                        alert(res.data.message || 'Could not forward message');
                    }
                })
                .catch((er) => {
                    this.loading = false;
                    alert(er.response?.data?.message || er.message || 'Could not forward message');
                });
        },
    },
};
</script>
