// import Vue from 'vue'
import {dialog} from '../types'
// import _ from 'lodash'

function setDialogState(state = {}, show=false, dialogData = {}) {
  state.show = show
  state.title = dialogData.title || ''
  state.subject = dialogData.subject || ''
  state.content = dialogData.content || ''
  state.buttons = dialogData.buttons || []
  state.onDialogShow = dialogData.onDialogShow || (() => {})
  state.onDialogHide = dialogData.onDialogHide || (() => {})
  state.onDialogButtonClick = dialogData.onDialogButtonClick || (() => {})
  return state
}

// 子模块的数据，作为全局store下state的sub-tree
const state = setDialogState()

// 对子模块数据的操作
const mutations = {
  [dialog.SHOW](state, dialogData) {
    setDialogState(state, true, dialogData)
  },
  [dialog.HIDE](state) {
    if (state.show) {
      state.show = false
    }
  }
}

export default {
  state,
  mutations
}