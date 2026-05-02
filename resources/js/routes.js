import { createWebHistory, createRouter } from "vue-router";


import Login from './pages/login';
import Forget from './pages/forgot';
import Overview from './pages/overview';
import Messages from './pages/messages';
import Groups  from "./pages/Groups";
import Clients from "./pages/clients";
import Homepage from "./Website/Homepage";
import program from "./pages/master-libraries/program";
import Workout from "./pages/master-libraries/Workout";
import Habit from "./pages/master-libraries/Habit";
import Exercises from "./pages/master-libraries/Exercises";
import Meals from "./pages/master-libraries/Meals";
import Food from "./pages/master-libraries/Food";
import Payments from "./pages/payments.vue";
import PodcastCMS from "./pages/podcastCMS.vue";
import Settings from "./pages/settings.vue";
import Teams from './pages/teams.vue';
import Profile from './pages/profile.vue';
import UserLogin from './Website/userLogin.vue';
import Podcast from './Website/podcast.vue';
import ContactUs from './Website/contactUS.vue';
import ListOfFoods from './Website/listOfFoods.vue';
import CalculateBMR from './Website/calculateBMR&TDE.vue';
import AboutMe from './Website/aboutMe.vue';
import Tags from './pages/tags.vue';
import Poc from './pages/Poc.vue';
import MealPlan from './pages/master-libraries/MealPlan.vue'
import Consultation from "./pages/consultation.vue";
import Localization from "./pages/localization.vue";
import UiStringsTranslation from "./pages/ui_strings_translation.vue";


const routes = [
    { path: '/cms/login', component: Login, name: 'Login' },
    { path: '/cms/forgot-password', component: Forget, name: 'Forget' },
    { path: '/cms/overview', component: Overview, name: 'Overview' },
    { path: '/cms/messages', component: Messages, name: 'Messages' },
    { path: '/cms/groups', component: Groups, name: 'Groups' },
    { path: '/cms/clients', component: Clients, name:'Clients'},    // change clients popup condition if changing url
    { path: '/cms/program', component: program, name: 'program' },
    { path: '/cms/workout', component: Workout, name: 'Workout' },
    { path: '/cms/habit', component: Habit, name: 'Habit' },
    { path: '/cms/exercises', component: Exercises, name: 'Exercises' },
    { path: '/cms/mealplan', component: MealPlan, name: 'MealPlan' },
    { path: '/cms/meals', component: Meals, name: 'Meals' },
    { path: '/cms/food', component: Food, name: 'Food' },
    { path: '/cms/payments', component: Payments, name: 'Payments' },
    { path: '/cms/settings', component: Settings, name: 'Settings'},
    { path: '/cms/teams', component: Teams, name:'Teams'},
    { path: '/cms/profile', component: Profile, name:'Profile'},
    { path: '/cms/tags', component: Tags, name:'Tags'},
    { path: '/cms/podcast', component: PodcastCMS, name:'PodcastCMS'},
    { path: '/cms/poc', component: Poc, name:'Poc'},
    { path: '/cms/consultation', component:Consultation, name:'Consultation'},
    { path: '/cms/localization', component: Localization, name: 'Localization'},
    { path: '/cms/ui-strings', component: UiStringsTranslation, name: 'UiStrings'},
    { path: '/', component: Homepage, name: 'Homepage' },
    { path: '/user/login', component:UserLogin, name:'UserLogin'},
    { path: '/AboutMe', component:AboutMe, name:'AboutMe'},
    { path: '/CalculateBMR&TDEE', component:CalculateBMR, name:'CalculateBMR&TDE'},
    { path: '/ListOfFoods', component:ListOfFoods, name:'ListOfFoods'},
    { path: '/ContactUS', component:ContactUs, name:'ContactUS'},
    { path: '/Podcast', component:Podcast, name:'Podcast'},
];

const router = createRouter({
    // base : '/cms',
    history: createWebHistory(),
    routes,
    linkActiveClass: "activePage", // active class for non-exact links.
    linkExactActiveClass: "activePage" // active class for exact links.
});

export default router;
