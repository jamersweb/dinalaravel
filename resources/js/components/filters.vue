<template lang="">
    <div class="my-popup-component justify-content-end" style="z-index:9999" @click.self="closeFilters()">
        <div class="bg-white brds-5 py-4 px-2 position-relative me-3" style="width:300px;height:95%">
            <button @click="closeFilters()" class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="h-10 text-center">
                <h5 class="m-0">Tags Filters</h5>
            </div>
            <div class="h-70 overflow-auto">
                <div class="catBtn mb-2" v-for="(tagCat,index) in tags" :key="index">
                    <div class="pointer bghv py-1 px-2" style="color:#F2A18C" @click="expandToggle(index)">
                        {{tagCat.tagType==null?'Uncategorized':tagCat.tagType.substring(0,15)}}
                        <i class="mt-1 float-end fa-solid fa-chevron-down" :class="{'inverted' : expanded[index]}" style="color:black;"></i>
                    </div>
                    <div v-show="expanded[index]" class="py-1 px-3">
                        <div class="my-1 text-start" v-for="(tag,index2) in tagCat.tagList" :key="index2">
                            <input type="checkbox" :id="index+'tgChk'+index2" class="form-check-input pointer" :value="tag.id" v-model="selectedTags">
                            <label :for="index+'tgChk'+index2" class="wb-all ms-2 h7 pointer">{{tag.name.substring(0,15)}}</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="h-20 d-flex flex-column justify-content-center align-items-center">
                <button class="brds-2 prim_btn w-50 py-2 my-1" @click="applyFilters()">Apply</button>
                <button class="brds-2 scnd_btn w-50 py-2 my-1" @click="resetFilters()">Reset</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props : ['tags','prefillTags'],
    data() {
        return {
            selectedTags : [],
            expanded: []
        }
    },
    mounted() {
        this.initializer();
        this.selectedTags = this.prefillTags;
    },
    methods: {
        applyFilters(){
            this.$parent.applyFilters(this.selectedTags);
            this.$parent.filters = false;
        },
        resetFilters(){
            this.selectedTags = [];
            this.$parent.clearFilters();
            this.$parent.filters = false;
        },
        closeFilters(){
            this.$parent.filters = false;
        },
        expandToggle(index){
            this.expanded[index] = !this.expanded[index];
            for (let i = 0; i < this.expanded.length; i++) {
                if(i!==index)
                this.expanded[i] = false;
            }
        },
        initializer(){
            for (let i = 0; i < this.tags.length; i++) {
                this.expanded.push(false);
            }
        }
    }
}
</script>
<style scoped>
    .h-10{
        height: 10%;
    }
    .h-20{
        height: 20%;
    }
    .h-70{
        height: 70%;
    }
    .catBtn {
        width: 85%;
        text-align: center;
        border: 1px solid #B1B0B0;
        background-color: white;
        position: relative;
        margin: auto;
    }
    .bghv:hover{
        background-color: rgb(240, 240, 240);
    }
    .inverted {
        transform: rotate(180deg);
    }
</style>