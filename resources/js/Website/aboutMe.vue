<template lang="">
    <div style="overflow:hidden" :class="{fixedposition:position}">
        <Header :detail="'WE ALL HAVE'" :title1="' A STORY ,'" :title2="'HERE IS MINE'" :discription="'In life, our overall wellbeing is dependent on two things:'" :page="'about'"/>
        <div class="col-12 px-2 col-sm-11 col-md-10 py-4 my-4 mx-auto" style="min-height:250px;">
            <img src="/cms-assets/images/mainWebsite/MaskGroup6.png" style="width:49%;margin-right:1%;" alt="">
            <img src="/cms-assets/images/mainWebsite/MaskGroup7.png" style="width:49%;margin-left:1%;" alt="">
        </div>
        <div class="row">
            <div class="col-12 px-3 px-sm-0 col-sm-11 col-md-10 py-0 py-sm-4 my-0 my-sm-4 mx-auto position-relative" style="min-height:300px;">
                <p class="col-7 col-md-6 ms-1 float-start f-20">What we feed our body and what we feed our mind and soul.
                    Things like stress, negative thought patterns, bad habits, and
                    toxic people have a way of keeping us from being
                    our authentic selves and living the most vibrant
                    lives imaginable. Tackling all this head-on all on
                    your own could seem difficult, but in times like
                    these, what you really need is a trustworthy ally
                    whom you can learn from, and that wants nothing
                    more than to see you thrive.
                </p>
                <div class="col-4 me-2 float-end position-relative">
                    <img src="/cms-assets/images/mainWebsite/MaskGroupAbout1.png" class="img-fluid" alt="">
                    <img src="/cms-assets/images/mainWebsite/MaskGroup8.png" class="img-fluid position-absolute d-none d-md-block" style="left:-40%;top:100px;z-index:9;width:80%;" alt="">
                </div>
                <img src="/cms-assets/images/mainWebsite/fitness.png" class="img-fluid w-80 position-absolute" style="left:0px;bottom:50px" alt="">
            </div>
        </div>
        <div class="row">
            <div class="col-11 px-1 mx-auto col-sm-11 col-md-10  py-4 my-4 mx-auto position-relative">
                <div class="col-12 px-0 col-sm-10 col-md-9 float-start fitnessCoach">
                </div>
                <p class="mb-0 position-absolute d-none d-sm-block" style="color:white;font-weight:bold;font-size:70px;right:0px;">I’m D<span style="color:black;">ina, A <br> </span>Heal<span style="color:black">th & <br> </span><span style="color:#FC9C88"> Fitness Coach</span></p>
                <p class="mb-0 px-3 position-absolute d-block d-sm-none" style="color:white;font-weight:bold;font-size:50px;right:0px;">I’m D<span style="color:black;">ina, A <br> </span>Heal<span style="color:black">th & <br> </span><span style="color:#FC9C88"> Fitness Coach</span></p>
            </div>
        </div>
        <div class="row">
            <div class="col-12 px-3 px-sm-0 col-sm-11 col-md-10 py-0 py-sm-4 my-0 my-sm-4 mx-auto position-relative" style="min-height:300px;">
                <div class="col-4 ps-1 float-start">
                    <img src="/cms-assets/images/mainWebsite/MaskGroup9.png" class="img-fluid" alt="">
                </div>
                <p class="mb-0 ps-2 f-20 col-8 float-end">
                    This is where I step in: I’m Dina, a Health & Fitness Coach,
                    and a Motivational Speaker. My vision is to impart people
                    with the motivation, tools, and guidance they need to live
                    a happier, healthier lifestyle. Moreover, I’m here to help
                    you achieve that total life balance between body, mind,
                    and spirit, so that you can put your best foot forward
                    every single day of your life.
                </p>
                <img src="/cms-assets/images/mainWebsite/20180128758462315-1.png" class="img-fluid position-absolute d-none d-xl-block" style="width:75%;bottom:-50px;right:0;" alt="">
            </div>
        </div>
        <div class="row mb-4 mt-0 pt-0 mt-xl-4 pt-xl-4 ">
            <div class="col-12 px-sm-0 col-sm-11 col-md-10 py-0 py-sm-4 my-0 my-sm-4 mx-auto position-relative">
                <p class="my-4 px-2 px-sm-0 col-12 f-20" style="text-align:center;">
                    Growing up, I was an extremely energetic child and was in great shape because of it.
                    But in my 20s, I gained a whopping 35 kilos because I was feeling stressed. That really
                    took a toll on my confidence, but I remained determined and ultimately took matters
                    into my own hands.
                </p>
                <p class="my-4 px-2 px-sm-0 col-12 f-20" style="text-align:center;">
                    However, you and I both know, it’s so much easier said than done. I did end up losing
                    all that weight, but I’ll admit that I was terrified about gaining it back, so I started
                    restricting my food intake and doing way too much cardio. And I can’t tell you how
                    many hours I spent starring at a mirror criticizing myself! Unfortunately, I ended up
                    becoming bulimic.
                </p>
            </div>
        </div>
        <div class="row px-1">
            <div class="col-11 px-0 col-sm-11 col-md-10 py-0 py-sm-4 my-4 mx-auto position-relative end-div">
                <p class="my-4 px-2 px-sm-2  col-11 col-sm-10 col-md-9 col-xl-8 f-20 float-end" style="text-align:end;color:white;">
                    Not all hope was lost, though. Remember that determination
                    I was talking about? I knew I was destined for something more;
                    something greater than myself.
                    <br>
                    <br>
                    So I overcame those staggering hurdles—each and every one.
                    And in my healing, I decided that this acquired wisdom wasn’t
                    meant to be kept to myself; it was meant to be shared with as
                    many people as possible. So here I am today, more empowered
                    than ever, to assist you in your personal transformation.
                    Trust me, if I did it, so can you!
                </p>
            </div>
        </div>
        <Footer/>
    </div>
</template>
<script>
import Header from './components/header.vue';
import Footer from './components/footer.vue';
export default {
    components: { Header, Footer },
    data() {
        return {
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
        changePosition() {
            this.position = !this.position;
        },
    }
}
</script>
<style scoped>
.fitnessCoach {
    min-height: 450px;
    background-image: url(/cms-assets/images/mainWebsite/Group167572.png);
    background-position: left;
    background-size: 100% 100%;
    background-repeat: no-repeat;
}

.end-div {
    min-height: 400px;
    background-image: url(/cms-assets/images/mainWebsite/Group167581.png);
    background-position: center;
    background-size: 100% 100%;
}

.fixedposition {
    position: fixed;
}

@media screen and (max-width: 400px) {
    .fitnessCoach {
        min-height: 380px;
        background-size: 100% 100%;
    }

    .end-div {
        min-height: 300px;
    }
}
</style>
