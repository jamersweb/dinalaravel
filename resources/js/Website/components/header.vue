<template lang="">
    <div v-if="shownav" class="my-popup-component" @click.self="shownav=false" style="height:100vh;width:100vw">
        <div :class="{show : shownav}" class="position-absolute animate__animated animate__fadeInRight" style="width:80px;height:100%;right:0px;display:none;z-index:9999;background-color:black;top:0;">
            <div class="position-relative pt-4 h-100  main-website">
                <button class="trans_btn position-absolute p-0" @click="shownav=false"
                    style="right:10px;top:5px;font-size:25px">
                    <i class="fa-solid fa-xmark" style="color:#FC9C88;"></i>
                </button>
                <router-link style="background:none;" to="/">
                    <button class="w-100 border-0 mt-4 mx-2 my-2 main-btn1">HOME</button>
                </router-link>
                <router-link style="background:none;" to="/AboutMe" >
                    <button class="w-100 border-0 mx-2 my-2 main-btn1">ABOUT ME</button>
                </router-link>
                <!-- <router-link  style="background:none;" to="/CalculateBMR&TDEE">
                    <button class="w-100 border-0 mx-2 my-2 main-btn1">CALCULATE BMR & TDEE</button>
                </router-link> -->
                <!-- <router-link  style="background:none;" to="/ListOfFoods">
                    <button class="w-100 border-0 mx-2 my-2 main-btn1">LIST OF FOODS</button>
                </router-link> -->
                <router-link  style="background:none;" to="/Podcast">
                    <button class="w-100 border-0 mx-2 my-2 main-btn1">PODCAST</button>
                </router-link>
                <router-link  style="background:none;" to="/ContactUS">
                    <button class="w-100 border-0 mx-2 my-2 main-btn1">CONTACT US</button>
                </router-link>
                <router-link style="background:none;" to="/user/login">
                    <button class="w-100 border-0 mx-2 my-2 brds-2 join" style="height:40px;width:95% !important;">JOIN  NOW</button>
                </router-link>
            </div>
        </div>
    </div>
    <div style="overflow:hidden">
        <div class="row">
            <div class="col-12 pb-4 main position-relative">
                <div class="col-12 col-sm-11 mx-auto mt-4" style="min-height:100px;">
                    <div class="h-100 float-start logo-div ms-0 ms-sm-3">
                        <router-link style="background:none;" to="/">
                            <img src="/cms-assets/images/navbar-topbar/logo-by-dina-1.png" class="logo float-start">
                        </router-link>
                    </div>
                    <div class="float-start px-0 px-sm-4 d-flex flex-wrap" style="height:100px;align-items:center;width:calc(100% - 116px)">
                        <div class="nav-div">
                            <div class="uncolapsed-nav main-website">
                                <div class="col-9 col-xl-8 float-start">
                                    <router-link style="background:none;" to="/">
                                        <button class="border-0 mx-1 main-btn mt-2">HOME</button>
                                    </router-link>
                                    <router-link style="background:none;" to="/AboutMe">
                                        <button class="border-0 mx-1 main-btn">ABOUT ME</button>
                                    </router-link>
                                    <!-- <router-link style="background:none;" to="/CalculateBMR&TDEE">
                                        <button class="border-0 mx-1 main-btn">CALCULATE BMR & TDEE</button>
                                    </router-link> -->
                                    <!-- <router-link style="background:none;" to="/ListOfFoods">
                                        <button class="border-0 mx-1 main-btn">LIST OF FOODS</button>
                                    </router-link> -->
                                    <router-link style="background:none;" to="/Podcast">
                                        <button class="border-0 mx-1 main-btn">PODCAST</button>
                                    </router-link>
                                    <router-link style="background:none;" to="/ContactUS">
                                        <button class="border-0 mx-1 main-btn">CONTACT US</button>
                                    </router-link>
                                </div>
                                <div class="col-3 col-xl-4 float-start">
                                    <router-link style="background:none;" to="/user/login">
                                        <button class="border-0 brds-2 join float-start ms-0 ms-xl-4">JOIN  NOW</button>
                                    </router-link>
                                </div>
                            </div>
                            <div class="colapsed-nav">
                                <button @click.self="shownav=!shownav,changePosition()" class="border-0 mx-1 p-0 main-btn" style="color:#FC9C88">
                                    <img @click.self="shownav=!shownav,changePosition()" src="/cms-assets/images/navbar-topbar/menu.png" alt="" style="height:30px">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 ps-2 ps-sm-4 pt-4 position-relative" style="height:calc(100% - 150px)">
                    <div class="col-11 col-md-8 col-lg-6 col-xl-6 ms-md-5 ps-1 ps-sm-3 position-relative">
                        <p class="mb-0 f-50" style="color:white;font-weight:bold;">{{detail}}</p>
                        <p class="mb-0 f-50" style="color:white;font-weight:bold;">{{title1}}</p>
                        <p class="mb-0 f-50" style="color:#FC9C88;font-weight:bold;">{{title2}}</p>
                        <p class="mb-0 f-50 mt-4 ms-1" style="color:white;font-weight:bold;">{{title3}}</p>
                    </div>
                    <div class="col-12 ps-2 ps-sm-4 pt-sm-4 position-relative" style="height:80px;">
                        <p class="mb-0 col-11 col-sm-8 col-xl-6 f-30 position-absolute" style="color:white;right:0px;top:0px;">{{discription}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['detail', 'title1', 'title2', 'title3', 'discription', 'page'],
    data() {
        return {
            shownav: false,
        }
    },
    methods: {
        changePosition() {
            this.$parent.changePosition();
        }
    }
}
</script>
<style scoped>
html {
    margin: 0;
    padding: 0;
}

body {
    margin: 0;
    padding: 0;
}

.active {
    background-color: #FC9C88;
}

.main {
    min-height: 300px;
    background-image: url(/cms-assets/images/navbar-topbar/Group16759.png);
    background-position: center;
    background-size: cover;
}

.main-btn {
    background-color: transparent;
    font-size: 17px;
    font-weight: bold;
    color: white;
}

.main-btn:hover {
    color: #FC9C88 !important;
}

.main-btn1 {
    background-color: transparent;
    font-size: 18px;
    text-align: left;
    color: white;
}

.main-btn1:hover {
    color: #FC9C88 !important;
}

.nav-div {
    width: 100%;
}

.uncolapsed-nav {
    display: block;
}

.colapsed-nav {
    display: none;
    text-align: end;
}

.join {
    font-size: 18px;
    font-weight: bold;
    background-color: #FC9C88;
    color: black;
    width: 150px;
    height: 40px;
}

.join:hover {
    color: #FC9C88;
    background-color: #10161B;
}

.show {
    display: block !important;
    width: 250px !important;
}

.f-50 {
    font-size: 60px;
}

.f-30 {
    font-size: 30px;
}

.f-20 {
    font-size: 20px;
}

.main-text {
    font-size: 30px;
    font-weight: bold;
}

.textdiv {
    height: 50px;
}

.logo {
    height: 100px;
    width: 100px;
}

.logo-div {
    text-align: end;
    width: 100px;
}

@media screen and (max-width: 770px) {
    .uncolapsed-nav {
        display: none;
    }

    .colapsed-nav {
        display: block;
    }

    .f-20 {
        font-size: 15px;
    }

    .f-30 {
        font-size: 20px;
    }

    .f-50 {
        font-size: 50px;
    }
}

@media screen and (max-width: 480px) {
    .main-text {
        font-size: 18px;
    }

    .logo {
        height: 80px;
        width: 80px;
        margin-top: 10px;
    }

    .logo-div {
        margin-left: 10px;
    }

    .textdiv {
        height: 30px;
    }

    .f-20 {
        font-size: 13px;
    }

    .f-30 {
        font-size: 17px;
    }

    .f-50 {
        font-size: 40px;
    }
}
</style>
