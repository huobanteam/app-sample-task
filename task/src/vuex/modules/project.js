// import Vue from 'vue'
import {
  taskProject
} from '../types'
import _ from 'lodash'
import store from '../store' //循环引用，异步调用引用store没有问题，无奈的做法

// 子模块的数据，作为全局store下state的sub-tree
const state = {
  currentProjectId: null,
  editing: false,
  activeProjectList: [],
  archivedProjectList: [],
  loading: true
}
//---task project相关公共方法
function getStorageCurrentProjectId() {
  let table_id = store.state.auth.tableId
  return Number(store.state.route.params.project_id) ||
    (localStorage.getItem('task_'+table_id+'_current')
      ? Number(localStorage.getItem('task_'+table_id+'_current')) : null)
}

function setStorageCurrentProjectId(currentProjectId) {
  let table_id = store.state.auth.tableId

  currentProjectId += ''
  localStorage.setItem('task_'+table_id+'_current',currentProjectId)
}

function updateProjectName(list, value, projectId) {
  let index = _.findIndex(list, (o) => o.project_id===projectId)
  list[index].name = value
}

function deleteProjectItem(list, item) {
  let index = _.findIndex(list, (o) => item.project_id === o.project_id)
  list.splice(index, 1)
}

function modifyProjectList(insertList, removeList, item) {
  let len = insertList.length
  let index = _.findIndex(removeList, (o) => item.project_id === o.project_id)
  let temp = removeList.splice(index,1)[0]
  temp.is_normal = !temp.is_normal
  if (!item.is_normal) {
    insertList.splice(0 ,0 ,temp)
  } else {
    insertList.splice(len, 0, temp)
  }
}

// 对子模块数据的操作
const mutations = {
  [taskProject.SET_CURRENT](state, item) {
    state.currentProjectId = item.project_id
    setStorageCurrentProjectId(item.project_id)
  },

  [taskProject.SET_EDITING](state, editing) {
    state.editing = editing
  },

  [taskProject.SORT](state) {
    let newOptions = _.assign(state.sortOptions, {disabled: true, sort: false})
    state.sortOptions = newOptions
  },

  [taskProject.UPDATE_ORDER](state, returnList, is_normal) {
    if (!is_normal) {
      state.archivedProjectList = returnList
    } else {
      state.activeProjectList = returnList
    }
  },

  [taskProject.TOGGLE_ARCHIVED](state, data) {
    if (data.is_normal) {
      modifyProjectList(state.activeProjectList, state.archivedProjectList, data)
    } else {
      modifyProjectList(state.archivedProjectList, state.activeProjectList, data)
    }
  },

  [taskProject.DELETE](state, item) {
    if (item.is_normal) {
      deleteProjectItem(state.activeProjectList, item)
    } else {
      deleteProjectItem(state.archivedProjectList, item)
    }
    if (item.current) {
      removeStorageCurrentProjectId()
    }
  },

  [taskProject.UPDATE](state, project_id, value) {
    updateProjectName(state.activeProjectList, value, project_id)
  },

  [taskProject.GET_ALL](state, data) {
    state.currentProjectId = getStorageCurrentProjectId()
    state.archivedProjectList = data.archived
    state.activeProjectList = data.normal
    state.loading = false
  },

  [taskProject.CREATE](state, item) {
    state.activeProjectList.push(item)
  },

  [taskProject.UPDATE_UNCOMPLETE_COUNT](state, action, number = 0) {
    let projects = state.activeProjectList.concat(state.archivedProjectList)
    let current = _.find(projects, {project_id: state.currentProjectId})
    if (!current) {
      return
    }
    let x = current.uncompleted_num
    if (action === 'insert' || action === 'uncompleted') {
      x += 1
    } else if (action === 'delete' || action === 'completed') {
      x += -1
    } else {
      x = number
    }
    current.uncompleted_num = x
  }

}

export default {
  state,
  mutations
}
