<template lang="">
    <Loader v-if="pageLoading" loadingText="Sending"/>
    <div class="my-popup-component">
        <div class="bg-white brds-5 p-4 col-11 col-md-8 col-xl-6 position-relative">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="text-center" v-if="filetype=='image'">
                <img :src="fileUrl" class="img-fluid" style="max-height:350px">
            </div>
            <div class="text-center" v-if="filetype=='video'">
                <video controls :src="fileUrl" class="img-fluid" style="max-height:350px">Browser Does Not Support Video</video>
            </div>
            <div class="d-flex justify-content-center align-items-center" v-if="filetype=='document'">
                <img src="/cms-assets/images/doc.png" alt="" class="img-fluid mx-2" style="height:200px">
                <h3 class="mb-0 mx-2">{{file.name}}</h3>
            </div>
            <div v-if="filetype=='audio'" class="text-center">
                <div>
                    <p class="h7">Maximum 5 minutes audio can be sent</p>
                    <div>
                        <span>{{audio.recordingText}}:</span>
                        <button @click="audioAction()" class="trans_btn rcdBtn" :class="{'rcding': audio.recording,'rcded': audio.recorded}">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center">
                        <img v-if="audio.recording" src="/cms-assets/images/Overview/sound.gif" class="mx-1" style="height:20px;width:100px;" alt="">
                    </div>
                </div>
            </div>
            <div class="text-center mt-3" v-if="filetype!='audio'">
                <button class="prim_btn brds-2 px-3" @click="send()">Send</button>
            </div>
            <div class="text-center mt-3" v-if="audio.recording">
                <div class="lds-facebook"><div></div><div></div><div></div></div>
            </div>
        </div>
    </div>
</template>
<script>
import Loader from '../../components/loader.vue';
import recorder from '../../recordAudio';

export default {
    components: { Loader },
    data() {
        return {
            fileUrl: null,
            pageLoading: false,
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
            }
        }
    },
    props: ['file', 'filetype'],
    mounted() {
        if (this.filetype == 'image' || this.filetype == 'video')
            this.fileUrl = URL.createObjectURL(this.file);
    },
    methods: {
        quitComponent() {
            this.$parent.closePreviewComp();
        },
        send() {
            if (this.filetype !== 'audio')
                this.$parent.sendFileMessage(this.file);
        },
        audioAction() {
            if (this.audio.recordState == "ready") {
                this.audio.recordingText = "Click to Stop and Send";
                this.audio.recording = true;
                this.audio.recorded = false;
                this.audio.recordState = "recording";

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
                    const audioFile = new File([blob], "audioSound" + randForName + ".wav", { type: "audio/wav", lastModified: new Date().getTime() });
                    this.$parent.sendFileMessage(audioFile);
                }
            }
        }
    },
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
    margin-left: 20px;
}

.rcding {
    border: 1px solid red;
    color: red;
}

.rcded {
    border: 1px solid rgb(2, 168, 2);
    color: rgb(2, 168, 2);
}

.lds-facebook {
    display: inline-block;
    position: relative;
    width: 80px;
    height: 80px;
}

.lds-facebook div {
    display: inline-block;
    position: absolute;
    left: 8px;
    width: 16px;
    background: #fff;
    animation: lds-facebook 1.2s cubic-bezier(0, 0.5, 0.5, 1) infinite;
}

.lds-facebook div:nth-child(1) {
    left: 8px;
    animation-delay: -0.24s;
}

.lds-facebook div:nth-child(2) {
    left: 32px;
    animation-delay: -0.12s;
}

.lds-facebook div:nth-child(3) {
    left: 56px;
    animation-delay: 0;
}

@keyframes lds-facebook {
    0% {
        top: 8px;
        height: 64px;
    }

    50%,
    100% {
        top: 24px;
        height: 32px;
    }
}
</style>
