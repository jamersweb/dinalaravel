<template lang="">
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <div v-if="userDetails!=null" @click.self="quitComponent" class="my-popup-component" style="overflow:auto;">
        <div class="brds-3 pb-3" style="width:80%;height:85%;background-color:white;overflow-y:auto;position:relative;">
            <button class="trans_btn float-end" @click="quitComponent" style="right:15px;top:10px;font-size:25px">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="ps-3" style="width:100%;height:60px;background-color:#EEEEEE;border-top-left-radius:15px;border-top-right-radius:15px;">
                <button class="top-btn" :class="{ active: summary}" style="border-top-left-radius:10px;border-bottom-left-radius:10px;border-left: 1px solid #C5C5C5;" @click="showSummary">Summary</button>
                <button class="top-btn" :class="{ active: consultation}" @click="showConsultation">Consultation</button>
                <button v-if="logInDetails.role==='Admin'" class="top-btn" :class="{ active: attachments}" @click="showAttachments">Attachments</button>
                <!-- <buttosn class="top-btn" :class="{ active: sales}" @click="showSales">Sales</button> -->
                <button class="top-btn" :class="{ active: invoices}" @click="showInvoices" style="border-top-right-radius:10px;border-bottom-right-radius:10px;">Invoices</button>
            </div>
            <div style="width:100%;min-height:100px;position:relative;">
                <img v-if="userDetails.image" :src="userDetails.image" alt="Error" style="width:80px;height:80px;margin-top:10px;margin-left:20px;float:left;border-radius:40px;">
                <img v-else src="/images/Group55795.png" alt="Error" style="width:80px;height:80px;margin-top:10px;margin-left:20px;float:left;border-radius:40px;">
                <div class="float-start mt-2 ms-4">
                    <p class="mb-0 ms-2" style="font-size:37px;">{{userDetails.full_name}}</p>
                    <p class="mb-0 ms-3" style="font-size:14px">{{userDetails.subscription}}</p>
                </div>
                <!-- <button class="float-start brds-4 tslin" style="position:absolute;top:30px;right:90px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;">Edit</button>
                <button class="client-dots float-start"></button> -->
                <div v-if="summary" class="dropdown float-end">
                    <button class="btn client-dots" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li @click="assignTagsShow"><a class="dropdown-item" href="#">Assign Tags</a></li>
                        <li v-if="userDetails.status=='deactive'" @click="changeStatus()"><a class="dropdown-item" href="javascript:void(0)">Activate</a></li>
                        <li v-else-if="userDetails.status=='active'" @click="changeStatus()"><a class="dropdown-item" href="javascript:void(0)">Deactivate</a></li>
                        <li v-else ><a class="dropdown-item" href="javascript:void(0)">-- Pending --</a></li>
                    </ul>
                </div>
            </div>
            <div v-if="summary" class="mx-auto" style="width:95%;min-height:370px">
                <div v-if="summary1">
                    <div style="width:100%;min-height:150px;" class="d-flex flex-wrap justify-content-around">
                        <div class="brds-2 tsl " style="width:32%;height:150px;">
                            <div class="mt-2" style="width:100%;height:45px;position:relative;">
                                <p class="float-start position-absolute m-0" style="font-size:14px;width:60%;top:5px;left:10px;">Recently earned badges:</p>
                                <!-- <p class="float-end position-absolute m-0" style="font-size:14px;width:20%;color:#B1B0B0;cursor:pointer;top:5px;right:5px">View All</p> -->
                            </div>
                            <div v-if="userDetails.badges.length!==0" class="ps-0" style="width:100%;overflow:auto;height:100px;display:flex;justify-content:center;">
                                <div style="width:60px;text-align:center;" class="float-start ms-1" v-for="(item, index) in userDetails.badges" :key="index">
                                    <img src="/images/Group16569.png" alt="Error" style="height:40px;width:40px;">
                                    <p style="font-size:7px;" class="mx-auto mb-0">{{item.badge_name}}</p>
                                </div>
                            </div>
                            <p v-else class="ms-3" style="color:#B1B0B0;">No Badges to show</p>
                        </div>
                        <div class="brds-2 tsl  ms-3" style="width:32%;height:150px;display:flex;justify-content:center;">
                            <div class="mt-4" style="width:60px;text-align:center;">
                                <img src="/images/Group16188.png" alt="" style="width:40px">
                                <p style="font-size:9px;margin-bottom:5px">Total workouts</p>
                                <p style="font-size:10px;color:#B1B0B0;">{{userDetails.totalWorkouts}}</p>
                            </div>
                        </div>
                        <div class="brds-2 tsl  ms-3 p-2" style="width:32%;overflow-y:auto;">
                            <p v-if="clientTags.names.length<1">No Tags to show</p>
                            <p v-for="(item, index) in clientTags.names" :key="index" class="px-2 float-start m-1" style="background-color:#F2A18C;border-radius:20px">{{item}}</p>
                        </div>
                        <!-- <div class="brds-2 tsl float-start ms-3" style="width:37%;height:150px;">
                            <img src="/images/Group16361.png" alt="Error" class="m-3 float-start" style="height:120px">
                            <div class="float-start mt-3 pt-3">
                                <p style="font-size:29px;margin:0px;">Fitbit</p>
                                <p style="font-size:14px;margin:0px;">Ask to Connect</p>
                            </div>
                        </div> -->
                    </div>
                    <div style="width:100%;min-height:300px;padding-top:15px;display:flex;justify-content:center">
                        <div class="brds-3 tsl float-start me-2" style="width:430px;min-height:270px;">
                            <div class="w-80 mx-auto mt-3" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Email:</p>
                                <p class="m-0 p2">{{userDetails.email}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Phone:</p>
                                <p class="m-0 p2">{{userDetails.phone}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Date of birth:</p>
                                <p class="m-0 p2">{{userDetails.dob}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Height:</p>
                                <p class="m-0 p2">{{userDetails.height}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Sex:</p>
                                <p class="m-0 p2">{{userDetails.gender}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Activity level:</p>
                                <p class="m-0 p2">{{userDetails.activitylevel}}</p>
                            </div>
                        </div>
                        <div class="brds-3 tsl float-start ms-2" style="width:430px;min-height:270px;">
                            <div class="w-80 mx-auto mt-3" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Added on:</p>
                                <p class="m-0 p2">{{userDetails.added_on}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Setup account on:</p>
                                <p class="m-0 p2">{{userDetails.setup_on}}</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Timezone:</p>
                                <p class="m-0 p2">{{userDetails.timezone}}</p>
                            </div>
                            <div v-if="userDetails.units" class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between">
                                <p class="m-0 pt-1 p1">Units:</p>
                                <p class="m-0 p2">Weight ({{userDetails.units.weight_unit}});Distance ({{userDetails.units.distance_unit}});Body Stats ({{userDetails.units.body_measures}})</p>
                            </div>
                            <div class="w-80 mx-auto mt-1" style="min-height:30px;display:flex;justify-content:space-between;margin-bottom:10px">
                                <p class="m-0 pt-1 p1">Meal workflow:</p>
                                <p class="m-0 p2">{{userDetails.meal_workflow}}</p>
                            </div>
                        </div>
                    </div>
                    <!-- <div style="width:100%;height:50px;">
                        <div class="position-relative mx-auto" style="height:50px;width:130px;">
                            <div style="height:5px;width:80px;background-color:black;position:absolute;bottom:20px;border-radius:10px;left:0px;cursor:pointer;"></div>
                            <div @click="showSummary2" style="height:5px;width:40px;background-color:#545352;position:absolute;bottom:20px;border-radius:10px;right:0px;cursor:pointer;"></div>
                        </div>
                    </div> -->
                </div>
                <div v-if="summary2">
                    <div class="tsl brds-2 mx-auto" style="width:97%;min-height:440px;">
                        <div class="p-3" style="width:100%;height:100px;display:flex;justify-content:space-between;">
                            <div>
                                <p style="font-size:29px;margin:0px;">Mariyan's Program</p>
                                <p style="font-size:14px;color:#B1B0B0;margin:0px;">Current training plan: <span style="text-transform:capitalize;">{{userDetails.training_plan}}</span></p>
                            </div>
                            <div>
                                <p style="font-size:29px;color:#B1B0B0;margin:0px;float:left;">1</p>
                                <button class="add-btn"></button>
                                <p style="font-size:29px;color:#B1B0B0;margin:0px;float:left;">Main</p>
                            </div>
                        </div>
                        <div style="display:flex;width:100%;justify-content:center;flex-wrap:wrap;">
                            <!-- <div class="brds-2 tsl position-relative" style="width:470px;height:160px;margin:0px 0px 10px 10px">
                                <div v-if="userDetails.meal_workflow==null">
                                    <p style="font-size:19px;margin:10px 0px 0px 10px;">Meal Plan</p>
                                    <p style="font-size:16px;color:#B1B0B0;margin:0px 0px 0px 10px;">No current meal plan</p>
                                </div>
                                <div v-else>
                                    <p style="font-size:19px;margin:10px 0px 0px 10px;">Meal Plan</p>
                                    <p style="font-size:10px;position:absolute;top:17px;right:120px;">APR-MAY 2022</p>
                                    <button class="brds-1 btn-1">T</button>
                                    <button class="btn-2" style="right:30px">&lt;</button>
                                    <button class="btn-2" style="right:15px">&gt;</button>
                                    <div class="mx-auto" style="height:110px;width:100%;justify-content:center;margin-top:5px;">
                                        <div class="meal-card">
                                            <img src="/images/Image11.png" alt="Error" style="width:75px;height:55px;margin:7px 0px 0px 10px;">
                                            <p style="font-size:8px;margin:0px 0px 2px 5px;">Tuesday</p>
                                            <p style="font-size:8px;height:15px;width:30px;border:1px solid #FC9C88;color:#FC9C88;border-radius:10px;margin:0px 0px 2px 5px;padding:1px 0px 0px 3px;">Lunch</p>
                                            <p style="font-size:8px;margin:0px 0px 0px 5px;">My Today Lunch Meal</p>
                                        </div>
                                        <div class="meal-card">
                                            <img src="/images/Image11.png" alt="Error" style="width:75px;height:55px;margin:7px 0px 0px 10px;">
                                            <p style="font-size:8px;margin:0px 0px 2px 5px;">Tuesday</p>
                                            <p style="font-size:8px;height:15px;width:30px;border:1px solid #FC9C88;color:#FC9C88;border-radius:10px;margin:0px 0px 2px 5px;padding:1px 0px 0px 3px;">Lunch</p>
                                            <p style="font-size:8px;margin:0px 0px 0px 5px;">My Today Lunch Meal</p>
                                        </div>
                                        <div class="meal-card">
                                            <img src="/images/Image11.png" alt="Error" style="width:75px;height:55px;margin:7px 0px 0px 10px;">
                                            <p style="font-size:8px;margin:0px 0px 2px 5px;">Tuesday</p>
                                            <p style="font-size:8px;height:15px;width:30px;border:1px solid #FC9C88;color:#FC9C88;border-radius:10px;margin:0px 0px 2px 5px;padding:1px 0px 0px 3px;">Lunch</p>
                                            <p style="font-size:8px;margin:0px 0px 0px 5px;">My Today Lunch Meal</p>
                                        </div>
                                        <div class="meal-card">
                                            <img src="/images/Image11.png" alt="Error" style="width:75px;height:55px;margin:7px 0px 0px 10px;">
                                            <p style="font-size:8px;margin:0px 0px 2px 5px;">Tuesday</p>
                                            <p style="font-size:8px;height:15px;width:30px;border:1px solid #FC9C88;color:#FC9C88;border-radius:10px;margin:0px 0px 2px 5px;padding:1px 0px 0px 3px;">Lunch</p>
                                            <p style="font-size:8px;margin:0px 0px 0px 5px;">My Today Lunch Meal</p>
                                        </div>
                                    </div>
                                </div>
                            </div> -->
                            <div class="brds-2 tsl position-relative p-2" style="width:470px;height:160px;margin:0px 0px 10px 10px">
                                <p style="font-size:19px;margin:0px;">Exercise Compliance</p>
                                <div style="position:absolute;top:35px;left:10px;">
                                    <p class="p4">2 weeks ago</p>
                                    <p v-if="userDetails.exercise_compile" class="p4">{{userDetails.exercise_compile.twoWeeks}}</p>
                                </div>
                                <div style="position:absolute;top:35px;left:120px;">
                                    <p class="p4">1 week ago</p>
                                    <p v-if="userDetails.exercise_compile" class="p4">{{userDetails.exercise_compile.lastWeek}}</p>
                                </div>
                                <div style="position:absolute;top:35px;left:210px;">
                                    <p class="p4">This week</p>
                                    <p v-if="userDetails.exercise_compile" class="p4">{{userDetails.exercise_compile.thisWeek}}</p>
                                </div>
                                <button style="border:none;background-color:transparent;position:absolute;top:60px;right:50px;">
                                    <p style="margin:0px;font-size:12px;">+</p>
                                    <p style="margin:0px;font-size:12px;">Schedule</p>
                                </button>
                            </div>
                            <div class="brds-2 tsl position-relative p-2" style="width:470px;height:160px;margin:0px 0px 10px 10px;">
                                <p style="font-size:19px;margin:0px;">Nutrition Compliance</p>
                                <div style="position:absolute;top:35px;left:10px;">
                                    <p class="p4">2 weeks ago</p>
                                    <p v-if="userDetails.nutrition_compile" class="p4">{{userDetails.nutrition_compile.twoWeeks}}</p>
                                </div>
                                <div style="position:absolute;top:35px;left:120px;">
                                    <p class="p4">1 week ago</p>
                                    <p v-if="userDetails.nutrition_compile" class="p4">{{userDetails.nutrition_compile.lastWeek}}</p>
                                </div>
                                <div style="position:absolute;top:35px;left:210px;">
                                    <p class="p4">This week</p>
                                    <p v-if="userDetails.nutrition_compile" class="p4">{{userDetails.nutrition_compile.thisWeek}}</p>
                                </div>
                                <button style="border:none;background-color:transparent;position:absolute;top:60px;right:50px;">
                                    <p style="margin:0px;font-size:12px;">+</p>
                                    <p style="margin:0px;font-size:12px;">Schedule</p>
                                </button>
                            </div>
                            <div class="brds-2 tsl position-relative" style="width:470px;height:160px;margin:0px 0px 10px 10px;">
                                <p style="font-size:19px;margin:8px;">Body Weight</p>
                                <img src="/images/Group74629.png" alt="Error" style="position:absolute;top:60px;height:80px;width:340px;">
                                <p style="font-size:39px;font-weight:bold;position:absolute;top:70px;right:10px">{{userDetails.body_weight}}</p>
                            </div>
                        </div>
                    </div>
                    <div style="width:100%;height:50px;">
                        <div class="position-relative mx-auto" style="height:50px;width:130px;">
                            <div @click="showSummary1" style="height:5px;width:40px;background-color:#545352;position:absolute;bottom:20px;border-radius:10px;left:0px;cursor:pointer;"></div>
                            <div style="height:5px;width:80px;background-color:black;position:absolute;bottom:20px;border-radius:10px;right:0px;cursor:pointer;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="consultation" class="mx-auto pt-3 brds-3 tsl position-relative" style="width:95%;min-height:335px;border:none">
                <div class="d-flex justify-content-between align-items-start px-4 mb-3">
                    <p style="font-size:29px;margin:0px;">Consultation Form</p>
                    <div v-if="logInDetails.role==='Admin'" class="habit-assignment-box">
                        <p class="mb-1 fw-bold">Assign Habit List</p>
                        <div class="d-flex gap-2">
                            <select v-model="selectedHabitListId" class="form-select form-select-sm">
                                <option :value="null">Select habit list</option>
                                <option v-for="list in habitLists" :key="list.id" :value="list.id">
                                    {{ list.name }}
                                </option>
                            </select>
                            <button class="assign-btn" @click="assignHabitList">Assign</button>
                        </div>
                        <p v-if="assignedHabitLists.length" class="assigned-list mb-0">
                            Assigned:
                            <span v-for="(list, index) in assignedHabitLists" :key="list.id">
                                {{ list.name }}<span v-if="index + 1 !== assignedHabitLists.length">, </span>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="w-100 px-4" v-for="(item, index) in consultationDetails" :key="index">
                    <p class="fw-bold mb-0">Q<span>{{index+1}}.</span> {{item.question}}</p>
                    <p v-if="item.answer_type=='single'" class="mb-0">Ans: {{item.answer}}</p>
                    <p v-else class="mb-0">Ans: <span v-for="(items, index1) in consultationDetails[index].answer" :key="index1" style="text-transform:capitalize;">{{items}}<span v-if="index1+1!==consultationDetails[index].answer.length">,</span></span></p>
                </div>
            </div>
            <div v-if="attachments" class="mx-auto pt-3 pb-3 brds-3 tsl position-relative" style="width:95%;min-height:335px;border:none">
                <p style="font-size:29px;margin:0px 0px 0px 20px;">Attachments</p>
                <p v-if="userAttachments.length<1" style="font-size:20px;margin:0px 0px 0px 20px;">No Posture picture added yet</p>
                <!-- <div class="mx-auto brds-2 mt-3 position-relative" style="width:95%;height:100px;border:1px solid #B1B0B0;text-align:center;">
                    <p v-if="attachmentFile==null" style="font-size:14px;color:#B1B0B0;margin:35px 0px 0px 0px;width:100%;">Upload as many attachments as you'd like. Click the Upload button to upload the file.</p>
                    <p v-else style="font-size:14px;color:#B1B0B0;margin:35px 0px 0px 0px;width:100%;">{{attachmentFile.name}}</p>
                    <input @change="getFile" type="file" ref="selectedFile" style="height:100%;width:100%;opacity:0;position:absolute;left:0px;top:0px;cursor:pointer;">
                </div> -->
                <div v-if="userAttachments!=null" v-for="(item, index) in userAttachments" :key="index"
                    class="mx-auto mt-3 brds-2 p-2" style="width:95%;border:1px solid #B1B0B0;">
                    <!-- <div class="w-100 px-4 py-2" style="min-height:50px"> -->
                        <h5>{{item.datetime}}</h5>
                        <div class="pt-3 d-flex justify-content-between">
                            <div class="shd_card w-25 p-3 text-center d-flex flex-column justify-content-between">
                                <img :src="item.front_picture" class="mb-2 img-fluid">
                                <p>Front</p>
                            </div>
                            <div class="shd_card w-25 p-3 text-center d-flex flex-column justify-content-between">
                                <img :src="item.side_picture" class="mb-2 img-fluid">
                                <p>Side</p>
                            </div>
                            <div class="shd_card w-25 p-3 text-center d-flex flex-column justify-content-between">
                                <img :src="item.back_picture" class="mb-2 img-fluid">
                                <p>Back</p>
                            </div>
                        </div>
                        <!-- <a :href="item.file" download class="mx-0 downloadBtn" title="Download"><i class="fa-solid fa-download"></i></a> -->
                        <!-- <button @click="downloadImage(item.file,item.file_name)" class="mx-0 downloadBtn border-0" style="background-color:transparent;"><i class="fa-solid fa-download"></i></button> -->
                    <!-- </div> -->
                </div>
                <!-- <button @click="saveAttachments" class="brds-4 tslin" style="position:absolute;top:30px;right:30px;border:none;height:27px;width:115px;font-size:17px;background-color:transparent;">Upload</button> -->
            </div>
            <div v-if="invoices" class="mx-auto pt-3 pb-3 mt-3 brds-3 px-3 tsl position-relative" style="width:95%;min-height:320px;border:none;">
                <p style="font-size:25px;padding-left:15px;">Invoices</p>
                <div v-if="items.length==0" class="w-100 text-center">
                    <p style="font-size:25px;">No Invoices To Show</p>
                    <p class="mx-auto" style="font-size:15px;width:70%;color:#B1B0B0;">Sell a product to a client and we'll automatically send them a request to complete
                        their payment. For each recurring product purchased, the invoices are sent at the
                        start of each billing cycle.
                    </p>
                </div>
                <Vue3EasyDataTable v-else
                :headers="headers"
                :items="items"
                :search-field="searchField"
                :search-value="searchValue"
                >
                <template #item-amount_paid="item">
                   $ {{item.amount_paid}}
                </template>
                <template #item-status="item">
                    <p v-if="item.status=='active'" class="mb-0 py-1 brds-2 text-center" style="background-color:green;width:70px;color:white;cursor:pointer;">Active</p>
                    <p v-else-if="item.status=='expired'" class="mb-0 py-1 brds-2 text-center" style="background-color:red;width:70px;color:white;cursor:pointer;">Expired</p>
                    <p v-else-if="item.status=='awaiting_payment'" class="mb-0 py-1 brds-2 text-center" style="background-color:orange;width:90px;color:white;cursor:pointer;">No Payment</p>
                    <p v-else-if="item.status=='refunded'" class="mb-0 py-1 brds-2 text-center bg-secondary" style="width:90px;color:white;cursor:pointer;">Refunded</p>
                </template>
                </Vue3EasyDataTable>
            </div>
            <!-- <div v-if="sales && sellProduct==false" class="mx-auto pt-3 brds-3 tsl position-relative" style="width:95%;min-height:335px;border:none">
                <div class="position-relative" style="width:100%;height:40px;">
                    <p style="position:absolute;top:0px;left:10px;font-size:29px;margin:0px;">Sales</p>
                    <select style="position:absolute;right:10px;top:0px;width:240px;height:30px;font-size:12px;color:#343434;border:1px solid #EDEDED;border-radius:5px;margin-top:5px;" id="">
                        <option selected>Date Added</option>
                    </select>
                </div>
                <div style="width:100%; min-height:295px;text-align:center">
                    <p style="font-size:29px;">No Sales Found</p>
                    <p class="mx-auto" style="font-size:14px;color:#B1B0B0;width:500px">Upload as many attachments as you'd like. Drag and drop files in the area below, or click the Upload button to add files from your computer.</p>
                    <button class="brds-4 tslin" style="border:none;height:30px;width:216px;font-size:17px;background-color:transparent;color:#B1B0B0;" @click="addsell">Sell New Product</button>
                </div>
            </div>
            <div v-if="sellProduct" class="mx-auto p-4 brds-3 tsl position-relative" style="width:95%;min-height:335px;border:none">
                <button class="trans_btn position-absolute" @click="quitsell"
                    style="right:15px;top:5px;font-size:25px;color:#707070;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <p class="mx-auto m-0" style="width:600px;font-size:26px;text-align:center;">Sell Home workout Programs & Coaching /
                    برامج التمارين المنزلية و التدريب اونلاين
                </p>
                <p style="font-size:17px;color:#B1B0B0;margin:0px;">Select a product to sell</p>
                <select class="tslin mb-2" style="width:280px;height:44px;font-size:14px;color:#343434;border:none;border-radius:10px;margin-top:5px;color:#B1B0B0" id="">
                    <option selected>Home workout Programs & Coaching</option>
                </select>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault1">
                    <label class="form-check-label" for="flexRadioDefault1" style="color:#B1B0B0">
                        Today
                    </label>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault2">
                    <label class="form-check-label" for="flexRadioDefault2" style="color:#B1B0B0">
                        Next week
                    </label>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault3">
                    <label class="form-check-label" for="flexRadioDefault3" style="color:#B1B0B0">
                        Selected date
                    </label>
                </div>
                <div class="form-check mb-1">
                    <input class="form-check-input" type="radio" name="flexRadioDefault" id="flexRadioDefault4">
                    <label class="form-check-label" for="flexRadioDefault4" style="color:#B1B0B0">
                        After current product
                    </label>
                </div>
                <p class="mt-3" style="font-size:17px;color:#B1B0B0;margin:0px;">Apply discount code?</p>
                <select class="tslin mb-4" style="width:280px;height:44px;font-size:14px;color:#343434;border:none;border-radius:10px;margin-top:5px;color:#B1B0B0" id="">
                    <option selected>No Discount</option>
                </select>
                <button class="prim_bg brds-2" style="height:40px;width:180px;border:none;position:absolute; bottom:20px;right:50px;font-size:16px;" @click="quitsell">Sell Product</button>
            </div>
            <div v-if="invoices" class="mx-auto brds-2 tsl position-relative px-3 py-2" style="min-height:350px;width:95%;text-align:center;">
                <p style="font-size:29px;text-align:start;">Invoices</p>
                <p style="font-size:29px;">No Invoices To Show</p>
                <p class="mx-auto" style="font-size:14px;color:#B1B0B0;width:60%">Sell a product to a client and we'll automatically send them a request to complete
                    their payment. For each recurring product purchased, the invoices are sent at the
                    start of each billing cycle.
                </p>
            </div> -->
        </div>
    </div>
    <assignTags v-if="tagsDiv" :tagType="'client'" :id="idForDetails" :prefilledTags="clientTags.ids"/>
</template>
<script>
import axios from 'axios';
import config from '../../config';
import Vue3EasyDataTable from 'vue3-easy-data-table';
import 'vue3-easy-data-table/dist/style.css';
import { ref } from "vue";
import Loader from '../loader.vue';
import Inform from '../inform.vue';
import assignTags from './assignTags.vue';
export default {
    name: 'clientPopup',
    components: { Loader, Inform, Vue3EasyDataTable, assignTags },
    props: ['idForDetails', 'logInDetails'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            apiConfigForAttachment: {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            summary: true,
            summary1: true,
            summary2: false,
            consultation: false,
            consultationEdit: false,
            attachments: false,
            sales: false,
            sellProduct: false,
            invoices: false,
            pageLoading: false,
            loaderText: '',
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            userDetails: null,
            consultationText: null,
            consultationDetails: null,
            userAttachments: null,
            attachmentFile: null,
            habitLists: [],
            assignedHabitLists: [],
            selectedHabitListId: null,
            tagsDiv: false,
            clientTags: {
                ids: [],
                names: []
            },
            headers: [
                { text: "Amount", value: "amount_paid", sortable: true },
                { text: "Card", value: "card", sortable: true },
                { text: "Discount Code", value: "discount_used", sortable: true },
                { text: "Status", value: "status", sortable: true },
                { text: "Subscription", value: "subscription", sortable: true },
                { text: "Start Date", value: "sub_start_date", sortable: true },
                { text: "End Date", value: "sub_expire_date", sortable: true },
            ],
            items: [],
        }
    },
    mounted() {
        this.getClientDetails();
        this.getClientConsultation();
        this.getClientInvoices();
        this.getClientTags();
        this.getHabitLists();
        if (this.logInDetails.role == 'Admin')
            this.getAttachments();
    },
    methods: {
        assignTagsShow() {
            this.tagsDiv = !this.tagsDiv;
        },
        changeStatus() {
            this.pageLoading = true;
            this.loaderText = 'Changing status';
            axios.get(config.baseApiUrl + 'activate-deactivate-client/' + this.idForDetails, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Client Status changed successfully';
                    this.informModal = true;
                    this.getClientDetails();
                    if(this.$route.name==='clients' || this.$route.name==='Clients')
                    this.$parent.getClientSummary();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        getFile() {
            this.attachmentFile = this.$refs.selectedFile.files[0];
        },
        getClientDetails() {
            let userId = this.idForDetails;
            this.pageLoading = true,
                this.loaderText = 'Loading';
            axios.get(config.baseApiUrl + 'client-detail/' + userId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.userDetails = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        getClientTags() {
            let userId = this.idForDetails;
            this.pageLoading = true,
                this.loaderText = 'Loading';
            axios.get(config.baseApiUrl + 'client-tags/' + userId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.clientTags = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        getHabitLists() {
            axios.get(config.baseApiUrl + 'get-all-habit-lists', this.apiConfig).then(res => {
                if (res.data.status) {
                    this.habitLists = res.data.data || [];
                    this.getAssignedHabitLists();
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = er.message || er;
                this.informModal = true;
            });
        },
        getAssignedHabitLists() {
            if (!this.habitLists.length) {
                this.assignedHabitLists = [];
                return;
            }

            const requests = this.habitLists.map(list =>
                axios.get(config.baseApiUrl + 'get-habit-list-users/' + list.id, this.apiConfig)
                    .then(res => {
                        const users = res.data.status ? (res.data.data || []) : [];
                        const assigned = users.some(user => user && user.id == this.idForDetails);
                        return assigned ? list : null;
                    })
                    .catch(() => null)
            );

            Promise.all(requests).then(results => {
                this.assignedHabitLists = results.filter(Boolean);
            });
        },
        assignHabitList() {
            if (!this.selectedHabitListId) {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Please select a habit list';
                this.informModal = true;
                return;
            }

            this.pageLoading = true;
            this.loaderText = 'Assigning habit list';
            axios.post(config.baseApiUrl + 'assign-habit-list-to-users', {
                habit_list_id: this.selectedHabitListId,
                user_ids: [this.idForDetails],
            }, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = res.data.message || 'Habit list assigned successfully';
                    this.informModal = true;
                    this.selectedHabitListId = null;
                    this.getAssignedHabitLists();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message || er;
                this.informModal = true;
            });
        },
        getClientInvoices() {
            let userId = this.idForDetails;
            this.pageLoading = true,
                this.loaderText = 'Loading';
            axios.get(config.baseApiUrl + 'client-invoices/' + userId, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.items = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = er;
                this.informModal = true;
            })
        },
        saveConsultation(m) {
            let option = m;
            if (option == 0) {
                this.consultationEdit = false;
                this.consultationText = null;
                return
            }
            else {
                let postData = {
                    user_id: null,
                    content: null,
                }
                postData.user_id = this.idForDetails;
                postData.content = this.consultationText;
                if (postData.content == null || postData.content == "") {
                    this.modalTitle = "Error!";
                    this.modalDetail = "Please enter some content it's required";
                    this.informModal = true;
                }
                else {
                    this.pageLoading = true;
                    this.loaderText = 'Uploading';
                    axios.post(config.baseApiUrl + 'create-consult-message', postData, this.apiConfig).then(res => {
                        this.pageLoading = false;
                        if (res.data.status) {
                            this.modalTitle = 'Done!';
                            this.modalDetail = 'Consultation Created Successfully';
                            this.informModal = true;
                            this.getClientConsultation();
                            this.consultationEdit = false;
                        }
                        else {
                            this.modalTitle = 'Error!';
                            this.modalDetail = res.data.message;
                            this.informModal = true;
                        }
                    })
                        .catch(er => {
                            this.pageLoading = false;
                            this.modalTitle = 'Error!';
                            this.modalDetail = er;
                        })
                }
            }
        },
        getClientConsultation() {
            this.pageLoading = true;
            let user_id = this.idForDetails;
            axios.get(config.baseApiUrl + 'client-answers/' + user_id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.consultationDetails = res.data.data;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalTitle = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true;
                })
        },
        // saveAttachments() {
        //     if (this.attachmentFile == null) {
        //         this.modalTitle = 'Error!';
        //         this.modalDetail = 'Please select a file first';
        //         this.informModal = true;
        //         return
        //     }
        //     else {
        //         this.pageLoading = true;
        //         let fd = new FormData();
        //         fd.append('user_id', this.idForDetails);
        //         fd.append('file', this.attachmentFile)
        //         axios.post(config.baseApiUrl + 'create-client-attatchment', fd, this.apiConfigForAttachment).then(res => {
        //             this.pageLoading = false;
        //             if (res.data.status) {
        //                 this.modalTitle = 'Done!';
        //                 this.modalDetail = 'Attachment Added successfully';
        //                 this.informModal = true;
        //                 this.getAttachments();
        //             }
        //             else {
        //                 this.modalTitle = 'Error!';
        //                 this.modalDetail = res.data.message;
        //                 this.informModal = true;
        //             }
        //         }).catch(er => {
        //             this.pageLoading = false;
        //             this.modalTitle = 'Error!';
        //             this.modalDetail = er;
        //             this.informModal = true;
        //         })
        //     }
        // },
        getAttachments() {
            this.pageLoading = true;
            axios.get(config.baseApiUrl + 'client-postures/' + this.idForDetails, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.userAttachments = res.data.data;
                }
            })
        },
        showSummary() {
            this.summary = true;
            this.consultation = false;
            this.attachments = false;
            this.sales = false;
            this.invoices = false;
            this.summary1 = true;
            this.summary2 = false;
        },
        showSummary1() {
            this.summary1 = true;
            this.summary2 = false;
        },
        showSummary2() {
            this.summary2 = true;
            this.summary1 = false;
        },
        showConsultation() {
            this.consultation = true;
            this.summary = false;
            this.attachments = false;
            this.sales = false;
            this.invoices = false;
        },
        showAttachments() {
            this.attachments = true;
            this.summary = false;
            this.consultation = false;
            this.sales = false;
            this.invoices = false;
        },
        showSales() {
            this.sales = true;
            this.summary = false;
            this.consultation = false;
            this.attachments = false;
            this.invoices = false;
            this.sellProduct = false;
        },
        showInvoices() {
            this.invoices = true;
            this.sellProduct = false;
            this.summary = false;
            this.consultation = false;
            this.attachments = false;
            this.sales = false;
        },
        addsell() {
            this.sellProduct = true;
        },
        quitsell() {
            this.sellProduct = false;
        },
        quitComponent() {
            this.$parent.ClientPopup();
        },
        acknowledged() {
            this.informModal = false;
        }
    },
}
</script>
<style scoped>
.top-btn {
    margin-top: 15px;
    height: 30px;
    font-size: 13px;
    color: #343434ee;
    border: 1px solid #C5C5C5;
    background-color: #FFFFFF;
    border-left: none;
}

.client-dots {
    height: 60px;
    width: 60px;
    border: none;
    position: absolute;
    top: 10px;
    right: 20px;
    border-radius: 30px;
    background-color: #F2A18C;
}

.client-dots::after {
    content: '\2026';
    font-size: 35px;
    color: white;
    position: absolute;
    top: -5px;
    right: 19px;
}

.p1 {
    font-size: 15px;
    float: left;
    width: 130px;
}

.p2 {
    font-size: 12px;
    float: right;
    width: 190px;
    box-shadow: 0 0 10px 0px #F2A18C inset;
    border-radius: 15px;
    padding: 6px 0px 5px 10px;
    color: #B1B0B0
}

.add-btn {
    height: 33px;
    width: 33px;
    border: none;
    float: left;
    border-radius: 30px;
    background-color: #F2A18C;
    position: relative;
    margin: 3px;
}

.add-btn::after {
    content: '\002B	';
    font-size: 33px;
    color: white;
    position: absolute;
    bottom: -10px;
    left: 5px;
}

.p4 {
    margin: 0px;
    font-size: 12px;
    color: #B1B0B0;
    width: 100%;
    text-align: center;
}

.btn-1 {
    height: 17px;
    width: 16px;
    font-size: 15px;
    padding: 0px;
    color: #FC9C88;
    border: 1px solid #FC9C88;
    background-color: #F5F5F5;
    position: absolute;
    top: 15px;
    right: 50px;
}

.btn-2 {
    font-size: 30px;
    color: #FC9C88;
    border: none;
    background-color: transparent;
    position: absolute;
    top: 2px;
    padding: 0px;
}

.meal-card {
    height: 110px !important;
    width: 95px !important;
    border-radius: 10px;
    background-color: #EEEAEA;
    margin-left: 10px;
    float: left;
}

.usermsg {
    float: left;
}

.adminmsg {
    right: 10px;
}

.active {
    background-color: #F2A18C;
    border: none;
}

.downloadBtn {
    position: relative;
    color: black;
    font-size: 20px;
    float: left;
}

.habit-assignment-box {
    width: 360px;
    border: 1px solid #E7E7E7;
    border-radius: 10px;
    padding: 10px;
    background-color: #FFFFFF;
}

.assign-btn {
    border: none;
    border-radius: 5px;
    background-color: #F2A18C;
    padding: 4px 14px;
    font-size: 13px;
    white-space: nowrap;
}

.assigned-list {
    margin-top: 8px;
    color: #777;
    font-size: 12px;
}
</style>
