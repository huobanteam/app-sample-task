import {error} from '../types'

// 子模块的数据，作为全局store下state的sub-tree
const state = {
  show: false,
  info: null
}

// 对子模块数据的操作
const mutations = {
  [error.SHOW](state, info) {
    state.show = true
    state.info = info
  },
  [error.HIDE](state) {
    state.show = false
  }
}

export default {
  state,
  mutations
}