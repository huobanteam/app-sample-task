// 对于异步操作，预定义发起(后缀`REQ`)、成功(无后缀)、失败(后缀`FAIL`) 三种状态
// 方便业务使用

import _ from 'lodash'

const cv = (arr) => {
  return _.reduce(arr, (ret, val) => {
    if (val) {
      ret[val] = val
    }
    return ret
  }, {})
}

const resourceMaker = (ajaxResources=[], normalResources=[], prefix='') => {
  let ret = _.reduce(ajaxResources, (ret, val) => {
    ret[`${val}`] = `${prefix}${val}`
    ret[`${val}_REQ`] = `${prefix}${val}_REQ`
    ret[`${val}_FAIL`] = `${prefix}${val}_FAIL`
    return ret
  }, {})

  ret = _.reduce(normalResources, (ret, val) => {
    ret[`${val}`] = `${prefix}${val}`
    return ret
  }, ret)

  return ret
}

export const task = resourceMaker([
  'CREATE',
  'DELETE',
  'UNCOMPLETED_COUNT',
  'UPDATE',
  'UPDATE_ORDER',
  'GET_ALL',
  // 'GET_ALL_FAIL',
  // 'GET_ALL_COMPLETED_REQ',
  // 'GET_ALL_COMPLETED',
  // 'GET_ALL_COMPLETED_FAIL',
  // 'GET_TASKINFO_REQ',
  'GET_TASKINFO',
  'UPDATE_TASK_INFO_STATUS',
  // 'GET_TASKINFO_FAIL'
  'CREATE_SUB_TASK',
  'UPDATE_SUB_TASK',
  'TASK_FIND'
], [
  'DELETE_FILE',
  'CHANGED'
], 'TASK_')

// console.log(task)

export const taskComment = resourceMaker([
  'CREATE',
  'DELETE'
], [], 'TASK_COMMENT_')

export const taskFollow = resourceMaker([
  'CREATE',
  'DELETE'
], [], 'TASK_FOLLOW_')

export const taskProject = resourceMaker([
  'CREATE',
  'DELETE',
  'GET_ALL',
  'UPDATE',
  'SORT',
  'UPDATE_ORDER'
], [
  'SET_CURRENT',
  'SET_EDITING',
  'TOGGLE_ARCHIVED',
  'UPDATE_UNCOMPLETE_COUNT'
], 'TASK_PROJECT_')

export const taskStream = resourceMaker([
  'GET_ALL',
  'CLEAR',
  'GET_ALL_FAIL'
], [], 'TASK_STREAM_')

export const auth = cv([
  'GET_INITINFO'
])

export const menu = resourceMaker([], [
  'SHOW',
  'HIDE'
], 'MENU_')

export const dialog = resourceMaker([], [
  'SHOW',
  'HIDE'
], 'DIALOG_')

export const error = resourceMaker([], [
  'SHOW',
  'HIDE'
], 'ERROR_')
