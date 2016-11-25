import Vue from 'vue'
import Vuex from 'vuex'
import createLogger from 'vuex/logger'

import dialog from './modules/dialog'
import error from './modules/error'
import menu from './modules/menu'

import task from './modules/task'
import auth from './modules/auth'
import project from './modules/project'
import stream from './modules/stream'
// import * as SDK from 'huoban-app-sdk'

Vue.use(Vuex)

// 调整isPro=1模拟生产环境
// 调整isDebug对应开启/关闭Vue的调试模式
const isPro = 0 || process.env.NODE_ENV == 'production'
const isDebug = 0 || !isPro
const applicationId = 11002

Vue.config.debug = isDebug

export default new Vuex.Store({
  modules: {
    dialog,
    menu,
    error,

    task,
    auth,
    project,
    stream
  },
  state: {isPro, isDebug, applicationId},
  strict: isDebug,
  middlewares: isDebug ? [createLogger()] : []
})
