<template lang="">
    <div class="shd_card mt-4 position-relative">
        <p class="mb-0" style="font-size: 14px;">{{title}}</p>
        <div class="position-absolute" style="right:30px;top:20px;">
            <img src="/cms-assets/images/Overview/Ellipse22.png" style="height:14px;width:13px;float:left;">
            <p class="float-start mx-2" style="font-size:11px;">{{sub}}</p>
        </div>
        <div style="display: flex;">
        <h1 class="mb-0" style="margin-right: 10px; font-size: 43px;"><strong>{{data.changeRatio}}</strong></h1>
            <svg v-if="data.change!=='no'" :class="{rotate:data.change==='dec'}" xmlns="http://www.w3.org/2000/svg" width="33.034" height="39.335" viewBox="0 0 33.034 39.335">
  <g id="Group_55971" data-name="Group 55971" transform="translate(-116.037 -38.035)">
    <path id="Shape" d="M0,35.335V0L14.175,14.18" transform="translate(132.067 40.035)" fill="none" stroke="#f2a18c" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="4"/>
    <path id="Shape-2" data-name="Shape" d="M0,14.18,14.175,0,0,14.18Z" transform="translate(118.037 40.035)" fill="none" stroke="#f2a18c" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="4"/>
  </g>
</svg>
        </div>
        <LineChart v-if="labels!==null&&datasets!==null" :label="labels" :dataset="datasets"/>
    </div>
</template>
<script>
import LineChart from './line-chart.vue';
export default {
    props: ['title', 'sub', 'data'],
    name: 'App',
    components: {
        LineChart
    },
    data() {
        return {
            labels: null,
            datasets: null,
        }
    },
    mounted() {
        this.labels = this.data.labels.reverse();
        this.datasets = this.data.dataset.reverse();
    }
}
</script>
<style scoped>
.rotate {
    transform: rotate(180deg);
}
</style>
