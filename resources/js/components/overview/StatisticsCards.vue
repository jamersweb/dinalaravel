<template lang="">
    <div class="statistics-cards mb-4">
        <div class="row g-3">
            <div class="col-md-6 col-lg-3" v-for="(stat, index) in statistics" :key="index">
                <StatCard
                    :label="stat.label"
                    :value="stat.value"
                    :subtext="stat.subtext"
                    :icon="stat.icon"
                    :variant="stat.variant"
                />
            </div>
        </div>
    </div>
</template>
<script>
import StatCard from './StatCard.vue';

export default {
    name: 'StatisticsCards',
    components: {
        StatCard
    },
    props: {
        statistics: {
            type: Array,
            default: () => [
                {
                    label: 'WATCHED',
                    value: 0,
                    subtext: 'of 0 lessons',
                    icon: 'fa-solid fa-check-circle',
                    variant: 'maroon'
                },
                {
                    label: 'REMAINING',
                    value: 0,
                    subtext: 'lessons to go',
                    icon: 'fa-solid fa-book-open',
                    variant: 'white'
                },
                {
                    label: 'WATCH TIME',
                    value: '0s',
                    subtext: 'total watched',
                    icon: 'fa-solid fa-clock',
                    variant: 'gold'
                },
                {
                    label: 'STREAK',
                    value: 0,
                    subtext: 'days in a row',
                    icon: 'fa-solid fa-fire',
                    variant: 'maroon'
                }
            ]
        }
    }
}
</script>
<style scoped>
.statistics-cards {
    margin-bottom: 30px;
}

@media screen and (max-width: 768px) {
    .statistics-cards .row {
        margin: 0;
    }
    
    .statistics-cards .col-md-6 {
        margin-bottom: 15px;
    }
}
</style>
