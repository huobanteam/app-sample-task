require('es6-promise').polyfill()
import Vue from 'vue'
import Router from 'vue-router'
import { sync } from 'vuex-router-sync'
import store from './vuex/store'
import configRouter from './routes'
import App from './App'
import Sortable from 'vue-sortable'
import KSortable from './vendor/k-sortable'
import HB from './plugins/hb'
import FastClick from 'fastclick'
import $ from 'zepto'

$(function() {
  FastClick.attach(document.body);
});

Vue.use(Router)
Vue.use(Sortable)
Vue.use(HB)
Vue.use(KSortable)
// Object.keys(filters).forEach(k => Vue.filter(k, filters[k]))

const router = new Router({
  history: true,
  saveScrollPosition: true,
  suppressTransitionError: true
})
configRouter(router)
sync(store, router)

router.start(Vue.extend(App), '#root')
window.router = router
