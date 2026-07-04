<template>
    <div class="position-relative" style="width: 100%; height: 100%; overflow: hidden;">
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" @acknowledged="acknowledged" />

        <div class="d-flex justify-content-between ps-3 pt-2 align-items-center flex-wrap gap-2" style="width: 100%; min-height: 50px; background-color: #eeeeee;">
            <div>
                <p class="m-0" style="font-size: 26px;">{{ title }}</p>
                <p class="m-0 mb-2" style="font-size: 11px; color: #707070;">
                    Apple App Store and Google Play subscription verifications from the mobile app.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2 pe-3 flex-wrap">
                <select
                    v-if="showPlatformFilter"
                    v-model="internalPlatform"
                    @change="loadRecords(1)"
                    class="brds-1 px-2"
                    style="height: 35px; font-size: 12px;"
                >
                    <option value="all">All stores</option>
                    <option value="ios">Apple (iOS)</option>
                    <option value="android">Google (Android)</option>
                </select>
                <select v-model="statusFilter" @change="loadRecords(1)" class="brds-1 px-2" style="height: 35px; font-size: 12px;">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="pending">Pending</option>
                </select>
                <div class="position-relative" style="width: 235px; height: 35px;">
                    <input
                        type="text"
                        placeholder="Search client, order id, product"
                        class="py-2 pe-2 ps-4 w-100"
                        v-model="search"
                        @keyup.enter="loadRecords(1)"
                        style="background-color: white; border: 1px solid rgb(167, 166, 166); border-radius: 10px; font-size: 10px;"
                    >
                    <img src="/cms-assets/images/navbar-topbar/search.png" alt="search" class="img-fluid position-absolute" style="max-height: 12px; left: 7px; top: 10px;">
                </div>
                <button class="prim_btn py-1 px-3 brds-1" style="font-size: 12px;" @click="loadRecords(1)">Search</button>
            </div>
        </div>

        <div class="px-3 pt-3 d-flex flex-wrap gap-3" v-if="summary">
            <div class="tsl brds-2 px-3 py-2" style="min-width: 120px;">
                <p class="mb-0" style="font-size: 11px; color: #707070;">Total records</p>
                <p class="mb-0 fw-bold" style="font-size: 20px;">{{ summary.total }}</p>
            </div>
            <div class="tsl brds-2 px-3 py-2" style="min-width: 120px;">
                <p class="mb-0" style="font-size: 11px; color: #707070;">Active</p>
                <p class="mb-0 fw-bold" style="font-size: 20px; color: #198754;">{{ summary.active }}</p>
            </div>
            <div class="tsl brds-2 px-3 py-2" style="min-width: 120px;">
                <p class="mb-0" style="font-size: 11px; color: #707070;">Apple</p>
                <p class="mb-0 fw-bold" style="font-size: 20px;">{{ summary.ios }}</p>
            </div>
            <div class="tsl brds-2 px-3 py-2" style="min-width: 120px;">
                <p class="mb-0" style="font-size: 11px; color: #707070;">Google</p>
                <p class="mb-0 fw-bold" style="font-size: 20px;">{{ summary.android }}</p>
            </div>
        </div>

        <div class="p-3" :style="{ height: summary ? 'calc(100% - 170px)' : 'calc(100% - 90px)', overflow: 'auto' }">
            <Vue3EasyDataTable
                :headers="headers"
                :items="items"
                table-class-name="customize-table"
            >
                <template #item-client_name="item">
                    <button
                        v-if="item.user_id"
                        type="button"
                        class="client-link-btn"
                        @click="openClientProfile(item.user_id, 'invoices')"
                    >
                        {{ item.client_name }}
                    </button>
                    <span v-else>{{ item.client_name }}</span>
                </template>
                <template #item-client_email="item">
                    <button
                        v-if="item.user_id && item.client_email && item.client_email !== '—'"
                        type="button"
                        class="client-link-btn"
                        @click="openClientProfile(item.user_id, 'invoices')"
                    >
                        {{ item.client_email }}
                    </button>
                    <span v-else>{{ item.client_email }}</span>
                </template>
                <template #item-amount="item">
                    {{ item.price?.formatted || '—' }}
                </template>
                <template #item-platform_label="item">
                    <span class="px-2 py-1 brds-2" :style="platformStyle(item.platform)">
                        {{ item.platform_label }}
                    </span>
                </template>
                <template #item-status="item">
                    <span class="px-2 py-1 brds-2 text-white" :style="{ backgroundColor: statusColor(item.status) }">
                        {{ item.status }}
                    </span>
                </template>
                <template #item-actions="item">
                    <button class="prim_btn py-1 px-3 brds-1" style="font-size: 12px;" @click="openDetail(item.id)">
                        View
                    </button>
                </template>
            </Vue3EasyDataTable>

            <p v-if="!pageLoading && items.length === 0" class="text-center mt-4 text-muted" style="font-size: 14px;">
                No app store orders yet. Records appear when users purchase via Apple or Google in the mobile app.
            </p>

            <div class="d-flex justify-content-between align-items-center mt-3" v-if="meta.total > 0">
                <p class="mb-0" style="font-size: 12px;">
                    Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} records)
                </p>
                <div>
                    <button
                        class="prim_btn py-1 px-3 brds-1 me-2"
                        style="font-size: 12px;"
                        :disabled="meta.current_page <= 1"
                        @click="loadRecords(meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        class="prim_btn py-1 px-3 brds-1"
                        style="font-size: 12px;"
                        :disabled="meta.current_page >= meta.last_page"
                        @click="loadRecords(meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div v-if="detailOpen" @click.self="detailOpen = false" class="my-popup-component">
            <div class="brds-2 position-relative text-start p-4" style="width: 70vw; max-height: 85vh; background-color: white; overflow-y: auto;">
                <button class="trans_btn float-end" @click="detailOpen = false" style="font-size: 25px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <h5 class="fw-bold mb-3">Subscription / Order Detail</h5>
                <div v-if="detail" class="row g-2" style="font-size: 13px;">
                    <div class="col-md-6">
                        <strong>Client:</strong>
                        <button
                            v-if="detail.user_id"
                            type="button"
                            class="client-link-btn ms-1"
                            @click="openClientProfile(detail.user_id, 'invoices')"
                        >
                            {{ detail.client_name }}
                        </button>
                        <span v-else>{{ detail.client_name }}</span>
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong>
                        <button
                            v-if="detail.user_id && detail.client_email && detail.client_email !== '—'"
                            type="button"
                            class="client-link-btn ms-1"
                            @click="openClientProfile(detail.user_id, 'invoices')"
                        >
                            {{ detail.client_email }}
                        </button>
                        <span v-else>{{ detail.client_email }}</span>
                    </div>
                    <div class="col-md-6"><strong>Amount:</strong> {{ detail.price?.formatted || '—' }}</div>
                    <div class="col-md-6"><strong>Platform:</strong> {{ detail.platform_label }}</div>
                    <div class="col-md-6"><strong>Product:</strong> {{ detail.product_label }} ({{ detail.product_id }})</div>
                    <div class="col-md-6"><strong>Order / Transaction ID:</strong> {{ detail.order_id || '—' }}</div>
                    <div class="col-md-6"><strong>Original transaction:</strong> {{ detail.original_transaction_id || '—' }}</div>
                    <div class="col-md-6"><strong>Status:</strong> {{ detail.status }}</div>
                    <div class="col-md-6"><strong>Base plan:</strong> {{ detail.base_plan_id || '—' }}</div>
                    <div class="col-md-6"><strong>Purchased:</strong> {{ detail.purchased_at || '—' }}</div>
                    <div class="col-md-6"><strong>Expires:</strong> {{ detail.expires_at || '—' }}</div>
                    <div class="col-md-6"><strong>Verified:</strong> {{ detail.verified_at || '—' }}</div>
                    <div class="col-12"><strong>Purchase token:</strong> <span style="word-break: break-all;">{{ detail.purchase_token || '—' }}</span></div>
                    <div class="col-12 mt-3">
                        <strong>Store response (raw)</strong>
                        <pre class="tsl brds-2 p-3 mt-2 mb-0" style="font-size: 11px; max-height: 280px; overflow: auto; white-space: pre-wrap;">{{ formattedRawPayload }}</pre>
                    </div>
                </div>
            </div>
        </div>

        <clientPopup
            v-if="clientPopupOpen"
            :idForDetails="selectedClientId"
            :logInDetails="logInProps"
            :initialTab="clientPopupTab"
            @close="closeClientProfile"
        />
    </div>
</template>

<script>
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import clientPopup from '../clients/clientPopup.vue';

export default {
    components: { Vue3EasyDataTable, Loader, Inform, clientPopup },
    props: {
        platformFilter: {
            type: String,
            default: 'all',
        },
        title: {
            type: String,
            default: 'App Store Orders',
        },
        showPlatformFilter: {
            type: Boolean,
            default: false,
        },
        logInProps: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token'),
                },
            },
            internalPlatform: 'all',
            statusFilter: '',
            search: '',
            summary: null,
            items: [],
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
            },
            headers: [
                { text: 'Client', value: 'client_name', sortable: true },
                { text: 'Email', value: 'client_email', sortable: true },
                { text: 'Store', value: 'platform_label', sortable: true },
                { text: 'Product', value: 'product_label', sortable: true },
                { text: 'Amount', value: 'amount', sortable: false },
                { text: 'Order ID', value: 'order_id', sortable: true },
                { text: 'Status', value: 'status', sortable: true },
                { text: 'Purchased', value: 'purchased_at', sortable: true },
                { text: 'Expires', value: 'expires_at', sortable: true },
                { text: 'Verified', value: 'verified_at', sortable: true },
                { text: '', value: 'actions' },
            ],
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            detailOpen: false,
            detail: null,
            clientPopupOpen: false,
            selectedClientId: null,
            clientPopupTab: 'summary',
        };
    },
    computed: {
        effectivePlatform() {
            return this.showPlatformFilter ? this.internalPlatform : this.platformFilter;
        },
        formattedRawPayload() {
            if (!this.detail || !this.detail.raw_payload) {
                return '—';
            }
            try {
                return JSON.stringify(this.detail.raw_payload, null, 2);
            } catch (e) {
                return String(this.detail.raw_payload);
            }
        },
    },
    watch: {
        platformFilter() {
            this.loadRecords(1);
        },
    },
    mounted() {
        this.loadSummary();
        this.loadRecords(1);
    },
    methods: {
        platformStyle(platform) {
            if (platform === 'ios') {
                return { backgroundColor: '#e8f0fe', color: '#1a56db' };
            }
            return { backgroundColor: '#e6f4ea', color: '#137333' };
        },
        statusColor(status) {
            if (status === 'active') return '#198754';
            if (status === 'expired') return '#dc3545';
            return '#6c757d';
        },
        loadSummary() {
            axios.get(config.baseApiUrl + 'store-subscriptions/summary', this.apiConfig)
                .then((res) => {
                    if (res.data.status) {
                        this.summary = res.data.data;
                    }
                })
                .catch(() => {});
        },
        loadRecords(page) {
            this.pageLoading = true;
            this.loaderText = 'Loading orders';
            const params = new URLSearchParams();
            params.set('page', page);
            params.set('per_page', this.meta.per_page);
            if (this.effectivePlatform === 'ios' || this.effectivePlatform === 'android') {
                params.set('platform', this.effectivePlatform);
            }
            if (this.statusFilter) {
                params.set('status', this.statusFilter);
            }
            if (this.search.trim()) {
                params.set('search', this.search.trim());
            }

            axios.get(config.baseApiUrl + 'store-subscriptions?' + params.toString(), this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.items = res.data.data;
                        this.meta = res.data.meta || this.meta;
                    } else {
                        this.showError(res.data.message || 'Could not load records');
                    }
                })
                .catch((er) => {
                    this.pageLoading = false;
                    const message = er.response?.data?.message || er.message || 'Could not load orders';
                    this.showError(message);
                });
        },
        openDetail(id) {
            this.pageLoading = true;
            this.loaderText = 'Loading detail';
            axios.get(config.baseApiUrl + 'store-subscriptions/' + id, this.apiConfig)
                .then((res) => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.detail = res.data.data;
                        this.detailOpen = true;
                    } else {
                        this.showError(res.data.message || 'Could not load detail');
                    }
                })
                .catch((er) => {
                    this.pageLoading = false;
                    this.showError(er.response?.data?.message || er.message);
                });
        },
        openClientProfile(userId, tab = 'invoices') {
            if (!userId) {
                return;
            }
            this.selectedClientId = userId;
            this.clientPopupTab = tab;
            this.clientPopupOpen = true;
        },
        closeClientProfile() {
            this.clientPopupOpen = false;
            this.selectedClientId = null;
        },
        showError(message) {
            this.modalTitle = 'Error';
            this.modalDetail = message;
            this.informModal = true;
        },
        acknowledged() {
            this.informModal = false;
        },
    },
};
</script>

<style scoped>
.client-link-btn {
    border: none;
    background: transparent;
    color: #f2a18c;
    padding: 0;
    font-size: inherit;
    text-align: left;
    cursor: pointer;
    text-decoration: underline;
}

.client-link-btn:hover {
    color: #d8846f;
}
</style>
