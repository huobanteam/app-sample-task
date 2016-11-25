// import Vue from 'vue'
import {menu} from '../types'
// import _ from 'lodash'

function setMenuState(state = {}, show=false, menuData = {}) {
  state.show = show
  state.items = menuData.items || []
  state.position = menuData.position || {left: 0, top: 0}
  state.onItemClick = menuData.onItemClick || function() {}
  state.current = menuData.current < 0 ? -1 : menuData.current
  state.menuTitle = menuData.menuTitle || ''
  state.menuStyle = menuData.menuStyle || {}
  return state
}

// 子模块的数据，作为全局store下state的sub-tree
const state = setMenuState()

// 对子模块数据的操作
const mutations = {
  [menu.SHOW](state, menuData) {
    setMenuState(state, true, menuData)
  },
  [menu.HIDE](state) {
    if (state.show) {
      state.show = false
    }
  }
}

export default {
  state,
  mutations
}
