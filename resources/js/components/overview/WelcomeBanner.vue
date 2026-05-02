<template lang="">
    <div class="welcome-banner mb-4">
        <div class="welcome-content">
            <h2 class="welcome-title">Welcome back, {{ userName }}</h2>
            <p class="welcome-quote" v-if="quote">{{ quote }}</p>
        </div>
    </div>
</template>
<script>
export default {
    name: 'WelcomeBanner',
    props: {
        userName: {
            type: String,
            default: 'User'
        },
        quote: {
            type: String,
            default: ''
        }
    }
}
</script>
<style scoped>
.welcome-banner {
    background: linear-gradient(135deg, #8B1538 0%, #A52D55 100%);
    border-radius: 15px;
    padding: 40px 30px;
    color: white;
    box-shadow: 0 4px 12px rgba(139, 21, 56, 0.3);
}

.welcome-content {
    max-width: 100%;
}

.welcome-title {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 10px;
    color: white;
}

.welcome-quote {
    font-size: 16px;
    font-style: italic;
    opacity: 0.95;
    margin-top: 10px;
    line-height: 1.6;
}

@media screen and (max-width: 768px) {
    .welcome-banner {
        padding: 30px 20px;
    }
    
    .welcome-title {
        font-size: 24px;
    }
    
    .welcome-quote {
        font-size: 14px;
    }
}
</style>
