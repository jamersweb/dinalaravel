<template lang="">
    <div class="my-popup-component">
        <div class="brds-4 px-4 w-60 position-relative" style="background-color:white;height:80vh;overflow-y:auto;">
            <button class="trans_btn position-absolute" @click="quitComponent"
                style="right:10px;top:5px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <p class="m-0 mt-4" style="font-size:30px;">Settings for</p>
            <p class="m-0" style="font-size:16px;color:#B1B0B0;">Notify me about</p>
            <input type="radio" name="" id="">
            <label style="font-size:12px;" for="">All Posts And Activities</label><br>
            <input type="radio" name="" id="">
            <label style="font-size:12px;" for="">Only Mentions and Reactions</label><br>
            <input type="radio" name="" id="">
            <label style="font-size:12px;" for="">None</label><br>
            <p class="m-0 mt-3" style="font-size:16px;color:#B1B0B0;">Auto-post when a client</p>
            <div class="w-100 my-1" style="height:20px;">
                <div class="float-start" style="height:15px;width:30px;border-radius:10px;border:1px solid #B1B0B0;position:relative">
                    <div style="position:absolute;height:13px;width:13px;border-radius:10px;background-color:#F2A18C;"></div>
                </div>
                <p class="float-start m-0 ms-2" style="font-size:12px;">Completes a Workout</p>
            </div>
            <div class="w-100 my-1" style="height:20px;">
                <div class="float-start" style="height:15px;width:30px;border-radius:10px;border:1px solid #B1B0B0;position:relative">
                    <div style="position:absolute;height:13px;width:13px;border-radius:10px;background-color:#F2A18C;"></div>
                </div>
                <p class="float-start m-0 ms-2" style="font-size:12px;">Completes a Cardio Activity</p>
            </div>
            <div class="w-100 my-1" style="height:20px;">
                <div class="float-start" style="height:15px;width:30px;border-radius:10px;border:1px solid #B1B0B0;position:relative">
                    <div style="position:absolute;height:13px;width:13px;border-radius:10px;background-color:#F2A18C;"></div>
                </div>
                <p class="float-start m-0 ms-2" style="font-size:12px;">Hits their Daily Nutrition Goal</p>
            </div>
            <div class="w-100 my-1" style="height:20px;">
                <div class="float-start" style="height:15px;width:30px;border-radius:10px;border:1px solid #B1B0B0;position:relative">
                    <div style="position:absolute;height:13px;width:13px;border-radius:10px;background-color:#F2A18C;"></div>
                </div>
                <p class="float-start m-0 ms-2" style="font-size:12px;">Hits their Fitness Goal</p>
            </div>
            <div class="w-100 my-1" style="height:20px;">
                <div class="float-start" style="height:15px;width:30px;border-radius:10px;border:1px solid #B1B0B0;position:relative">
                    <div style="position:absolute;height:13px;width:13px;border-radius:10px;background-color:#F2A18C;"></div>
                </div>
                <p class="float-start m-0 ms-2" style="font-size:12px;">Hits their Habit Streak</p>
            </div>
            <p class="m-0 my-2" style="font-size:16px;color:#B1B0B0;">Learn more about using auto-posts to boost accountability</p>
            <div class="w-90 mx-auto my-2" style="background-color:#E7E7E7;height:1px;"></div>
            <p class="m-0 my-2" style="font-size:16px;color:#B1B0B0;">Who can post?</p>
            <select class="brds-1 my-2" style="width:150px;height:35px;border:1px solid #C5C5C5;color:#C5C5C5;" name="" id="">
                <option value="">Anyone</option>
                <option value="">Admin</option>
            </select>
            <div class="w-100 my-2">
                <button class="prim_bg px-4 py-1 brds-2 border-0">Save</button>
            </div>
        </div>
    </div>
</template>
<script>
export default {

}
</script>
<style lang="">

</style>
