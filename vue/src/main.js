import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import ElementUI from 'element-ui'
import "./assets/style/reset.scss"
import "./assets/style/common.scss"
import "./assets/style/element-ui-index.scss"
import axios from "axios";
import qs from "qs";
import Common from "./utils/utils"
import "./assets/iconfont/iconfont.css"
import VideoPlayer from 'vue-video-player'
import 'video.js/dist/video-js.css'
import 'vue-video-player/src/custom-theme.css'
import VueI18n from 'vue-i18n'
import zh from './i18n/langs/zh'
import en from './i18n/langs/en'
import ja from './i18n/langs/ja'
import enLocale from 'element-ui/lib/locale/lang/en'
import zhLocale from 'element-ui/lib/locale/lang/zh-CN'
import jaLocale from 'element-ui/lib/locale/lang/ja'

import 'video.js/dist/video-js.css'
import moment from 'moment'

Vue.prototype.moment = moment;

Vue.use(VueI18n)
Vue.use(VideoPlayer)
// Vue.use(ElementUI)
Vue.use(Common)

let javaApiUrl = process.env.VUE_APP_API_URL

const serverJava = axios.create({
  baseURL: javaApiUrl,
  timeout: 30000,
  headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=utf-8' }
});

const server = axios.create({

  baseURL: "http://test-admin.playbabies.net/",
  // baseURL: "/",
  timeout: 30000,
  headers: {
    "Content-Type": "application/x-www-form-urlencoded;charset=utf-8",
  },
});

const i18n = new VueI18n({
  locale: window.localStorage.getItem('lang') || 'zh',
  messages: {
    'zh': Object.assign(zh, zhLocale), // 中文语言包
    'en': Object.assign(en, enLocale), // 英文语言包
    'ja': Object.assign(ja, enLocale), // 英文语言包
  }
})
Vue.use(ElementUI, {
  i18n: (key,value) => i18n.t(key,value)
});
// 请求拦截器
server.interceptors.request.use((config) => {
  config.data = qs.stringify(config.data)
  if (window.localStorage.getItem('usercode')) {
    config.data = config.data + "&usercode=" + window.localStorage.getItem('usercode')
  } else {
    router.replace({
      path: '/login',
      query: { redirect: router.currentRoute.fullPath }//登录成功后跳入浏览的当前页面
    })
  }
  return config
})
// 响应拦截器，
server.interceptors.response.use(
  response => {
    if (response.status == 200) {
      return response.data
    } else {
      router.replace({
        path: '/login',
        query: { redirect: router.currentRoute.fullPath }//登录成功后跳入浏览的当前页面
      })
    }
  },
  error => {
    router.replace({
      path: '/login',
      query: { redirect: router.currentRoute.fullPath }//登录成功后跳入浏览的当前页面
    })
    return Promise.reject(error);
  }
)

Vue.prototype.$http = server;
Vue.prototype.$httpJava = serverJava;

new Vue({
  router,
  i18n,  // 不要忘记
  store,
  render: h => h(App)
}).$mount('#app')
