<template lang="">

  <!-- text message case  -->

  <div v-if="msgdet.msg_type=='text'" class="msg textMsg mt-4" :class="{'ms-auto' : msgdet.sender==='admin'}" style="position:relative;">
    <!-- <img src="/cms-assets/images/Clients/downarrow.png" alt="" class="position-absolute" style="height:7px;right:10px;top:5px;"> -->
      <div class="d-flex align-items-center">
          <pre class="h7 mb-0" style="white-space: pre-wrap;">{{msgdet.content}}</pre>
          <div class="ms-2 dropdown" style="position:absolute;right:0px;top:0px;">
              <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" width="10.867" height="6.273" viewBox="0 0 10.867 6.273">
                  <g id="Shape" transform="translate(1.061 1.061)">
                    <path id="Shape-2" data-name="Shape" d="M.089,8.746,4.462,4.462,0,0" transform="translate(8.746) rotate(90)"
                      fill="none" stroke="#343434" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                      stroke-width="1.5" />
                  </g>
                </svg>
              </button>
              <ul class="tsl dropdown-menu border-0">
                  <!-- <li><button class="dropdown-item">Forward</button></li> -->
                  <li><button class="dropdown-item" @click="deleteMessage(msgdet.id)">Delete</button></li>
              </ul>
          </div>
      </div>
      <span class="ms-date h8">{{msgdet.time}}</span>
  </div>

  <!-- text message case  -->
  <!-- image message case  -->

  <div v-if="msgdet.msg_type=='image'" class="msg mt-4" :class="{'ms-auto' : msgdet.sender==='admin'}" style="position:relative;">
    <div class="d-flex align-items-center">
      <div class="py-2">
        <a :href="msgdet.file_url" target="blank">
          <img :src="msgdet.file_url" alt="" class="mx-auto d-block img-fluid brds-3" style="max-width:400px">
        </a>
      </div>
    </div>
    <div class="ms-2 dropdown" style="position:absolute;right:0px;top:0px;">
        <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" width="10.867" height="6.273" viewBox="0 0 10.867 6.273">
            <g id="Shape" transform="translate(1.061 1.061)">
              <path id="Shape-2" data-name="Shape" d="M.089,8.746,4.462,4.462,0,0" transform="translate(8.746) rotate(90)"
                fill="none" stroke="#343434" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                stroke-width="1.5" />
            </g>
          </svg>
        </button>
        <ul class="tsl dropdown-menu border-0">
            <!-- <li><button class="dropdown-item">Forward</button></li> -->
            <li><button class="dropdown-item" @click="deleteMessage(msgdet.id)">Delete</button></li>
        </ul>
    </div>
    <a :href="msgdet.file_url" :download="msgdet.content" class="mx-0 downloadBtn" title="Download Full Resolution"><i class="fa-solid fa-download"></i></a>
    <!-- <button @click="downloadImage(msgdet.file_url,msgdet.content)" class="mx-0 downloadBtn border-0" style="background-color:transparent;"><i class="fa-solid fa-download"></i></button> -->
    <span class="ms-date h8">{{msgdet.time}}</span>
  </div>

  <!-- image message case  -->
  <!-- video message case  -->

  <div v-if="msgdet.msg_type=='video'" class="msg mt-4" :class="{'ms-auto' : msgdet.sender==='admin'}" style="position:relative;">
    <div class="d-flex align-items-center">
      <div class="py-2">
        <video :src="msgdet.file_url" controls class="mx-auto d-block img-fluid brds-3" style="width:310px;height:175px">
          Browser does not support video
        </video>
        <div class="d-flex justify-content-between mt-2">
          <!-- <p class="mb-0 me-3">{{msgdet.documentName}}</p> -->
          <a :href="msgdet.file_url" :download="msgdet.content" class="mx-0 downloadBtn" title="Download"><i class="fa-solid fa-download"></i></a>
          <!-- <button @click="downloadImage(msgdet.file_url,msgdet.content)" class="mx-0 downloadBtn border-0" style="background-color:transparent;"><i class="fa-solid fa-download"></i></button> -->
        </div>
      </div>
    </div>
    <div class="ms-2 dropdown" style="position:absolute;right:0px;top:0px;">
        <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" width="10.867" height="6.273" viewBox="0 0 10.867 6.273">
            <g id="Shape" transform="translate(1.061 1.061)">
              <path id="Shape-2" data-name="Shape" d="M.089,8.746,4.462,4.462,0,0" transform="translate(8.746) rotate(90)"
                fill="none" stroke="#343434" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                stroke-width="1.5" />
            </g>
          </svg>
        </button>
        <ul class="tsl dropdown-menu border-0">
            <!-- <li><button class="dropdown-item">Forward</button></li> -->
            <li><button class="dropdown-item" @click="deleteMessage(msgdet.id)">Delete</button></li>
        </ul>
    </div>
    <span class="ms-date h8">{{msgdet.time}}</span>
  </div>

  <!-- video message case  -->
  <!-- document message case  -->

  <div v-if="msgdet.msg_type=='document'" class="msg mt-4 justify-content-center" :class="{'ms-auto' : msgdet.sender==='admin'}" style="position:relative;width:250px">
    <div class="d-flex align-items-center">
      <div class="py-2 text-center  ">
        <img src="/cms-assets/images/doc.png" alt="" class="img-fluid d-block mx-auto">
        <div class="d-flex justify-content-between mt-2">
          <p class="mb-0 me-3">{{msgdet.content}}</p>
          <a :href="msgdet.file_url" :download="msgdet.content" class="mx-0 downloadBtn" title="Download"><i class="fa-solid fa-download"></i></a>
          <!-- <button @click="downloadImage(msgdet.file_url,msgdet.content)" class="mx-0 downloadBtn border-0" style="background-color:transparent;"><i class="fa-solid fa-download"></i></button> -->
        </div>
      </div>
    </div>
    <div class="ms-2 dropdown" style="position:absolute;right:0px;top:0px;">
        <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" width="10.867" height="6.273" viewBox="0 0 10.867 6.273">
            <g id="Shape" transform="translate(1.061 1.061)">
              <path id="Shape-2" data-name="Shape" d="M.089,8.746,4.462,4.462,0,0" transform="translate(8.746) rotate(90)"
                fill="none" stroke="#343434" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                stroke-width="1.5" />
            </g>
          </svg>
        </button>
        <ul class="tsl dropdown-menu border-0">
            <!-- <li><button class="dropdown-item">Forward</button></li> -->
            <li><button class="dropdown-item" @click="deleteMessage(msgdet.id)">Delete</button></li>
        </ul>
    </div>
    <span class="ms-date h8">{{msgdet.time}}</span>
  </div>

  <!-- document message case  -->
  <!-- audio message case  -->

  <div v-if="msgdet.msg_type=='audio'" class="msg mt-4" :class="{'ms-auto' : msgdet.sender==='admin'}" style="position:relative;">
    <div class="d-flex align-items-center">
      <div class="pt-2" style="max-width:400px">
        <audio  controls style="width: 400px;max-width: 100%;">
          <source :src="msgdet.file_url" type="audio/mp3">
            Browser does not support audio
        </audio>
      </div>
    </div>
    <div class="ms-2 dropdown" style="position:absolute;right:0px;top:0px;">
        <button class="prim_hvr trans_btn px-2" data-bs-toggle="dropdown" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" width="10.867" height="6.273" viewBox="0 0 10.867 6.273">
            <g id="Shape" transform="translate(1.061 1.061)">
              <path id="Shape-2" data-name="Shape" d="M.089,8.746,4.462,4.462,0,0" transform="translate(8.746) rotate(90)"
                fill="none" stroke="#343434" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10"
                stroke-width="1.5" />
            </g>
          </svg>
        </button>
        <ul class="tsl dropdown-menu border-0">
            <!-- <li><button class="dropdown-item">Forward</button></li> -->
            <li><button class="dropdown-item" @click="deleteMessage(msgdet.id)">Delete</button></li>
        </ul>
    </div>
    <span class="ms-date h8">{{msgdet.time}}</span>
  </div>
  
  <!-- audio message case  -->

</template>
<script>
export default {
    props: ['msgdet', 'baseUrl', 'logInDetails'],
    data() {
        return {
            msgTime: null,
        }
    },
    // mounted() {
    //     this.msgTime = new Date(this.msgdet.time).toLocaleString();
    // },
    methods: {
        deleteMessage(id) {
            this.$parent.deleteMessage(id);
        }
    }
}
</script>
<style scoped>
.msg {
    padding: 10px 20px;
    display: flex;
    background-color: gainsboro;
    border-radius: 2rem;
    width: fit-content;
    position: relative;
    justify-content: space-between;
    align-items: center;
    min-width: 125px;
    max-width: 90%;
}

.ms-2.dropdown {
  display: none;
}

.msg:hover .ms-2.dropdown {
  display: block;
}

.msg.ms-auto {
    background-color: #efefef;
}

.ms-date {
    position: absolute;
    right: 0;
    bottom: -18px;
}

.downloadBtn {
    position: absolute;
    top: 43%;
    right: -35px;
    color: black;
    font-size: 25px;
}

.ms-auto .downloadBtn {
    left: -35px;
    right: unset !important;
}

pre {
    font-family: 'Poppins', sans-serif !important;
}
</style>
