<template lang="">
    <div class="stat-card" :class="cardClass">
        <div class="stat-icon" v-if="icon">
            <i :class="icon"></i>
        </div>
        <div class="stat-content">
            <p class="stat-label">{{ label }}</p>
            <h1 class="stat-value">{{ value }}</h1>
            <p class="stat-subtext">{{ subtext }}</p>
        </div>
    </div>
</template>
<script>
export default {
    name: 'StatCard',
    props: {
        label: {
            type: String,
            required: true
        },
        value: {
            type: [String, Number],
            required: true
        },
        subtext: {
            type: String,
            default: ''
        },
        icon: {
            type: String,
            default: ''
        },
        variant: {
            type: String,
            default: 'maroon', // 'maroon', 'white', 'gold'
            validator: (value) => ['maroon', 'white', 'gold'].includes(value)
        }
    },
    computed: {
        cardClass() {
            return `stat-card-${this.variant}`;
        }
    }
}
</script>
<style scoped>
.stat-card {
    border-radius: 15px;
    padding: 25px 20px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    min-height: 150px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stat-card-maroon {
    background: linear-gradient(135deg, #8B1538 0%, #A52D55 100%);
    color: white;
}

.stat-card-white {
    background: white;
    color: #333;
    border: 1px solid #e0e0e0;
}

.stat-card-gold {
    background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%);
    color: #333;
}

.stat-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    opacity: 0.2;
    font-size: 60px;
}

.stat-card-maroon .stat-icon,
.stat-card-gold .stat-icon {
    opacity: 0.15;
}

.stat-content {
    position: relative;
    z-index: 1;
}

.stat-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
    opacity: 0.9;
}

.stat-card-white .stat-label {
    color: #666;
}

.stat-value {
    font-size: 42px;
    font-weight: bold;
    margin: 0;
    line-height: 1;
    margin-bottom: 8px;
}

.stat-card-maroon .stat-value,
.stat-card-white .stat-value {
    color: inherit;
}

.stat-card-gold .stat-value {
    color: #333;
}

.stat-subtext {
    font-size: 13px;
    margin: 0;
    opacity: 0.8;
}

.stat-card-white .stat-subtext {
    color: #666;
}

@media screen and (max-width: 768px) {
    .stat-card {
        min-height: 120px;
        padding: 20px 15px;
    }
    
    .stat-value {
        font-size: 32px;
    }
    
    .stat-icon {
        font-size: 45px;
    }
}
</style>
