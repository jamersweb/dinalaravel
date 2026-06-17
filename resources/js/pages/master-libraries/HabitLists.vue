<template>
    <div class="habit-lists-page">
        <Loader v-if="pageLoading" :loadingText="loaderText" />
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />

        <div class="page-header">
            <div>
                <h3>Habit Lists</h3>
                <p>Create reusable habit lists, then assign them to clients after reviewing consultation forms.</p>
            </div>
            <button class="primary-btn" @click="newHabitList">New Habit List</button>
        </div>

        <div class="content-grid">
            <div class="list-panel">
                <p v-if="habitLists.length === 0" class="empty-text">No habit lists created yet.</p>
                <div
                    v-for="list in habitLists"
                    :key="list.id"
                    class="habit-list-row"
                    :class="{ active: selectedList && selectedList.id === list.id }"
                    @click="selectHabitList(list)"
                >
                    <div>
                        <h5>{{ list.name }}</h5>
                        <p>{{ list.total_items || (list.items ? list.items.length : 0) }} habits</p>
                    </div>
                    <span>{{ list.total_assignments || 0 }} assigned</span>
                </div>
            </div>

            <div class="editor-panel">
                <h4>{{ form.id ? 'Edit Habit List' : 'Create Habit List' }}</h4>
                <label>Name</label>
                <input v-model="form.name" class="form-control" placeholder="Daily fundamentals">

                <label>Description</label>
                <textarea v-model="form.description" class="form-control" rows="3" placeholder="Optional note for this list"></textarea>

                <div class="items-header">
                    <label>Habits</label>
                    <button class="secondary-btn" @click="addItem">Add Habit</button>
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="item-row">
                    <input v-model="item.habit_name" class="form-control" :placeholder="'Habit ' + (index + 1)">
                    <button class="icon-btn" @click="removeItem(index)">Remove</button>
                </div>

                <div class="actions">
                    <button class="primary-btn" @click="saveHabitList">{{ form.id ? 'Update' : 'Create' }}</button>
                    <button class="secondary-btn" @click="resetForm">Clear</button>
                    <button v-if="form.id" class="danger-btn" @click="deleteHabitList">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import config from '../../config';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';

export default {
    name: 'HabitLists',
    components: { Loader, Inform },
    data() {
        return {
            apiConfig: {
                headers: {
                    Authorization: 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            habitLists: [],
            selectedList: null,
            form: {
                id: null,
                name: '',
                description: '',
                items: [{ habit_name: '' }]
            }
        };
    },
    mounted() {
        this.getHabitLists();
    },
    methods: {
        getHabitLists() {
            this.pageLoading = true;
            this.loaderText = 'Loading habit lists';
            axios.get(config.baseApiUrl + 'get-all-habit-lists', this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.habitLists = res.data.data || [];
                    } else {
                        this.showMessage('Error!', res.data.message || 'Unable to load habit lists');
                    }
                })
                .catch(err => {
                    this.pageLoading = false;
                    this.showMessage('Error!', err.message || 'Unable to load habit lists');
                });
        },
        selectHabitList(list) {
            this.selectedList = list;
            this.form = {
                id: list.id,
                name: list.name || '',
                description: list.description || '',
                items: (list.items || []).map(item => ({
                    id: item.id,
                    habit_name: item.habit_name || ''
                }))
            };
            if (this.form.items.length === 0) {
                this.addItem();
            }
        },
        newHabitList() {
            this.selectedList = null;
            this.resetForm();
        },
        addItem() {
            this.form.items.push({ habit_name: '' });
        },
        removeItem(index) {
            if (this.form.items.length === 1) {
                this.form.items[0].habit_name = '';
                return;
            }
            this.form.items.splice(index, 1);
        },
        saveHabitList() {
            const payload = {
                name: this.form.name.trim(),
                description: this.form.description,
                items: this.form.items
                    .filter(item => item.habit_name && item.habit_name.trim() !== '')
                    .map(item => ({
                        id: item.id,
                        habit_name: item.habit_name.trim()
                    }))
            };

            if (!payload.name) {
                this.showMessage('Error!', 'Please enter a habit list name');
                return;
            }
            if (payload.items.length === 0) {
                this.showMessage('Error!', 'Please add at least one habit');
                return;
            }

            const url = this.form.id
                ? config.baseApiUrl + 'update-habit-list/' + this.form.id
                : config.baseApiUrl + 'create-habit-list';

            this.pageLoading = true;
            this.loaderText = this.form.id ? 'Updating habit list' : 'Creating habit list';
            axios.post(url, payload, this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.showMessage('Done!', res.data.message || 'Habit list saved');
                        this.resetForm();
                        this.getHabitLists();
                    } else {
                        this.showMessage('Error!', res.data.message || 'Unable to save habit list');
                    }
                })
                .catch(err => {
                    this.pageLoading = false;
                    this.showMessage('Error!', err.message || 'Unable to save habit list');
                });
        },
        deleteHabitList() {
            if (!this.form.id || !window.confirm('Delete this habit list? This also removes assignments.')) {
                return;
            }
            this.pageLoading = true;
            this.loaderText = 'Deleting habit list';
            axios.get(config.baseApiUrl + 'delete-habit-list/' + this.form.id, this.apiConfig)
                .then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.showMessage('Done!', res.data.message || 'Habit list deleted');
                        this.resetForm();
                        this.getHabitLists();
                    } else {
                        this.showMessage('Error!', res.data.message || 'Unable to delete habit list');
                    }
                })
                .catch(err => {
                    this.pageLoading = false;
                    this.showMessage('Error!', err.message || 'Unable to delete habit list');
                });
        },
        resetForm() {
            this.form = {
                id: null,
                name: '',
                description: '',
                items: [{ habit_name: '' }]
            };
            this.selectedList = null;
        },
        showMessage(title, detail) {
            this.modalTitle = title;
            this.modalDetail = detail;
            this.informModal = true;
        },
        acknowledged() {
            this.informModal = false;
        }
    }
};
</script>

<style scoped>
.habit-lists-page {
    height: calc(100vh - 125px);
    overflow: auto;
    border: 1px solid #E7E7E7;
    border-radius: 13px;
    background: #fff;
    padding: 22px;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.page-header h3 {
    margin: 0;
    font-size: 28px;
}

.page-header p,
.empty-text,
.habit-list-row p {
    margin: 0;
    color: #777;
    font-size: 13px;
}

.content-grid {
    display: grid;
    grid-template-columns: minmax(260px, 34%) 1fr;
    gap: 18px;
}

.list-panel,
.editor-panel {
    border: 1px solid #E7E7E7;
    border-radius: 10px;
    padding: 16px;
    min-height: 420px;
}

.habit-list-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid #EDEDED;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    cursor: pointer;
}

.habit-list-row.active,
.habit-list-row:hover {
    background: #F8E5DF;
    border-color: #F2A18C;
}

.habit-list-row h5 {
    margin: 0 0 4px;
    font-size: 16px;
}

.habit-list-row span {
    color: #F2A18C;
    font-size: 12px;
    white-space: nowrap;
}

.editor-panel h4 {
    margin-bottom: 16px;
}

.editor-panel label,
.items-header label {
    display: block;
    font-weight: 600;
    margin: 12px 0 6px;
}

.items-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 8px;
}

.item-row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
}

.primary-btn,
.secondary-btn,
.danger-btn,
.icon-btn {
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    font-size: 13px;
}

.primary-btn {
    background: #F2A18C;
    color: #111;
}

.secondary-btn,
.icon-btn {
    background: #EEEEEE;
    color: #111;
}

.danger-btn {
    background: #cc3d3d;
    color: #fff;
}

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>
