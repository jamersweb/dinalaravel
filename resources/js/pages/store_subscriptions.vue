<template>
    <div style="border: 1px solid #e7e7e7; border-radius: 1em; overflow: hidden; height: calc(100vh - 125px)">
        <div class="sideBar border-end float-start">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom">
                <p class="mb-0 fw-bold" style="font-size: 14px;">App Store Orders</p>
            </div>
            <div>
                <div
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="switchTab(tab.id)"
                    class="grey_bg p-3 border-bottom"
                    :class="{ active: activeTab === tab.id }"
                    style="cursor: pointer;"
                >
                    <h6 class="px-3 mb-0" :class="{ active: activeTab === tab.id }" style="font-weight: bold;">
                        {{ tab.label }}
                    </h6>
                </div>
            </div>
        </div>

        <div class="position-relative float-start" style="width: calc(100% - 250px); height: 100%; overflow: hidden;">
            <StoreOrdersPanel :platform-filter="activeTab" :title="activeTabLabel" />
        </div>
    </div>
</template>

<script>
import StoreOrdersPanel from '../components/payments/store_orders_panel.vue';

export default {
    components: { StoreOrdersPanel },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            tabs: [
                { id: 'all', label: 'All orders' },
                { id: 'ios', label: 'Apple (iOS)' },
                { id: 'android', label: 'Google (Android)' },
            ],
            activeTab: 'all',
        };
    },
    computed: {
        activeTabLabel() {
            const tab = this.tabs.find((t) => t.id === this.activeTab);
            return tab ? tab.label : 'App Store Orders';
        },
    },
    mounted() {
        this.$emit('adminCheckEvent');
    },
    methods: {
        switchTab(tabId) {
            this.activeTab = tabId;
        },
    },
};
</script>

<style scoped>
.active {
    color: #f2a18c;
}
</style>
