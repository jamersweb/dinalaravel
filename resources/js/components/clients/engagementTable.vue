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
                <div style="height:30px;width:30px;float:left;">
                    <img v-if="item.image!=''" :src="item.image" alt="" style="height:30px;width:30px;border-radius:15px;">
                    <img v-else src="/cms-assets/images/navbar-topbar/user.jpg" alt="" style="height:30px;width:30px;border-radius:15px;">
                </div>
                <div style="float:left;">
                    <button @click="checking(item.id)" style="border:none;background-color:transparent;">{{item.full_name}}</button>
                    <p style="color:#F2A18C;font-size:8px;margin-bottom:0px;margin-left:5px;">{{item.since}}</p>
                </div>
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
                { text: "Number of Sign-Ins / Week", value: "no_of_signins", sortable: true },
                { text: "Number of Workouts / Week", value: "no_of_workouts", sortable: true },
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

</style>
