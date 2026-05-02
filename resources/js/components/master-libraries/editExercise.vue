<template lang="">
    <div class="my-popup-component">
        <Loader v-if="pageLoading" :loadingText="loaderText"/>
        <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
        <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
        <listTags v-if="tagsComp" :prefilledtags="postData.tags"/>
        <div class="main-box w-80 p-2 p-md-4 position-relative" style="overflow-y:auto">
            <button class="trans_btn position-absolute" @click="quitComponent()"
                style="right:25px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h4 class="m-0 text-center fw-bold">View/Edit Exercise</h4>
            <div class="w-100 container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-6 px-2">
                        <div class="shd_card drag-drop-area w-100 p-2 mt-2" style="height:65%">
                            <div v-if="postData.video_type=='youtube'" class="w-100 h-100 d-flex justify-content-center align-items-center">
                                <iframe v-if="media.selected" :src="media.url" controls class="w-100 h-100 brds-2"></iframe>
                                <h3 v-else>please provide the video link below</h3>
                            </div>
                            <div v-if="postData.video_type=='custom'" class="w-100 h-100 d-flex justify-content-center align-items-center">
                                <video v-if="media.selected" :src="media.url" controls class="w-100 h-100 brds-2" 
                                ref="videoPlayer" @loadedmetadata="getDuration()"></video>
                                <h3 v-else>please upload the video</h3>
                            </div>
                            <div v-if="postData.video_type=='image'" class="w-100 h-100 d-flex justify-content-center align-items-center">
                                <img v-if="media.selected" :src="media.url" controls class="w-100 h-100 brds-2">
                                <h3 v-else>please upload the image</h3>
                            </div>
                        </div>
                        <div class="mt-2" > 
                            <input v-if="postData.video_type=='youtube'" v-model="youtubeUrlInput" @input="getYoutubeVideo()" type="text" class="form-control" placeholder="YouTube link (watch, embed, Shorts, youtu.be…)">
                            <div v-if="postData.video_type=='youtube'" class="text-center cdzx brds-1 position-relative py-2 mt-2">
                                <input ref="youtubeCustomThumbnail" @change="onYoutubeCustomThumbnail()" type="file" style="left:0;top:0"
                                accept="image/jpeg,image/png,image/jpg,image/webp" class="w-100 h-100 transparent position-absolute pointer">
                                <span class="h7">Custom thumbnail (optional — overrides YouTube)</span>
                            </div>
                            <p v-if="postData.video_type=='youtube'" class="small text-muted mt-1 mb-0 px-1" style="font-size:11px;">Shorts links are supported. Thumbnail tip: 16:9 (1280×720) matches most cards; 9:16 for vertical.</p>
                            <div v-if="postData.video_type=='custom'" class="text-center cdzx brds-1 position-relative py-2">
                                <input ref="selectedVideo" @change="getVideo()" type="file" style="left:0;top:0"
                                accept="video/mp4,video/mkv" class="w-100 h-100 transparent position-absolute pointer">
                                <span>Browse Video</span>
                            </div>
                            <div v-if="postData.video_type=='custom'" class="text-center cdzx brds-1 position-relative py-2 mt-2">
                                <input ref="customVideoThumbnail" @change="onCustomVideoThumbnail()" type="file" style="left:0;top:0"
                                accept="image/jpeg,image/png,image/jpg,image/webp" class="w-100 h-100 transparent position-absolute pointer">
                                <span class="h7">Thumbnail image (optional — else a frame from the video)</span>
                            </div>
                            <p v-if="postData.video_type=='custom'" class="small text-muted mt-1 mb-0 px-1" style="font-size:11px;">Tip: 16:9 (e.g. 1280×720) matches most exercise cards; 9:16 works for vertical/Shorts-style. The app uses contain/fit so a little letterboxing is normal.</p>
                            <div v-if="postData.video_type=='image'" class="text-center cdzx brds-1 position-relative py-2">
                                <input ref="selectedImage" @change="getImage()" type="file" style="left:0;top:0"
                                accept="image/jpg,image/png,image/jpeg" class="w-100 h-100 transparent position-absolute pointer">
                                <span>Browse Image</span>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-center mt-2 d-flex">
                                <span class="me-3 fw-bold mt-2 w-30">Media Type: </span>
                                <select @change="changeMediaType()" class="form-control" v-model="postData.video_type">
                                    <option value="youtube">Youtube Video</option>
                                    <option value="custom">Custom Video</option>
                                    <option value="image">Image</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-center mt-3 d-flex">
                                <span class="me-3 fw-bold mt-2 w-30">Exercise Type: </span>
                                <select class="form-control" v-model="postData.type" @change="error=null">
                                    <option value="--select--" disabled selected>--select--</option>
                                    <option value="Strength-weps and weights">Strength-reps and weights</option>
                                    <option value="Endurance">Endurance</option>
                                    <option value="Cardio">Cardio</option>
                                    <option value="Timed(longer better ex planks)">Timed(longer better ex planks)</option>
                                    <option value="Timed(faster better ex sprints)">Timed(faster better ex sprints)</option>
                                    <option value="HIIT">HIIT</option>
                                    <option value="Times strength seconds and weights">Times strength seconds and weights</option>
                                    <option value="Warm up before workout">Warm up before workout</option>
                                    <option value="Stretches">Stretches</option>
                                    <option value="Mobility">Mobility</option>
                                    <option value="Topic (nutrition & fitness)">Topic (nutrition & fitness)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-center mt-3 d-flex">
                                <span class="me-3 fw-bold mt-2 w-30">Language: </span>
                                <select @change="removeAlternates()" class="form-control" v-model="postData.language" style="white-space: nowrap;">
                                    <option value="en">English</option>
                                    <option value="ar">Arabic</option>
                                    <option value="no">No Audio</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 px-2">
                        <div class="">
                            <p class="fw-bold mt-2 mb-1">Exercise code <span class="text-muted small fw-normal">(optional, unique)</span></p>
                            <input @input="this.error = null" class="form-control" type="text" v-model="postData.content_code" placeholder="e.g. EX-001" maxlength="64" autocomplete="off">
                        </div>
                        <div class="mt-3">
                            <p class="fw-bold mt-2 mb-1">Title: </p>
                            <input @input="this.error = null" class="form-control" type="text" v-model="postData.title" placeholder="type exercise title">
                        </div>
                        <div class="mt-3">
                            <p class="fw-bold mt-2 mb-1">Tags: </p>
                            <div class="">
                                <div class="brds-1 border w-100 p-2" @click="toggleTagsComponent" style="cursor:pointer;">
                                    <span v-if="postData.tags.length==0" class="h7 text-muted">click to select or unselect</span>
                                    <span v-else class="d-flex flex-wrap">
                                        <span v-for="tagN in postData.tagNames" class="tag-box">{{tagN}}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="fw-bold mt-2 mb-1">Alternate Exercises: </p>
                            <div class="">
                                <div class="brds-1 border w-100 p-2" @click="toggleExerComponent" style="cursor:pointer;">
                                    <span v-if="postData.alterNames.length==0" class="h7 text-muted">click to select or unselect</span>
                                    <span v-else class="d-flex flex-wrap">
                                        <span v-for="exN in postData.alterNames" class="tag-box">{{exN}}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <p class="fw-bold mt-2 mb-1">Instructions: </p>
                            <textarea v-model="postData.instructions" @change="error=null" class="form-control" style="resize:none" 
                            rows="2" placeholder="type instructions for exercise:"></textarea>
                        </div>
                        <div class="mt-3">
                            <p class="fw-bold mt-2 mb-1">Weights: </p>
                            <textarea v-model="postData.weights" class="form-control" style="resize:none" 
                            rows="2" placeholder="type weights for exercise:"></textarea>
                        </div>
                        <div v-if="error!=null" class="mt-2">
                            <span class="text-danger text-center">*{{error}}</span>
                        </div>
                        <div class="mt-3">
                            <div class="text-center">
                                <button @click="checkForErrors()" class="prim_btn_rnd_lg prim_btn w-100">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <assignAlternateExercise v-if="selectExerOn" :prefill="postData.alternates" :language="postData.language"/>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import { extractYoutubeVideoId } from '../../helpers/youtube';
import listTags from './listTags.vue';
import Loader from '../../components/loader.vue';
import Inform from '../../components/inform.vue';
import Confirm from '../../components/confirm.vue';
import assignAlternateExercise from './assignAlternateExercise.vue';
export default {
    props : ['exerciseData'],
    components: { listTags, Loader, Inform, Confirm, assignAlternateExercise },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            media : {
                selected : false,
                url : ''
            },
            postData: {
                id : null,
                title: '',
                type: '--select--',
                video: null,
                video_type: 'youtube',
                tags: [],
                tagNames: [],
                alternates: [],
                alterNames: [],
                instructions: '',
                video_duration: 0,
                language: 'en',
                weights: '',
                content_code: '',
                image: null,
                customThumbnail: null,
                manualVideoThumbnail: null
            },
            youtubeUrlInput: '',
            error : null,
            pageLoading: false,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            loaderText: '',
            tagsComp: false,
            selectExerOn: false
        }
    },
    mounted(){
        const tempObj = JSON.parse(JSON.stringify(this.exerciseData));
        this.postData.id = tempObj.id;
        this.postData.content_code = tempObj.content_code || '';
        this.postData.title = tempObj.title;
        this.postData.type = tempObj.type;
        this.postData.language = tempObj.language;
        this.postData.tags = tempObj.tags;
        this.postData.tagNames = tempObj.tagNames;
        this.postData.instructions = tempObj.instructions;
        this.postData.weights = tempObj.weights;
        this.postData.video_type = tempObj.video_type;
        this.postData.alternates = tempObj.alternates;
        this.postData.alterNames = tempObj.altNames;
        this.postData.video_duration = tempObj.video_duration;
        this.media.selected = true;
        this.media.url = tempObj.video_url;
        if(tempObj.video_type=='youtube'){
            this.youtubeUrlInput = tempObj.video_url;
            this.postData.video = extractYoutubeVideoId(tempObj.video_url) || null;
        }
    },
    methods: {
        removeAlternates() {
            this.postData.alternates = [];
            this.postData.alterNames = [];
        },
        assignAlternativeExercises(m, n) {
            this.postData.alternates = m;
            this.postData.alterNames = n;
            this.selectExerOn = false;
        },
        toggleExerComponent() {
            this.selectExerOn = !this.selectExerOn;
        },
        getYoutubeVideo() {
            if(this.youtubeUrlInput.trim()=='')
            return;
            this.error = null;
            const youtubeVideoId = extractYoutubeVideoId(this.youtubeUrlInput);
            if (youtubeVideoId && youtubeVideoId.length === 11) {
                this.pageLoading = true;
                this.loaderText = 'Getting video details';
                this.media.selected = true;
                this.media.url = "https://www.youtube.com/embed/" + youtubeVideoId;
                this.postData.video = youtubeVideoId;
                let link = "https://www.googleapis.com/youtube/v3/videos?id=" + youtubeVideoId + "&key=" + config.youtubeApiKey + "&part=snippet,contentDetails";
                axios.get(link).then(res => {
                    if (!res.data.items || !res.data.items[0] || !res.data.items[0].contentDetails) {
                        this.postData.video_duration = 0;
                        this.pageLoading = false;
                        return;
                    }
                    var hms = res.data.items[0].contentDetails.duration;
                    if (hms.includes("M")) {
                        var mnt = hms.substring(
                            hms.indexOf("T") + 1,
                            hms.lastIndexOf("M")
                        );
                        var sec = hms.substring(
                            hms.indexOf("M") + 1,
                            hms.lastIndexOf("S")
                        );
                        sec = parseInt(sec);
                        var seconds = mnt * 60;
                        seconds = seconds + sec;
                        this.postData.video_duration = seconds;
                    }
                    else {
                        var sec = hms.substring(
                            hms.indexOf("T") + 1,
                            hms.lastIndexOf("S")
                        );
                        sec = parseInt(sec);
                        this.postData.video_duration = sec;
                    }
                    this.pageLoading = false;
                    console.log("got duration: ",this.postData.video_duration);
                }).catch(er => {
                    console.log("error: ",er.message);
                    this.postData.video_duration = 0;
                    this.pageLoading = false;
                });
            } else {
                this.error = 'please enter a valid youtube video link';
                this.media.selected = false;
                this.media.url = null;
                this.postData.video = null;
            }
        },
        quitComponent() {
            this.$parent.exerciseDetail.data = null;
            this.$parent.exerciseDetail.visible = false;
        },
        changeMediaType() {
            this.media.selected = false;
            this.media.url = '';
            this.postData.video = null;
            this.postData.image = null;
            this.postData.customThumbnail = null;
            this.postData.manualVideoThumbnail = null;
            this.youtubeUrlInput = '';
            if (this.$refs.youtubeCustomThumbnail) {
                this.$refs.youtubeCustomThumbnail.value = '';
            }
            if (this.$refs.customVideoThumbnail) {
                this.$refs.customVideoThumbnail.value = '';
            }
        },
        onYoutubeCustomThumbnail() {
            const el = this.$refs.youtubeCustomThumbnail;
            const f = el && el.files && el.files[0];
            this.postData.customThumbnail = f || null;
        },
        onCustomVideoThumbnail() {
            const el = this.$refs.customVideoThumbnail;
            const f = el && el.files && el.files[0];
            this.postData.manualVideoThumbnail = f || null;
        },
        toggleTagsComponent() {
            this.tagsComp = !this.tagsComp;
        },
        selectedTags(tags, tagnames) {
            this.error = null;
            this.tagsComp = !this.tagsComp;
            this.postData.tagNames = tagnames;
            this.postData.tags = tags;
        },
        getVideo() {
            this.error = null;
            this.postData.video = this.$refs.selectedVideo.files[0];
            if(this.postData.video==null){
                this.media.selected = false;
                this.media.url = '';
                return;
            }
            if (!this.postData.video.type.includes("video")) {
                this.error = 'please select a video';
                this.media.selected = false;
                this.postData.video = null;
                this.media.url = '';
                return;
            }
            else {
                this.media.url = URL.createObjectURL(this.postData.video);
                this.media.selected = true;
            }
        },
        getDuration() {
            const videoPlayer = this.$refs.videoPlayer;
            this.postData.video_duration = parseInt(videoPlayer.duration);
            if (videoPlayer && videoPlayer.duration) {
                this.postData.video_duration = parseInt(videoPlayer.duration);
            } else {
                this.postData.video_duration = 0;
            }
        },
        getImage(){
            this.error = null;
            this.postData.image = this.$refs.selectedImage.files[0];
            if(this.postData.image==null){
                this.media.selected = false;
                this.media.url = '';
                return;
            }
            if (!this.postData.image.type.includes("image")) {
                this.postData.image = null;
                this.media.selected = false;
                this.media.url = '';
                this.error = 'please select an image';
                return;
            }
            else {
                this.media.url = URL.createObjectURL(this.postData.image);
                this.media.selected = true;
            }
        },
        checkForErrors() {
            if (this.postData.title.trim() == '') {
                this.error = 'please write exercise title';
                return;
            }
            if (this.postData.tags.length==0) {
                this.error = 'please select atleast one tag';
                return;
            }
            // if (this.postData.instructions.trim() == '') {
            //     this.error = 'please write exercise instructions';
            //     return;
            // }
            if (this.postData.type == '--select--') {
                this.error = 'please select exercise type';
                return;
            }
            if (this.postData.video_type==='youtube' && this.postData.video==null && !this.media.selected) {
                this.error = 'please enter youtube video URL';
                return;
            }
            if (this.postData.video_type==='custom' && this.postData.video==null && !this.media.selected && !this.postData.manualVideoThumbnail) {
                this.error = 'please upload the video';
                return;
            }
            if (this.postData.video_type==='image' && this.postData.image==null && !this.media.selected) {
                this.error = 'please upload the image';
                return;
            }
            this.createExercise();
        },
        async createExercise() {
            let fd = new FormData();
            fd.append('id', this.postData.id);
            fd.append('title', this.postData.title);
            fd.append('content_code', (this.postData.content_code || '').trim());
            fd.append('type', this.postData.type);
            fd.append('video_type', this.postData.video_type);
            fd.append('tags', JSON.stringify(this.postData.tags));
            fd.append('alternates', JSON.stringify(this.postData.alternates));
            fd.append('instructions', this.postData.instructions);
            fd.append('video_duration', this.postData.video_duration);
            fd.append('language', this.postData.language);
            fd.append('weights', this.postData.weights);
            if(this.postData.video_type==='image' && this.postData.image!=null)
            fd.append('thumbnail', this.postData.image);
            else if(this.postData.video_type==='custom' && this.postData.video!=null){
                if (this.postData.manualVideoThumbnail) {
                    fd.append('thumbnail', this.postData.manualVideoThumbnail);
                } else {
                    const thumbnailBlob = await this.generateThumbnail(this.postData.video);
                    const thumbnailImage = new File([thumbnailBlob], "thumbailImage.png", { type: "image/png", lastModified: new Date().getTime() });
                    fd.append('thumbnail', thumbnailImage);
                }
                fd.append('video', this.postData.video);
            } else if(this.postData.video_type==='custom' && this.postData.video==null && this.postData.manualVideoThumbnail) {
                fd.append('thumbnail', this.postData.manualVideoThumbnail);
            } else if(this.postData.video!=null) {
                fd.append('ytVideoId', this.postData.video);
            }
            if (this.postData.customThumbnail) {
                fd.append('custom_thumbnail', this.postData.customThumbnail);
            }

            this.pageLoading = true;
            this.loaderText = 'Uploading';
            axios.post(config.baseApiUrl + 'update-exercise', fd, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = "Exercise updated successfully";
                    this.informModal = true;
                    this.quitComponent();
                    this.$parent.getAllExercises();

                } else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                    console.log("Error: updating exercise ", res.data.message);
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = "Something went wrong";
                this.informModal = true;
                console.log("Error: updating exercise ", er);
            });
        },
        generateThumbnail(file) {
            return new Promise((resolve) => {
                const canvas = document.createElement("canvas");
                const video = document.createElement("video");
                video.autoplay = true;
                video.muted = true;
                video.currentTime = 1;
                video.src = URL.createObjectURL(file);

                video.onloadeddata = () => {
                    let ctx = canvas.getContext("2d");

                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
                    video.pause();
                    ctx.canvas.toBlob(
                        blob => {
                            resolve(blob);
                        },
                        "image/jpeg",
                        0.5 /* quality */
                    );
                };
            });
        },
        acknowledged() {
            this.informModal = false;
        },
    },
}
</script>
<style scoped>
.transparent{
    opacity: 0;
}
.cdzx {
    background-color: #ececec;
}
.main-box {
    background-color: white;
    border-radius: 30px;
    height: 90%;
}
.form-control:focus{
    border-color: #f2a18c !important;
    box-shadow: 0 0 0 0.25rem #f2a18c4a !important;
}
.shd_card input[type="text"] {
    padding: 10px;
    border-radius: 10px;
}

.celll {
    width: 49%;
    border: 1px solid grey;
    border-radius: 10px;
    padding: 10px;
    display: flex;
    justify-content: space-between;
}

@media only screen and (max-width: 900px) {
    div.celll {
        width: 100%;
        margin-top: 5px;
    }

    button.trans_btn {
        right: 10px !important;
    }
}

button.celll {
    background-color: transparent;
    justify-content: center !important;
    color: #F2A18C;
}

.selec {
    background-color: #F2A18C !important;
    color: black !important;
}

.tag-box {
    background-color: #F2A18C;
    border-radius: 5px;
    font-size: 10px;
    padding: 5px;
    margin: 2px;
    white-space: nowrap;
}

.noBorder {
    border: none !important;
}

.inp1::placeholder {
    color: #F2A18C;
}
</style>
