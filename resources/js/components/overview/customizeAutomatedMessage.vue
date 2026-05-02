<template lang="">
    <div class="my-popup-component">
        <div class="w-50 brds-3 p-3 bg-white position-relative text-center" style="height:60vh;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent" style="right:10px;top:10px;font-size:25px;z-index:9;">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h4 class="mb-2">Customize Message</h4>
            <div class="col-12 px-4" style="height:30px;">
                <div class="form-check col-2 float-start text-start">
                    <input v-model="msgType" value="text" class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                    <label class="form-check-label" for="flexRadioDefault1">
                        Text
                    </label>
                </div>
                <div class="form-check col-2 float-start text-start">
                    <input v-model="msgType" value="audio" class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" >
                    <label class="form-check-label" for="flexRadioDefault2">
                        Audio
                    </label>
                </div>
                <div class="form-check col-2 float-start text-start">
                    <input v-model="msgType" value="video" class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2" >
                    <label class="form-check-label" for="flexRadioDefault2">
                        Video
                    </label>
                </div>
            </div>
            <div v-if="msgType=='text'" class="col-12 mt-1 d-flex justify-content-around">
                <div class="col-5">
                    <h4>English</h4>
                    <textarea  v-model="content" class="col-12 mt-3 tsl mx-auto brds-2 border-0 p-3" rows="5"></textarea>
                </div>
                <div class="col-5">
                    <h4>Arabic</h4>
                    <textarea v-model="content_ar" class="col-12 mt-3 tsl mx-auto brds-2 border-0 p-3" rows="5"></textarea>
                </div>
            </div>
            <!-- <div v-if="msgType=='text'&&newmsgType!=='text'" class="col-12 d-flex justify-content-around">
                <div class="col-5">
                    <h4>English:</h4>
                    <textarea  v-model="postData.content" class="col-5 mt-2 tsl mx-auto brds-2 border-0 p-3" rows="5"></textarea>
                </div>
                <div class="col-5">
                    <h4>Arabic:</h4>
                    <textarea v-model="postData.content_ar" class="col-5 mt-2 tsl mx-auto brds-2 border-0 p-3" rows="5"></textarea>
                </div>
            </div> -->
            <div v-if="msgType=='video'" class="col-12 mt-2 d-flex justify-content-around">
                <h4 class="col-5">English</h4>
                <h4 class="col-5">Arabic</h4>
            </div>
            <div v-if="newmsgType=='video'&&msgType=='video'" class="col-11 mx-auto d-flex justify-content-around p-2 mt-2 tsl mx-auto brds-2 position-relative align-items-center" style="min-height:25px;">
                <video v-if="videoURL!==null" @play="pauseOthers('video',0)" :src="videoURL" controls style="width:45%;"></video>
                <video v-if="videoURL_ar!==null" @play="pauseOthers('video',1)" :src="videoURL_ar" controls style="width:45%;"></video>
            </div>
            <div v-if="msgType=='video'" class="col-12 d-flex justify-content-around mt-2 mx-auto position-relative align-items-center" style="height:125px;">
                <div class="col-5 tsl brds-2 position-relative p-2" style="height:inherit;">
                    <input @input="getVideo('0')" ref="selectedVideoEn" type="file" accept="video/*" class="col-12 position-absolute" style="height:100%;top:0px;left:0px;opacity:0;">
                    <p v-if="video==null" class="mb-0"> Select a video file for the Automated Message</p>
                    <p v-else class="mb-0 col-12" style="word-break:break-all;">{{video.name}}</p>
                </div>
                <div class="col-5 tsl brds-2 position-relative p-2" style="height:inherit;">
                    <input @input="getVideo('1')" ref="selectedVideoAr" type="file" accept="video/*" class="col-12 position-absolute" style="height:100%;top:0px;left:0px;opacity:0;">
                    <p v-if="video_ar==null" class="mb-0"> Select a video file for the Automated Message</p>
                    <p v-else class="mb-0 col-12" style="word-break:break-all;">{{video_ar.name}}</p>
                </div>
            </div>
            <div v-if="msgType=='audio'" class="col-12 mt-2 d-flex justify-content-around">
                <h4 class="col-5">English</h4>
                <h4 class="col-5">Arabic</h4>
            </div>
            <div v-if="newmsgType=='audio'&&msgType=='audio'" class="col-11 mx-auto d-flex justify-content-around p-2 tsl brds-2">
                <audio v-if="audioURL!==null" @play="pauseOthers('audio',0)" controls class="col-5" style="height:30px;">
                    <source :src="audioURL">
                </audio>
                <audio v-if="audioURL_ar!==null" @play="pauseOthers('audio',1)" controls class="col-5" style="height:30px;">
                    <source :src="audioURL_ar">
                </audio>
            </div>
            <div v-if="msgType=='audio'" class="col-12 d-flex justify-content-around mt-2 px-3 ">
                <div class="tsl brds-2 p-2" style="width:47%">
                    <div>
                        <span>{{audio.recordingText}}:</span>
                        <button @click="audioAction(0)" class="trans_btn rcdBtn" :class="{'rcding': audio.recording,'rcded': audio.recorded}">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <p class="mb-0 mx-1">{{minutes}}:{{seconds}}</p>
                        <img v-if="audio.recording" src="/cms-assets/images/Overview/sound.gif" class="mx-1" style="height:20px;width:100px;" alt="">
                    </div>
                </div>
                <div class="tsl brds-2 p-2" style="width:47%">
                    <div>
                        <span>{{audio_ar.recordingText}}:</span>
                        <button @click="audioAction(1)" class="trans_btn rcdBtn" :class="{'rcding': audio_ar.recording,'rcded': audio_ar.recorded}">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <p class="mb-0 mx-1">{{minutes_ar}}:{{seconds_ar}}</p>
                        <img v-if="audio_ar.recording" src="/cms-assets/images/Overview/sound.gif" class="mx-1" style="height:20px;width:100px;" alt="">
                    </div>
                </div>
            </div>
            <button class="prim_bg px-4 py-1 brds-2 border-0 mt-3" @click="customizeMessage">Update</button>
        </div>
    </div>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
</template>
<script>
import recorder from '../../recordAudio';
import axios from 'axios';
import config from '../../config';
import Loader from '../loader.vue';
import Inform from '../inform.vue';
export default {
    components: { Loader, Inform },
    props: ['autoMessage',],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            msgType: 'text',
            audio: {
                recordingText: "Click to Start Recording",
                recording: false,
                recorded: false,
                recordState: "ready",
                _startTime: null,
                _endTime: null,
                _recorderObj: null,
                _time: 300,
                _timer: null,
                _MediaStream: null
            },
            audio_ar: {
                recordingText: "Click to Start Recording",
                recording: false,
                recorded: false,
                recordState: "ready",
                _startTime: null,
                _endTime: null,
                _recorderObj: null,
                _time: 300,
                _timer: null,
                _MediaStream: null
            },
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            content: null,
            audiofile: null,
            video: null,
            videoURL: null,
            audioURL: null,
            content_ar: null,
            audiofile_ar: null,
            audioURL_ar: null,
            video_ar: null,
            videoURL_ar: null,
            newmsgType: null,
            minutes: 0,
            seconds: 0,
            recording: 0,
            minutes_ar: 0,
            seconds_ar: 0,
            recording_ar: 0,
        }
    },
    mounted() {
        if (this.autoMessage.type == 'text') {
            this.content = this.autoMessage.content;
            this.content_ar = this.autoMessage.content_ar;
        }
        else if (this.autoMessage.type == 'audio') {
            this.audioURL = this.autoMessage.content;
            this.audioURL_ar = this.autoMessage.content_ar;
        }
        else {
            this.videoURL = this.autoMessage.content;
            this.videoURL_ar = this.autoMessage.content_ar;
        }
        this.msgType = this.autoMessage.type;
        this.newmsgType = this.autoMessage.type;
    },
    methods: {
        pauseOthers(m, n) {
            if (m == 'audio') {
                let audio = document.getElementsByTagName('audio');
                for (let index = 0; index < audio.length; index++) {
                    if (index !== n) {
                        audio[index].pause();
                    }
                }
            }
            else if (m == 'video') {
                let video = document.getElementsByTagName('video');
                for (let index = 0; index < video.length; index++) {
                    if (index !== n) {
                        video[index].pause();
                    }
                }
            }
        },
        getVideo(m) {
            if (m == 0) {
                this.video = this.$refs.selectedVideoEn.files[0];
                if (!this.video.type.includes("video")) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Selected file is not a video';
                    this.informModal = true;
                    return
                }
                this.newmsgType = "video"
                this.videoURL = URL.createObjectURL(this.video);
            }
            else if (m == 1) {
                this.video_ar = this.$refs.selectedVideoAr.files[0];
                if (!this.video_ar.type.includes("video")) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Selected file is not a video';
                    this.informModal = true;
                    return
                }
                this.newmsgType = "video"
                this.videoURL_ar = URL.createObjectURL(this.video_ar);
            }
        },
        quitComponent() {
            this.$parent.showCustomizeMessage(null, null, null);
        },
        customizeMessage() {
            let fd = new FormData;
            fd.append('msg_id', this.autoMessage.id);
            fd.append('type', this.msgType);
            if (this.msgType == 'audio') {
                if (this.audiofile == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Record the audio for the English message';
                    this.informModal = true;
                    return
                }
                if (this.audiofile_ar == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Record the audio for the Arabic message';
                    this.informModal = true;
                    return
                }
                fd.append('audio', this.audiofile);
                fd.append('audio_ar', this.audiofile_ar);
            }
            else if (this.msgType == 'video') {
                if (this.video == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Select the video for the English message';
                    this.informModal = true;
                    return
                }
                if (this.video_ar == null) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Select the video for the Arabic message';
                    this.informModal = true;
                    return
                }
                fd.append('video', this.video);
                fd.append('video_ar', this.video_ar);
            }
            else if (this.msgType == 'text') {
                if (this.content == null || this.content == '') {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Enter the text for the English message';
                    this.informModal = true;
                    return
                }
                if (this.content_ar == null || this.content_ar == '') {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Enter the text for the Arabic message';
                    this.informModal = true;
                    return
                }
                fd.append('content', this.content);
                fd.append('content_ar', this.content_ar);
            }
            this.allMessages = null;
            this.pageLoading = true;
            this.loaderText = 'Updating';
            axios.post(config.baseApiUrl + 'customize-auto-message', fd, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.quitComponent();
                    this.$parent.getAllMessages();
                }
                else {
                    this.modalTitle = 'Error';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            });
        },
        audioAction(m) {
            if (m == 0) {
                this.audioURL = null;
                if (this.audio.recordState == "ready") {
                    this.audio.recordingText = "Click to Stop and Send";
                    this.audio.recording = true;
                    this.audio.recorded = false;
                    this.audio.recordState = "recording";
                    this.calculateTime(0);
                    this.audio._startTime = new Date().getTime();
                    window.clearInterval(this.audio._timer);
                    recorder.get((rec, val) => {
                        this.audio._recorderObj = rec;
                        this.audio._MediaStream = val
                        if (rec) {
                            this.audio._timer = setInterval(() => {
                                if (this.audio._time <= 0) {
                                    rec.stop();
                                    this.audio._time = 300
                                    this.audio._timer = null;
                                    this.audioAction()
                                    return;
                                } else {
                                    this.audio._time--;
                                    rec.start();
                                }
                            }, 1000);
                        }
                    });
                }
                else if (this.audio.recordState == "recording") {
                    this.audio.recordingText = "Click to Play Recording";
                    this.audio.recording = false;
                    this.audio.recorded = true;
                    this.audio.recordState = "recorded";
                    window.clearInterval(this.audio._timer)
                    this.audio._endTime = new Date().getTime();
                    let duration = (this.audio._endTime - this.audio._startTime) / 1000;
                    if (this.audio._recorderObj) {
                        this.audio._recorderObj.stop();
                        // Reset speaking time
                        this.audio._time = 300;
                        // Get the speech binaries
                        let blob = this.audio._recorderObj.getBlob();
                        this.audio._MediaStream.getTracks()[0].stop();
                        this.audio._recorderObj = null;
                        // this.$parent.sendAudioRecordedMessage(blob,duration);
                        let randForName = Math.random() * (9999999 - 1111111) + 1111111;
                        this.audiofile = new File([blob], "audioSound" + randForName + ".wav", { type: "audio/wav", lastModified: new Date().getTime() });
                        // this.$parent.sendFileMessage(audioFile);
                        this.audioURL = URL.createObjectURL(this.audiofile);
                        this.newmsgType = 'audio';
                        // this.calculateTime(2);
                    }
                }
            }
            if (m == 1) {
                this.audioURL_ar = null;
                if (this.audio_ar.recordState == "ready") {
                    this.audio_ar.recordingText = "Click to Stop and Send";
                    this.audio_ar.recording = true;
                    this.audio_ar.recorded = false;
                    this.audio_ar.recordState = "recording";
                    this.calculateTime(1);
                    this.audio_ar._startTime = new Date().getTime();
                    window.clearInterval(this.audio_ar._timer);
                    recorder.get((rec, val) => {
                        this.audio_ar._recorderObj = rec;
                        this.audio_ar._MediaStream = val
                        if (rec) {
                            this.audio_ar._timer = setInterval(() => {
                                if (this.audio_ar._time <= 0) {
                                    rec.stop();
                                    this.audio_ar._time = 300
                                    this.audio_ar._timer = null;
                                    this.audio_arAction()
                                    return;
                                } else {
                                    this.audio_ar._time--;
                                    rec.start();
                                }
                            }, 1000);
                        }
                    });
                }
                else if (this.audio_ar.recordState == "recording") {
                    this.audio_ar.recordingText = "Click to Play Recording";
                    this.audio_ar.recording = false;
                    this.audio_ar.recorded = true;
                    this.audio_ar.recordState = "recorded";
                    window.clearInterval(this.audio_ar._timer)
                    this.audio_ar._endTime = new Date().getTime();
                    let duration = (this.audio_ar._endTime - this.audio_ar._startTime) / 1000;
                    if (this.audio_ar._recorderObj) {
                        this.audio_ar._recorderObj.stop();
                        // Reset speaking time
                        this.audio_ar._time = 300;
                        // Get the speech binaries
                        let blob = this.audio_ar._recorderObj.getBlob();
                        this.audio_ar._MediaStream.getTracks()[0].stop();
                        this.audio_ar._recorderObj = null;
                        // this.$parent.sendaudio_arRecordedMessage(blob,duration);
                        let randForName = Math.random() * (9999999 - 1111111) + 1111111;
                        this.audiofile_ar = new File([blob], "audioSound" + randForName + ".wav", { type: "audio/wav", lastModified: new Date().getTime() });
                        // this.$parent.sendFileMessage(audioFile);
                        this.audioURL_ar = URL.createObjectURL(this.audiofile_ar);
                        this.newmsgType = 'audio';
                        // this.calculateTime(2);
                    }
                }
            }
        },
        calculateTime(m) {
            if (m == 0) {
                if (this.audio.recording) {
                    setTimeout(() => {
                        if (this.recording == 0) {
                            this.recording = this.recording + 1;
                            this.calculateTime(0);
                        }
                        else if (this.seconds < 59) {
                            this.seconds = this.seconds + 1;
                            this.calculateTime(0);
                        }
                        else if (this.minutes < 4) {
                            this.seconds = 0;
                            this.minutes = this.minutes + 1;
                            this.calculateTime(0);
                        }
                        else {
                            this.audioAction();
                            this.calculateTime(0);
                        }
                    }, 1000)
                }
                else {
                    this.seconds = this.seconds - 1;
                }
            }
            if (m == 1) {
                if (this.audio_ar.recording) {
                    setTimeout(() => {
                        if (this.recording_ar == 0) {
                            this.recording_ar = this.recording_ar + 1;
                            this.calculateTime(1);
                        }
                        else if (this.seconds_ar < 59) {
                            this.seconds_ar = this.seconds_ar + 1;
                            this.calculateTime(1);
                        }
                        else if (this.minutes_ar < 4) {
                            this.seconds_ar = 0;
                            this.minutes_ar = this.minutes_ar + 1;
                            this.calculateTime(1);
                        }
                        else {
                            this.audioAction();
                            this.calculateTime(1);
                        }
                    }, 1000)
                }
                else {
                    this.seconds_ar = this.seconds_ar - 1;
                }
            }
        },
        acknowledged() {
            this.informModal = false;
        },
    }
}
</script>
<style scoped>
.rcdBtn {
    width: 50px;
    height: 50px;
    font-size: 25px;
    border-radius: 50%;
    border: 1px solid #F2A18C;
    color: #F2A18C;
    margin-left: 10px;
}

.rcding {
    border: 1px solid red;
    color: red;
}

.rcded {
    border: 1px solid rgb(2, 168, 2);
    color: rgb(2, 168, 2);
}
</style>
