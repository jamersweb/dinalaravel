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
                <button @click="checking(item.id)" style="border:none;background-color:transparent;">{{item.full_name}}</button>
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
                { text: "About Nutrition Compliance/Week", value: "full_name", sortable: true },
                { text: "Total Calories", value: "total_calories", sortable: true },
                { text: "Total Carbs", value: "total_carbs", sortable: true },
                { text: "Total Fats", value: "total_fats", sortable: true },
                { text: "Total Proteins", value: "total_proteins", sortable: true },
                { text: "Total Meals", value: "total_meals", sortable: true },
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
