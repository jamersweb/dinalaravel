<template lang="">
    <div v-if="shownav" class="my-popup-component" @click.self="shownav=false" style="height:100vh;width:100vw">
        <div :class="{show : shownav}" class="position-absolute animate__animated animate__fadeInRight p-0" style="width:80px;height:100%;right:0px;display:none;z-index:9999;background-color:black;top:0;">
            <div class="position-relative pt-4 h-100  main-website px-0">
                <button class="trans_btn position-absolute p-0" @click="shownav=false"
                    style="right:10px;top:5px;font-size:25px">
                    <i class="fa-solid fa-xmark" style="color:#FC9C88;"></i>
                </button>
                <router-link class="mt-4" style="background:none;" to="/">
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
                    <button class="w-100 border-0 mx-2 my-2 brds-2 join1" style="height:40px;width:60% !important;">JOIN  NOW</button>
                </router-link>
            </div>
        </div>
    </div>
    <div style="overflow:hidden;"  :class="{fixedposition:position}">
        <div class="row" style="--bs-gutter-x:0;">
            <div class="col-12 main position-relative">
                <div class="col-12 col-sm-11 mt-4 mx-auto" style="min-height:100px;">
                    <div class="h-100 float-start logo-div ms-0 ms-sm-3">
                        <img src="/cms-assets/images/navbar-topbar/logo-by-dina-1.png" class="logo float-start">
                    </div>
                    <div class="float-start  d-flex flex-wrap px-0 px-sm-4" style="height:100px;align-items:center;width:calc(100% - 116px)">
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
                                <button @click.self="shownav=!shownav,position=!position" class="border-0 mx-1 main-btn p-0" style="color:#FC9C88">
                                    <img @click.self="shownav=!shownav,position=!position" src="/cms-assets/images/navbar-topbar/menu.png" alt="" style="height:30px">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 pt-0 ms-0 ms-md-4 ps-2 ps-sm-4 position-relative" style="height:calc(100% - 150px)">
                    <div class="col-11 col-md-8 col-lg-6 col-xl-6 ms-md-4 ps-0 ps-sm-4 pt-0 pt-md-4 mt-4">
                        <div class="w-100 px-1 mt-4 pt-4">
                            <div class="brds-2 px-1 textdiv" style="max-width:430px;width:fit-content;background-color:#C7BEBE">
                                <p class="px-2 brds-2 prim_bg float-start mb-0 main-text" style="margin-top:0.5%;height:90%;">THE BEST</p>
                                <p class="px-2 float-start mb-0 main-text" style="height:98%;color:#FFFFFF;margin-top:2px;">FITNESS TRAINER</p>
                            </div>
                        </div>
                        <p class="my-3 f-60 mb-0" style="color:white;font-weight:bold;">WORK HARD TO GET BETTER LIFE</p>
                        <button class="brds-2 px-4 py-0 prim_bg border-0" style="font-size:20px;color:black !important;">GET STARTED</button>
                    </div>
                </div>
            </div>
            <div class="about-div px-1 px-sm-4 py-4 col-12 position-relative">
                <img src="/cms-assets/images/mainWebsite/Group16757.png" class="d-none d-md-block" alt="" style="position:absolute;height:300px;right:15%;top:10%;z-index:-9;">
                <img src="/cms-assets/images/mainWebsite/Group16757.png" class="d-none d-md-block" alt="" style="position:absolute;height:300px;right:-50px;bottom:-200px;">
                <div class="col-12 col-sm-9 ps-2 ps-sm-4 h-100 float-start">
                    <div class="col-11">
                        <button class="prim_bg my-1 px-4 py-1 brds-3 border-0 f-20" style="font-weight:bold;color:black;">ABOUT ME</button><br>
                        <p class="my-1 col-12 col-sm-11 f-40" style="font-weight:bold;">WE ALL HAVE A STORY,<br> HERE IS MINE</p>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="col-md-6 col-12 float-start">
                                <p class="my-1 f-20" style="color:#8F8787;">In life, our overall wellbeing is dependent
                                on two things: what we feed our body and
                                what we feed our mind and soul. Things
                                like stress, negative thought patterns, bad
                                habits, and toxic people have a way of
                                keeping us from being our authentic selves
                                and living the most vibrant lives imaginable.
                                Tackling all this head-on all on your own
                                could seem difficult, but in times like these,
                                what you really need is a trustworthy ally
                                whom you can learn from, and that wants
                                nothing more than to see you thrive.
                                </p>
                                <button class="my-3 px-4 py-1 brds-3 f-20" style="font-weight:bold;border:1px solid #000000;background-color:transparent;color:black;">READ MORE</button>
                            </div>
                            <div class="col-12 col-md-5 float-start">
                                <img src="/cms-assets/images/mainWebsite/MaskGroup1.png" alt="" class="img-fluid float-md-end float-start col-5 col-md-10 col-lg-9">
                                <img src="/cms-assets/images/mainWebsite/MaskGroup2.png" alt="" class="img-fluid float-end d-md-none col-5 col-md-10 col-lg-9">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-none d-md-block col-3 h-100 float-start position-relative" style="text-align:center;">
                    <img src="/cms-assets/images/mainWebsite/MaskGroup2.png" alt="" class="img-fluid col-12 col-md-11 col-lg-10" style="margin-top:200px">
                </div>
            </div>
            <div class="col-12 pb-1 pb-sm-4 mx-auto subscription">
                <div class="col-12 col-sm-11 mx-auto mt-1 mt-sm-4 py-1 py-sm-4" style="text-align:center;">
                    <button class="px-4 py-1 mt-4 prim_bg brds-1 border-0 f-20" style="font-weight:bold;color:black;">SUBSCRIPTIONS PLANS</button>
                    <p class="col-11 col-sm-9 mt-4 mx-auto f-40" style="font-weight:bold;">12 WEEK HOME TRANSFORMATION WORKOUT PROGRAM & ONLINE COACHING</p>
                    <div class="col-11 col-sm-6 mx-auto brds-1" style="height:3px;background-color:#C5C5C5"></div>
                </div>
                <div class="col-11 py-1 py-sm-4 mx-auto">
                    <p class="col-12 my-1 my-sm-3 f-40" style="font-weight:bold;">01 Month</p>
                    <div class="col-12 mx-auto brds-1" style="height:3px;background-color:#C5C5C5"></div>
                    <div class="row">
                        <div class="col-12 pb-3" style="min-height:80px;">
                            <div class="col-9 py-2 float-start mt-4 f-20" >
                                Home workout Programs & Coaching /برامج التمارين المنزلية و التدريب اونلاي
                            </div>
                            <div class="col-3 float-start mt-4">
                                <button class="px-1 py-1 px-sm-4 prim_bg brds-1 border-0 f-20" style="font-weight:bold;color:black;">$29.99 USD / month</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mx-auto brds-1" style="height:3px;background-color:#C5C5C5"></div>
                    <div class="col-12" style="text-align:center;">
                        <button class="brds-1 px-4 py-2 mt-4 col-8 col-sm-6 col-md-4" style="border:1px solid #FC9C88;background-color:transparent;font-size:20px;color:black;">BUY NOW</button>
                    </div>
                </div>
            </div>
            <div class="col-12 pb-4 mx-auto fitnessLife position-relative">
                <div class="col-12 mt-3 d-flex flex-wrap justify-content-center">
                    <button class="border-0 px-4 py-1 f-30" style="background-color:transparent;color:white;font-weight:bold;">English</button>
                    <button class="border-0 px-4 py-1 f-30" style="background-color:transparent;color:#FC9C88;font-weight:bold;">Arabic</button>
                </div>
                <div class="col-12">
                    <p class="mx-auto col-12 my-2 f-30" style="color:#FC9C88;text-align:center;font-weight:bold">Explore Fitness Life</p>
                    <div class="mx-auto col-11 col-sm-9 col-md-8">
                        <iframe style="width:100%;height:400px" src="https://www.youtube.com/embed/uyrIQvEjtGA" controls ></iframe>
                    </div>
                    <div class="mx-auto my-2 col-6 col-sm-4 col-md-3 col-xl-2">
                        <button class="col-12 f-20 brds-2 px-4 py-2" style="border:1px solid #FC9C88;color:#FC9C88;background-color:transparent;">WATCH MORE</button>
                    </div>
                    <!-- <p class="col-10 col-sm-8 col-md-6 mx-auto my-2 f-20" style="text-align:center;color:white;">Application Requires An English Default Mobile Phone For Full Function</p> -->
                </div>
                <img src="/cms-assets/images/mainWebsite/Group16757.png" class="d-none d-md-block" style="position:absolute;height:300px;left:-110px;bottom:-90px;">
                <img src="/cms-assets/images/mainWebsite/Group16757.png" class="d-none d-md-block" style="position:absolute;height:300px;right:-80px;bottom:-200px;z-index:0;">
            </div>
            <div class="col-12 py-4 equipment">
                <div class="mx-auto col-8 mt-4 pt-4 col-sm-8 col-md-6 col-xl-4">
                    <button class="f-20 py-2 col-12 brds-2 border-0 prim_bg" style="font-weight:bold;color:black;">FITNESS EQUIPMENT REQUIRED</button>
                </div>
                <div class="col-12 pb-4 col-sm-11 col-md-10 col-xl-8 mx-auto">
                    <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                            <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2"></button>
                        </div>
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                            <img src="/cms-assets/images/mainWebsite/Group16758.png" class="d-block w-100" alt="...">
                            </div>
                            <div class="carousel-item">
                            <img src="/cms-assets/images/mainWebsite/Group16759.png" class="d-block w-100" alt="...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 px-2 px-sm-4 py-4 app">
                <div class="col-12 col-md-7 mt-4 ps-1 ps-sm-4 float-start">
                    <button class="prim_bg py-1 my-2 col-10 col-md-6 f-30 border-0 brds-2" style="color:black;" >DOWNLOAD APP</button>
                    <p class="mb-0 my-3 col-11 f-20" style="color:white;">Once You Register You Will Receive 2 Emails To Set Up
                        Your Account And Download The Application Where You
                        Will View Your Program
                        Once Your Set Up Your Profile You Will Fill Out A
                        Form About Your Health Background And Goals
                        Then Dina Will Set Upon The Right Program That
                        Suits You.
                    </p>
                    <div class="col-12 pointer my-3">
                        <img src="/cms-assets/images/mainWebsite/Group16732.png" class="col-5 col-sm-4 col-md-3 float-start" alt="">
                        <img src="/cms-assets/images/mainWebsite/Group16733.png" class="col-5 col-sm-4 col-md-3 ms-4 float-start" alt="">
                    </div>
                </div>
                <div class="col-4 float-start d-none d-md-block">
                    <img src="/cms-assets/images/mainWebsite/Group16760.png" class="col-12 col-xl-10" alt="">
                </div>
            </div>
            <Footer @click="scrollToTop()"/>
        </div>
    </div>
</template>
<script>
import Footer from './components/footer.vue';
export default {
    components: { Footer },
    data() {
        return {
            shownav: false,
            position: false,
        }
    },
    mounted() {
        this.scrollToTop();
    },
    methods: {
        scrollToTop() {
            window.scrollTo(0, 0)
        },
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

.main {
    height: 700px;
    background-image: url(/cms-assets/images/navbar-topbar/Group16760.png);
    background-position: center;
    background-size: cover;
}

.subscription {
    min-height: 400px;
    background-image: url(/cms-assets/images/mainWebsite/sub.png);
    background-position: center;
    background-size: 100% 100%;
}

.fitnessLife {
    min-height: 400px;
    background-image: url(/cms-assets/images/mainWebsite/Group16709.png);
    background-position: center;
    background-size: 100% 100%;
}

.equipment {
    min-height: 300px;
    background-image: url(/cms-assets/images/mainWebsite/Rectangle2598.png);
    background-position: center;
    background-size: 100% 100%;
}

.app {
    min-height: 370px;
    background-image: url(/cms-assets/images/mainWebsite/Group16709.png);
    background-position: center;
    background-size: 100% 100%;
}

.main-btn {
    background-color: transparent;
    font-size: 17px;
    font-weight: bold;
    color: white !important;
}

.main-btn:hover {
    color: #FC9C88 !important;
}

.main-btn1 {
    background-color: transparent;
    font-size: 18px;
    text-align: left;
    color: white !important;
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
    background-color: #10161B;
    color: #FC9C88;
    width: 150px;
    height: 40px;
}

.join1 {
    font-size: 18px;
    font-weight: bold;
    background-color: #FC9C88;
    color: black;
    width: 150px;
    height: 40px;
}

.show {
    display: block !important;
    width: 250px !important;
}

.main-text {
    font-size: 25px;
    font-weight: bold;
}

.textdiv {
    height: 40px;
}

.about-div {
    min-height: 500px;
}

.f-40 {
    font-size: 40px;
}

.f-30 {
    font-size: 30px;
}

.f-20 {
    font-size: 20px;
}

.f-60 {
    font-size: 60px;
}

.carousel-indicators [data-bs-target] {
    height: 15px;
    width: 15px;
    border-radius: 10px;
    border: 1px solid;
}

.carousel-indicators [data-bs-target][data-v-4bc28aa0] {
    border-color: black;
}

.carousel-indicators .active {
    background-color: black;
}

.carousel-indicators {
    bottom: -20px;
}

.logo {
    height: 100px;
    width: 100px;
}

.logo-div {
    text-align: end;
    width: 100px;
}

.fixedposition {
    position: fixed;
}

@media screen and (max-width: 770px) {
    .uncolapsed-nav {
        display: none;
    }

    .colapsed-nav {
        display: block;
    }

    .main {
        background-image: url(/cms-assets/images/navbar-topbar/Group16759.png);
        height: 500px;
    }

    .f-20 {
        font-size: 15px;
    }

    .f-30 {
        font-size: 20px;
    }

    .f-40 {
        font-size: 35px;
    }
}

@media screen and (max-width:520px) {
    .f-60 {
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


    .main {
        height: 450px;
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

    .f-40 {
        font-size: 30px;
    }

    .f-60 {
        font-size: 40px;
    }

    .carousel-indicators {
        bottom: -40px;
    }
}
</style>
