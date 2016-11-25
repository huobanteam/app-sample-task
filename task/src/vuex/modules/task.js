// import Vue from 'vue'
import {
  task,
  taskFollow
} from '../types'
import _ from 'lodash'
import store from '../store'

// 子模块的数据，作为全局store下state的sub-tree
const state = {
  projects: {},
  createLoading: false,
  getAllTaskLoading: false,
  group: 'priority',
  tasks: [],
  taskInfo: null,
  getLoading: false
}

function findTaskById(state, task_id) {
  let task = null
  if (state.taskInfo && state.taskInfo.task_id === task_id) {
    task = state.taskInfo
    return task
  }

  let tasks = []
  state.tasks.forEach(group => {
    tasks = tasks.concat(group.tasks)
  })

  task = _.find(tasks, {task_id})

  return task
}

function updateTasks(state, task) {
  if (state.tasks) {
    state.tasks.forEach(group => {
      var index = _.findIndex(group.tasks, (_task) => _task.task_id === Number(task.task_id))
      if (index != -1) {
        group.tasks.splice(index,1, task)
        return false
      }
    })
  }
}

// 对子模块数据的操作
const mutations = {
  [task.CREATE](state, task) {
    state.createLoading = false
  },
  [task.CREATE_REQ](state) {
    state.createLoading = true
  },
  [task.CREATE_FAIL](state) {
    state.createLoading = false
  },
  [task.CREATE_SUB_TASK_REQ](state) {
    state.createLoading = false
  },
  [task.CREATE_SUB_TASK](state, task) {
    if (state.taskInfo) {
      state.taskInfo.task_sub_tasks.push(task)
    }
  },
  [task.CREATE_SUB_TASK_FAIL](state) {
    state.createLoading = false
  },

  [task.UPDATE_SUB_TASK](state, sub_task_id, updateFields) {
    let index = _.findIndex(state.taskInfo.task_sub_tasks, (_subTask) => _subTask.task_id === sub_task_id)
    if (index != -1) {
      let _sub_task = state.taskInfo.task_sub_tasks[index]
      for (let valueName in updateFields) {
        _sub_task[valueName] = updateFields[valueName]
      }
    }
  },

  [task.GET_ALL_REQ](state, {group}, showLoading) {
    state.group = group
    state.getAllTaskLoading = showLoading
  },
  [task.GET_ALL](state, tasks) {
    state.getAllTaskLoading = false
    state.tasks = tasks
  },
  [task.GET_ALL_FAIL](state, err) {
    state.getAllTaskLoading = false
  },
  [task.UPDATE_REQ](state) {

  },
  [task.UPDATE](state, task) {
    if (Number(store.state.route.params.item_id) === task.task_id) {
      state.taskInfo = task
    }
    updateTasks(state, task)
  },
  [task.UPDATE_FAIL](state, err) {
    console.log('update-fail')
  },
  [task.GET_TASKINFO_REQ](state, showLoading) {
    state.taskInfo = null
    state.getLoading = showLoading
  },
  [task.GET_TASKINFO](state, taskInfo) {
    state.taskInfo = taskInfo
    state.getLoading = false
    updateTasks(state, taskInfo)
  },
  [task.UPDATE_TASK_INFO_STATUS](state, data) {
    state.taskInfo.task_status = data.task_status
  },
  [task.GET_TASKINFO_FAIL](state, taskInfo) {
    state.getLoading = false
  },
  [task.DELETE](state, task_id) {
    if (state.tasks && state.tasks.length > 0) {
      try {
        state.tasks.forEach(group => {
          var index = _.findIndex(group.tasks, (_task) => _task.task_id === Number(task_id))
          if (index != -1) {
            group.tasks.splice(index,1)
            throw new Error('breakLoop')
          }
        })
      } catch (e) {}
    }
  },
  [task.TASK_FIND_REQ](state) {
    state.getAllTaskLoading = true
  },
  [task.TASK_FIND](state, tasks) {
    state.getAllTaskLoading = false
    if (tasks.tasks.length > 0) {
      let temTasks = [{
        group_name: '',
        tasks: tasks.tasks
      }]
      state.tasks = temTasks
    } else {
      state.tasks = []
    }
  },
  [task.TASK_FIND_FAIL](state) {
    state.getAllTaskLoading = false
  },
  [task.DELETE_FILE](state, fileId) {
    if (state.taskInfo) {
      let files = state.taskInfo.task_files
      let index = _.findIndex(files, {file_id: fileId})
      files.splice(index, 1)
      state.taskInfo.task_files = files
    }
  },
  [task.CHANGED](state) {
    state.taskInfo = null
  },
  [taskFollow.CREATE_REQ](state, task_id) {
    let task = findTaskById(state, task_id)
    if (task) {
      task.followed = true
    }
  },
  [taskFollow.DELETE_REQ](state, task_id) {
    let task = findTaskById(state, task_id)
    if (task) {
      task.followed = false
    }
  }
}

export default {
  state,
  mutations
}
