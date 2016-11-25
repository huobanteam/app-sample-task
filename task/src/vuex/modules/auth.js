import { auth } from '../types'

// 初始化应用的数据
const state = {
  user: null,
  ticket: '',
  table: null,
  appId: null,
  tableId: null
}

const mutations = {
  // 取得应用的初始化信息
  [auth.GET_INITINFO](state, initInfo) {
    state.user = initInfo.user
    state.ticket = initInfo.ticket
    state.table = initInfo.table
    state.appId = initInfo.app_id
    state.tableId = initInfo.table.table_id
  }
}

export default {
  state,
  mutations
}