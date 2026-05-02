<template lang="">
    <div class="left-menu h-100" style="background-color: #eeeeee;border-radius:1rem;overflow:auto">
        <div class="left-menu-head d-flex justify-content-around px-2 py-2"
            style="border-bottom: 1px solid rgb(177, 177, 177);">
            <div class="d-flex align-items-center">
                <img v-show="!collapsedMenu" src="/cms-assets/images/navbar-topbar/logo.png" alt="logo" class="img-fluid" style="max-height:70px">
                <img v-show="collapsedMenu" src="/cms-assets/images/navbar-topbar/logo2.png" alt="logo" class="img-fluid" >
            </div>
            <div class="d-flex align-items-center menu-coll-btn">
                <img @click="collapseToggle" src="/cms-assets/images/navbar-topbar/resizer.png" alt="menu-collapser"
                    class="img-fluid" style="max-height:15px;cursor:pointer;z-index:99999;">
            </div>
        </div>
        <div class="left-menu-body" style="margin-top: 30px;">
            <div class="menu-list">
                <router-link class="menu_item px-4 mx-0" to="/cms/overview">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/overview.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <p v-show="!collapsedMenu">Overview</p>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/messages">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/msgs.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <p v-show="!collapsedMenu">Messages</p>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/groups">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/groupsteams.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <p v-show="!collapsedMenu">Groups</p>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/clients">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/clients.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <p v-show="!collapsedMenu">Clients</p>
                    </div>
                </router-link>
                <router-link v-if="logInProps.role==='Admin'" class="menu_item px-4 mx-0" to="/cms/teams">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/groupsteams.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <p v-show="!collapsedMenu">Teams</p>
                    </div>
                </router-link>
                <div @click="ToggleMAster" class="pointer">
                    <a class="menu_item px-4 mx-0">
                        <div :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/libr.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p style="display: flex; align-items: baseline;" v-show="!collapsedMenu">Master Libraries
                                <img :class="{'flip-verticaly': proshow}" class="masterimg" src="/cms-assets/images/masterShape.png" alt=""></p>
                        </div>
                    </a>
                </div>
                <div v-show="proshow">
                    <router-link class="menu_item px-4 mx-0" to="/cms/program">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/program.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Programs</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/workout">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/workout.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Workout</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/habit">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/habit.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Habits</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/exercises">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/exercise.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Exercises</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/mealplan">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/mealplan.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Meal Plan</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/meals">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/meal.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Meals</p>
                        </div>
                    </router-link>
                    <router-link class="menu_item px-4 mx-0" to="/cms/food">
                        <div class="ms-4" :class="{widthalign:!collapsedMenu}">
                            <img src="/cms-assets/images/navbar-topbar/food.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                                style="height:25px;width:30px;">
                        </div>
                        <div>
                            <p v-show="!collapsedMenu" :class="{masterlibraries:collapsedMenu}">Foods</p>
                        </div>
                    </router-link>
                </div>
                <router-link class="menu_item px-4 mx-0" to="/cms/payments">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/payments.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">Payments</span>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/podcast">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/podcast.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">PodCast</span>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/consultation">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/friend.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">Consultation</span>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/tags">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/promotion.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">Tags</span>
                    </div>
                </router-link>
                <!-- <router-link class="menu_item px-4 mx-0" to="/cms/poc">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/poc.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">POC</span>
                    </div>
                </router-link> -->
                <router-link class="menu_item px-4 mx-0" to="/cms/localization">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/promotion.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">Localization</span>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/ui-strings">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/promotion.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu">UI Strings</span>
                    </div>
                </router-link>
                <router-link class="menu_item px-4 mx-0" to="/cms/settings">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/setting.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;">
                    </div>
                    <div>
                        <span v-show="!collapsedMenu"> Settings</span>
                    </div>
                </router-link>
                <button class="menu_item px-4 mx-0 w-100 border-0" @click="logout">
                    <div :class="{widthalign:!collapsedMenu}">
                        <img src="/cms-assets/images/navbar-topbar/Group 55730.png" alt="" :class="{collapsedimg:collapsedMenu}" class="img-fluid"
                            style="height:25px;width:30px;float:left;">
                    </div>
                    <div style="float:right;width:75%;text-align:start;">
                        <span v-show="!collapsedMenu">Logout</span>
                    </div>
                </button>
                <!-- <div class="menu_item px-4"><router-link to="/cms/logout"><img src="/cms-assets/images/navbar-topbar/overview.png" alt="" class="img-fluid" style="max-height:25px">Logout</router-link></div> -->
            </div>
        </div>
        <div>

        </div>
    </div>
</template>
<script>
import config from "../config";
export default {
    props: ['logInProps'],
    data() {
        return {
            collapsedMenu: false,
            proshow: false,
        };
    },
    created() {
        this.checkWindow();
        window.addEventListener("resize", this.checkWindow);
    },
    methods: {
        ToggleMAster() {
            this.proshow = !this.proshow;
        },
        checkWindow() {
            let size = window.innerWidth;
            if (size < 850) {
                this.collapsedMenu = false;
            }
        },
        logout() {
            const token = config.storage.getItem('fwd_session_token');
            config.storage.removeItem('fwd_session_token');
            axios.get(config.baseApiUrl + 'logout', {
                headers: {
                    Authorization: 'Bearer ' + token
                }
            });
            this.$router.push('/cms/login');
        },
        collapseToggle() {
            this.collapsedMenu = !this.collapsedMenu;
            this.$parent.collapseToggle();
        },
    },
};
</script>
<style scoped>
a.menu_item {
    color: black;
    text-decoration: none;
    display: flex;
    justify-content: left;
    align-items: center;
}

a {
    font-size: 15px;
}

a.activePage {
    font-weight: bold;
    font-size: 15px;
}

.main_app .menu_item {
    padding: 13px !important;
}

.collapsedimg {
    height: 15px !important;
    width: 20px !important;
    margin-left: 6px;
}

.masterimg {
    width: 10px;
    height: 8px;
    margin-left: 10px;
}

p {
    margin-bottom: 0px;
}

.masterlibraries {
    font-size: 10px;
}

.widthalign {
    width: 25%;
}
.flip-verticaly{
    transform: scaleY(-1);
}
</style>
