import Api from '../api'
import * as types from './types'
import $ from 'zepto'
import _ from 'lodash'
import store from './store'

// ---- error actions
export const errorShow = ({dispatch}, info) => {
  dispatch(types.error.SHOW, info)
  setTimeout(() => {
    dispatch(types.error.HIDE)
  }, 3000)
}

export const errorHide = ({dispatch}) => {
  dispatch(types.error.HIDE)
}

export const getInitInfo = ({dispatch, state}, result) => {
  dispatch(types.auth.GET_INITINFO, result)
}

// ---- task actions
export const taskGetAllAction = ({dispatch}, project_id, group, showLoading=true) => {
  dispatch(types.task.GET_ALL_REQ, group, showLoading)
  return Api.task.getAllTasks(project_id, group).then(data => {
    dispatch(types.task.GET_ALL, data.data)
    let totalTaskCount = _.reduce(data.data, (ret, group) => {
      ret += group.tasks.length
      return ret
    }, 0)
    // console.log(totalTaskCount)
    if (group.group !== 'completed') {
      dispatch(types.taskProject.UPDATE_UNCOMPLETE_COUNT, 'set', totalTaskCount)
    }
    return data.data
  }).catch(error => {
    dispatch(types.task.GET_ALL_FAIL, error)
    return error
  })
}

export const taskGetInfo = ({dispatch}, task_id, showLoading=true) => {
  dispatch(types.task.GET_TASKINFO_REQ, showLoading)
  return Api.task.getTaskInfo(task_id).then(data => {
    dispatch(types.task.GET_TASKINFO, data.data)
    return data.data
  }).catch(error => {
    dispatch(types.task.GET_TASKINFO_FAIL, error)
    return error
  })
}

export const taskDeleteFileAction = ({dispatch}, fileId) => {
  dispatch(types.task.DELETE_FILE, fileId)
}

export const taskCreateAction = ({dispatch, state}, data) => {
  dispatch(types.task.CREATE_REQ)
  return Api.task.create(data).then((result) => {
    dispatch(types.task.CREATE, result.data)
    dispatch(types.taskProject.UPDATE_UNCOMPLETE_COUNT, 'insert')
    return result
  }).catch((error) => {
    dispatch(types.task.CREATE_FAIL, error)
    errorShow(store, error.data.message)
    return error
  })
}

export const subTaskCreateAction = ({dispatch, state}, data) => {
  // dispatch(types.task.CREATE_SUB_TASK_REQ)
  return Api.task.create(data).then(result => {
    dispatch(types.task.CREATE_SUB_TASK, result.data)
    return result.data
  }).catch(err => {
    dispatch(types.error.SHOW, err.data.message)
    setTimeout(() => {
      dispatch(types.error.HIDE)
    }, 3000)
  })
}

export const subTaskUpdateAction = ({dispatch}, task_id, data) => {
  dispatch(types.task.UPDATE_SUB_TASK, task_id, data)
  return Api.task.update(task_id, data).then((result) => {
    if (data.task_due_on) {
      dispatch(types.task.UPDATE_SUB_TASK, task_id, {task_due_status: result.data.task_due_status})
    }
  }).catch(err => {
    dispatch(types.task.UPDATE_SUB_TASK_FAIL, err)
  })
}

export const taskDeleteAction = ({dispatch}, task_id, isSubTask=false) => {
  dispatch(types.task.DELETE, task_id)
  return Api.task.delete(task_id).then(({data}) => {
    if (!isSubTask) {
      dispatch(types.taskProject.UPDATE_UNCOMPLETE_COUNT, 'delete')
    }
  }).catch((error) => {
    dispatch(types.task.DELETE_FAIL, error)
    if (!isSubTask && error.data.code == 3500002) {
      dispatch(types.taskProject.UPDATE_UNCOMPLETE_COUNT, 'delete')
    }
  })
}

export const taskUpdateAction = ({dispatch}, task_id, task) => {
  dispatch(types.task.UPDATE_REQ)
  return Api.task.update(task_id, task).then(({data}) => {
    dispatch(types.task.UPDATE, data)
    if (task.task_status) {
      dispatch(types.taskProject.UPDATE_UNCOMPLETE_COUNT, task.task_status)
    }
    return data
  }).catch((error) => {
    // task update出错后显示的回退
    // 如果当前有任务列表，重载任务列表
    if (store.state.route.params.project_id) {
      // taskGetAllAction(store,
      //                  Number(store.state.route.params.project_id),
      //                  {group: store.state.task.group})
    }
    if (store.state.route.params.item_id) {
      // taskGetInfo(store, Number(store.state.route.params.item_id))
    }
    dispatch(types.task.UPDATE_FAIL, error)
    errorShow(store, error.data.message)
    throw error
  })
}

export const taskFindAction = ({dispatch}, data) => {
  data.limit = 100
  dispatch(types.task.TASK_FIND_REQ)
  Api.task.find(data).then(({data}) => {
    dispatch(types.task.TASK_FIND, data)
  }).catch((error) => {
    errorShow(store, error.data.message)
    dispatch(types.task.TASK_FIND_FAIL, error)
  })
}

// 注意 这个Action不需要dispatch
export const taskUpdateOrderAction = ({dispatch}, group_id, data) => {
  return Api.task.updateOrder(group_id, data).then(data => {
    return data
  }).catch(error => {
    errorShow(store, error.data.message)
    return error
  })
}

export const updateTaskInfoStatusAction = ({dispatch}, data) => {
  dispatch(types.task.UPDATE_TASK_INFO_STATUS, data)
}

export const taskChangedAction = ({dispatch}) => {
  dispatch(types.task.CHANGED)
}

export const taskProjectSortAction = ({dispatch}) => {
  dispatch(types.taskProject.SORT)
}

export const taskProjectSetCurrentAction = ({dispatch}, item) => {
  dispatch(types.taskProject.SET_CURRENT, item)
}

export const taskProjectToggleArchivedAction = ({dispatch},item) => {
  Api.taskProject.update(item.project_id, {is_normal: !item.is_normal}).then((data) => {
    dispatch(types.taskProject.TOGGLE_ARCHIVED, data.data)
  }).catch((error) => {
    dispatch(types.taskProject.TOGGLE_ARCHIVED_FAIL, error)
  })
}

export const taskProjectCreateAction = ({dispatch}, name) => {
  return Api.taskProject.create({name: name}).then((result) => {
    dispatch(types.taskProject.CREATE, result.data)
    return result.data
  }).catch(error => {
    dispatch(types.taskProject.CREATE_FAIL, error)
    return error
  })
}

export const taskProjectDeleteAction = ({dispatch}, item) => {
  return Api.taskProject.delete(item.project_id).then(({data}) => {
    dispatch(types.taskProject.DELETE, item)
    return data
  }).catch((error) => {
    dispatch(types.error.SHOW, error.data.message)
    setTimeout(() => {
      dispatch(types.error.HIDE)
    }, 3000)
  })
}

export const taskProjectUpdateAction = ({dispatch}, item, value) => {
  dispatch(types.taskProject.UPDATE, item.project_id, value)
  return Api.taskProject.update(item.project_id, {name: value, is_normal: item.is_normal})
  .then()
  .catch((error) => {
    dispatch(types.taskProject.UPDATE_FAIL, error)
  })
}

export const taskProjectGetAllAction = ({dispatch}, tableId) => {
  return Api.taskProject.getAll().then(({data}) => {
    dispatch(types.taskProject.GET_ALL, data, tableId)
    return data
  }).catch((error) => {
    dispatch(types.taskProject.GET_ALL_FAIL, error)
    return error
  })
}

export const taskProjectUpdateOrderAction = ({dispatch}, projectIdList, is_normal) => {
  return Api.taskProject.updateOrder({project_ids: projectIdList}).then(({data}) => {
    dispatch(types.taskProject.UPDATE_ORDER, data, is_normal)
  }).catch((error) => {
    dispatch(types.taskProject.UPDATE_ORDER_FAIL, error)
  })
}

//----task comment actions
export const taskCommentCreateAction = ({dispatch},task_id, data) => {
  return Api.taskComment.create(task_id, data).then(({data}) => {
    return data
    // dispatch(types.taskComment.CREATE, data)
  }).catch((error) => {
    dispatch(types.taskComment.CREATE_FAIL, error)
  })
}

export const taskCommentDeleteAction = ({dispatch}, comment_id) => {
  dispatch(types.taskComment.DELETE_REQ)
  Api.taskComment.delete(comment_id).then(({data}) => {
    dispatch(types.taskComment.DELETE, data)
  }).catch((error) => {
    dispatch(types.taskComment.DELETE_FAIL, error)
  })
}

export const taskCommentGetAllAction = ({dispatch}, task_id, data) => {
  dispatch(types.taskComment.GET_ALL_REQ)
  Api.taskComment.getAll(data).then(({data}) => {
    dispatch(types.taskComment.GET_ALL, data)
  }).catch((error) => {
    dispatch(types.taskComment.GET_ALL_FAIL, error)
  })
}

//----task follow actions
export const taskFollowCreateAction = ({dispatch},task_id) => {
  dispatch(types.taskFollow.CREATE_REQ, task_id)
  return Api.taskFollow.create(task_id).then(({data}) => {
    return data
  }).catch((error) => {
    dispatch(types.taskFollow.CREATE_FAIL, error)
  })
}

export const taskFollowDeleteAction = ({dispatch}, task_id) => {
  dispatch(types.taskFollow.DELETE_REQ, task_id)
  return Api.taskFollow.delete(task_id).then(({data}) => {
    return data
  }).catch((error) => {
    dispatch(types.taskFollow.DELETE_FAIL, error)
  })
}

export const taskFollowGetAllAction = ({dispatch}, task_id) => {
  return Api.taskFollow.getAll(task_id).then(({data}) => {
    return data
  }).catch((error) => {
    dispatch(types.taskFollow.GET_ALL_FAIL, error)
  })
}
//task stream actions
//isUpdate是否为用户更改任务时的即时动态
export const taskStreamGetAllAction = ({dispatch}, task_id, data, isUpdate) => {
  return Api.taskStream.getAll(task_id, data).then(({data}) => {
    dispatch(types.taskStream.GET_ALL, data, isUpdate)
    return data.load_more
  }).catch((error) => {
    dispatch(types.taskStream.GET_ALL_FAIL, error)
  })
}

export const taskStreamClearAction = ({dispatch}) => {
  dispatch(types.taskStream.CLEAR)
}

// ---- menu actions
export const menuShow = ({dispatch}, el, menuData) => {
  let offset = $(el).offset()
  menuData.position = {
    left: offset.left,
    top: offset.top + offset.height + 8
  }
  dispatch(types.menu.SHOW, menuData)
}

export const menuHide = ({dispatch, state}) => {
  if (state.menu.show) {
    dispatch(types.menu.HIDE)
  }
}

export const menuToggle = ({dispatch, state}, el, menuData) => {
  if (state.menu.show) {
    menuHide({dispatch})
  } else {
    menuShow({dispatch}, el, menuData)
  }
}

// ---- dialog actions

export const dialogShow = ({dispatch}, dialogData) => {
  return new Promise((resolve, reject) => {
    dialogData.onDialogButtonClick = (index) => {
      resolve(index)
    }
    dispatch(types.dialog.SHOW, dialogData)
  })
}

export const dialogHide = ({dispatch}) => {
  dispatch(types.dialog.HIDE)
}
