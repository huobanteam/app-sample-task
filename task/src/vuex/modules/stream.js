import {
  taskStream
} from '../types'

const state = {
  hasStreams: false,
  streamsList: []
}

const mutations = {
  [taskStream.GET_ALL](state, data, isUpdate) {
    if (isUpdate) {
      state.streamsList = data.streams
    } else {
      state.streamsList = state.streamsList.concat(data.streams)
    }
    state.hasStreams = data.load_more
  },
  [taskStream.CLEAR](state) {
    state.streamsList = []
    state.hasStreams = false
  }
}

export default {
  state,
  mutations
}