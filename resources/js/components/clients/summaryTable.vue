<template lang="">
    <div>
        <Vue3EasyDataTable
            :headers="headers"
            :items="userData"
            :search-field="searchField"
            :search-value="searchValue"
            v-model:items-selected="itemsSelected"
            >
            <template #item-full_name="item">
                <img v-if="item.image!=''" :src="item.image" alt="" style="height:30px;width:30px;border-radius:15px;">
                <img v-else src="/cms-assets/images/navbar-topbar/user.jpg" alt="" style="height:30px;width:30px;border-radius:15px;">
                <button @click="checking(item.id)" style="border:none;background-color:transparent;color:#343434">{{item.full_name}}</button>
            </template>
            <template #item-current_phase="item">
                <p style="margin-top:5px;margin-bottom:0px;margin-left:10px;">{{item.current_phase}}</p>
                <div v-if="item.progress!=null" class="position-relative mb-2" style="width:87px;height:16px;background-color:#F7F7F7;border-radius:10px;border:1px solid #F7F7F7">
                    <div v-if="item.progress!=null" :style="{width:item.progress+'%'}" style="height:100%;border-radius:10px;text-align:center;background-color:#F2A18C;position:absolute;top:0;left: 0;"></div>
                    <p style="position: absolute;left:43%;top:-2px;">{{Math.round(item.progress)}}%</p>
                </div>
            </template>
            <template #item-next_phase="item">
                <p style="margin-top:5px;margin-bottom:0px;margin-left:10px;">{{item.next_phase}}</p>
            </template>
            <template #item-program_status="item">
                <span v-if="item.program_status==='subscribed'" class="badge bg-secondary">{{item.program_status}}</span>
                <span v-else-if="item.program_status==='in-progress' || item.program_status==='resumed'" class="badge bg-theme">{{item.program_status}}</span>
                <span v-else-if="item.program_status==='paused'" class="badge bg-primary">{{item.program_status}}</span>
                <span v-else-if="item.program_status==='completed'" class="badge bg-success">{{item.program_status}}</span>
                
            </template> 
        </Vue3EasyDataTable>
        <p style="display:none;">{{selectedClientsIds}}</p>
    </div>
</template>
<script>
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
export default {
    components: { Vue3EasyDataTable },
    props: ['userData', 'searchValue'],
    data() {
        return {
            searchField: ref("full_name"),
            headers: [
                { text: "Name", value: "full_name", sortable: true },
                { text: "Main Program", value: "main_program", sortable: true },
                { text: "Program Status", value: "program_status", sortable: true },
                { text: "Current Phase", value: "current_phase", sortable: true },
                { text: "Next Phase", value: "next_phase", sortable: true },
            ],
            items: [],
            itemsSelected: [],
        }
    },
    computed: {
        selectedClientsIds() {
            let clientIds = [];
            for (let index = 0; index < this.itemsSelected.length; index++) {
                clientIds.push(this.itemsSelected[index].id);
            }
            this.$parent.getClientIds(clientIds);
            return clientIds;
        }
    },
    methods: {
        removeSelected() {
            this.itemsSelected = [];
        },
        checking(m) {
            this.$parent.ClientPopup(m);
        }

    }
}
</script>
<style scoped>
.badge{
    font-size: 11px !important;
}
.bg-theme{
    background-color: rgb(242, 161, 140) !important;
}
</style>
