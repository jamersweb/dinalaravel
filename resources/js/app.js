require('./bootstrap')

import { createApp } from 'vue';
import App from './App.vue';
import router from './routes';
import config from './config';
import firebase from 'firebase/compat/app';

// Add Bearer token to all CMS API requests (token read at request time)
window.axios.interceptors.request.use((req) => {
  if (req.url?.includes('/api/cms/')) {
    const token = config.storage.getItem('fwd_session_token');
    if (token) {
      req.headers.Authorization = 'Bearer ' + token;
    }
  }
  return req;
});

// Redirect to CMS login on 401 (expired/invalid token)
window.axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 && error.config?.url?.includes('/api/cms/')) {
      config.storage.removeItem('fwd_session_token');
      if (window.location.pathname.startsWith('/cms') && !window.location.pathname.includes('/login')) {
        window.location.href = '/cms/login';
      }
    }
    return Promise.reject(error);
  }
);

const firebaseConfig = {
    apiKey: "AIzaSyCE9-khu88yCOdsuUZvruvFvIxXQoV8HQk",
    authDomain: "fitness-with-dina.firebaseapp.com",
    projectId: "fitness-with-dina",
    storageBucket: "fitness-with-dina.appspot.com",
    messagingSenderId: "507556087140",
    appId: "1:507556087140:web:4869b8d58ee78e8a3fe671",
    measurementId: "G-29CZMF25BC"
  };

  firebase.initializeApp(firebaseConfig);

createApp(App).use(router).mount('#app');
// createApp(Index).mount('#app')