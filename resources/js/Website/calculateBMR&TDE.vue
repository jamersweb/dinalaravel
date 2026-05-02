<template lang="">
    <div style="overflow:hidden">
        <Header :detail="'CALCULATING YOUR'" :title="'BMR & TDEE'" :discription="'In life, our overall wellbeing is dependent on two things:'"/>
        <div class="row position-relative">
            <div class="col-12 px-4 px-sm-0 col-sm-11 col-md-10 py-4 my-4 mx-auto position-relative">
                <iframe style="width:100%;height:400px" src="https://www.youtube.com/embed/uyrIQvEjtGA" controls ></iframe>
            </div>
            <img src="/cms-assets/images/mainWebsite/claculatebmr&tdee/Group16759.png" class="position-absolute" style="height:200px;width:300px;bottom:50px;left:-120px;" alt="">
        </div>
        <div class="row">
            <div class="col-12 px-4 px-sm-0 col-sm-11 col-md-10 col-xl-9 py-4 my-0 my-sm-4 mx-auto position-relative">
                <div class="col-10 mx-auto mb-4 brds-1" style="text-align:center;">
                    <button class="px-4 py-2 border-0 f-30" style="color:white;background-color:#10161B;">CLICK HERE TO CALCULATE YOUR BMR & TDEE</button>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-11 mx-auto">
                <div class="col-6 float-start col-md-4 my-2 col-xl-3 px-2">
                    <div class=" tsl brds-2 py-4" style="text-align:center;min-height:200px;">
                        <p class="mb-0 f-20" style="color:black;">STEP 01</p>
                        <div class="mx-auto my-2 col-10" style="height:2px;background-color:#FC9C88;"></div>
                        <p class="mb-0 f-20 mt-4" style="color:#C5C5C5;">CALCULATE YOUR</p>
                        <p class="mb-0 f-20" style="color:#F29C88;">BMR & TDEE</p>
                    </div>
                </div>
                <div class="col-6 float-start col-md-4 my-2 col-xl-3 px-2">
                    <div class=" tsl brds-2 py-4" style="text-align:center;min-height:200px;">
                        <p class="mb-0 f-20" style="color:black;">STEP 02</p>
                        <div class="mx-auto my-2 col-10" style="height:2px;background-color:#FC9C88;"></div>
                        <p class="mb-0 f-20 mt-4" style="color:#C5C5C5;">DOWNLOAD</p>
                        <p class="mb-0 f-20" style="color:#F29C88;">MYFITNESSPAL</p>
                    </div>
                </div>
                <div class="col-6 float-start col-md-4 my-2 col-xl-3 px-2">
                    <div class=" tsl brds-2 py-4" style="text-align:center;min-height:200px;">
                        <p class="mb-0 f-20" style="color:black;">STEP 03</p>
                        <div class="mx-auto my-2 col-10" style="height:2px;background-color:#FC9C88;"></div>
                        <p class="mb-0 f-20 mt-4" style="color:#C5C5C5;">CALCULATE WHAT YOU</p>
                        <p class="mb-0 f-20" style="color:#F29C88;">HAVE BEEN EATING</p>
                    </div>
                </div>
                <div class="col-6 float-start col-md-4 my-2 col-xl-3 px-2">
                    <div class=" tsl brds-2 py-4" style="text-align:center;min-height:200px;">
                        <p class="mb-0 f-20" style="color:black;">STEP 04</p>
                        <div class="mx-auto my-2 col-10" style="height:2px;background-color:#FC9C88;"></div>
                        <p class="mb-0 f-20 mt-4" style="color:#C5C5C5;">ADJUST CALORIES &</p>
                        <p class="mb-0 f-20" style="color:#F29C88;">MACROS TO YOUR GOAL</p>
                    </div>
                </div>
            </div>
        </div>
        <Footer/>
    </div>
</template>
<script>
import Header from './components/header.vue';
import Footer from './components/footer.vue';
export default {
    components: { Header, Footer }
}
</script>
<style lang="">

</style>
