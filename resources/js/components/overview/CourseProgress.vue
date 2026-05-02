<template lang="">
    <div class="course-progress-card shd_card">
        <div class="row align-items-center">
            <div class="col-md-3 col-12 mb-md-0 mb-3">
                <div class="course-thumbnail" v-if="courseImage">
                    <img :src="courseImage" alt="Course" class="thumbnail-img">
                    <div class="play-overlay">
                        <i class="fa-solid fa-play"></i>
                    </div>
                </div>
                <div class="course-thumbnail placeholder" v-else>
                    <i class="fa-solid fa-video"></i>
                </div>
            </div>
            <div class="col-md-6 col-12 mb-md-0 mb-3">
                <div class="course-info">
                    <span class="badge-in-progress" v-if="inProgress">In Progress</span>
                    <h4 class="course-title">{{ courseTitle || 'No active course' }}</h4>
                    <p class="next-lesson" v-if="nextLesson">Next: {{ nextLesson }}</p>
                    <button class="btn-continue" @click="continueLearning">
                        Continue Learning
                    </button>
                </div>
            </div>
            <div class="col-md-3 col-12">
                <div class="progress-section">
                    <div class="progress-percentage">{{ progressPercentage }}%</div>
                    <div class="progress-bar-container">
                        <div class="progress-bar" :style="{ width: progressPercentage + '%' }"></div>
                    </div>
                    <p class="progress-label">Complete</p>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'CourseProgress',
    props: {
        courseImage: {
            type: String,
            default: ''
        },
        courseTitle: {
            type: String,
            default: ''
        },
        nextLesson: {
            type: String,
            default: ''
        },
        progressPercentage: {
            type: Number,
            default: 0
        },
        inProgress: {
            type: Boolean,
            default: true
        }
    },
    methods: {
        continueLearning() {
            this.$emit('continue-learning');
        }
    }
}
</script>
<style scoped>
.course-progress-card {
    border-top: 4px solid #8B1538;
    padding: 25px;
    margin-bottom: 20px;
}

.course-thumbnail {
    position: relative;
    width: 100%;
    padding-top: 56.25%; /* 16:9 aspect ratio */
    background: #f5f5f5;
    border-radius: 10px;
    overflow: hidden;
}

.course-thumbnail.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e0e0e0;
}

.course-thumbnail.placeholder i {
    font-size: 48px;
    color: #999;
}

.thumbnail-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(139, 21, 56, 0.8);
    border-radius: 50%;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.play-overlay:hover {
    background: rgba(139, 21, 56, 1);
    transform: translate(-50%, -50%) scale(1.1);
}

.play-overlay i {
    color: white;
    font-size: 20px;
    margin-left: 3px;
}

.course-info {
    padding-left: 20px;
}

.badge-in-progress {
    display: inline-block;
    background: #8B1538;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.course-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
}

.next-lesson {
    font-size: 14px;
    color: #666;
    margin-bottom: 15px;
}

.btn-continue {
    background: linear-gradient(135deg, #8B1538 0%, #A52D55 100%);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-continue:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 21, 56, 0.3);
}

.progress-section {
    text-align: center;
}

.progress-percentage {
    font-size: 32px;
    font-weight: bold;
    color: #8B1538;
    margin-bottom: 10px;
}

.progress-bar-container {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #8B1538 0%, #A52D55 100%);
    transition: width 0.3s ease;
}

.progress-label {
    font-size: 12px;
    color: #666;
    margin: 0;
}

@media screen and (max-width: 768px) {
    .course-info {
        padding-left: 0;
        margin-top: 15px;
    }
    
    .course-title {
        font-size: 18px;
    }
    
    .progress-percentage {
        font-size: 24px;
    }
}
</style>
