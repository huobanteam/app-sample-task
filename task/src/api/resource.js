import Vue from 'vue'
import VueResource from 'vue-resource'
import store from 'src/vuex/store'

const isPro = store.state.isPro
// const API_ROOT = isPro ? 'https://app11002.huoban.com/api/' : 'http://app11002.dev.huoban.com/api/'
const API_ROOT = isPro ? 'https://app11002.huoban.com/api/' : 'http://task.dev.huoban.com/api/'

Vue.use(VueResource)

// HTTP相关
Vue.http.options.crossOrigin = true
Vue.http.options.xhr = {withCredentials: true}

Vue.http.interceptors.push({
  request(request) {
    // 这里对请求体进行处理
    request.headers = request.headers || {}
    if ((request.url.indexOf(API_ROOT) === 0) &&
      !request.headers['X-Task-Ticket'] &&
      store.state.auth.ticket) {
      // request.headers.Authorization = 'Bearer ' + store.state.auth.ticket
      request.headers['X-Task-Ticket'] = store.state.auth.ticket
    }
    return request
  },
  response(response) {
    return response
  }
})

// ---- task resources
const taskHeaders = () => {
  return {
    headers: {
      'X-Task-Table-Id': store.state.auth.table.table_id,
      'X-Task-App-Id': store.state.auth.appId
    }
  }
}

export const TaskResource = {
  create(data) {
    return Vue.http.post(API_ROOT + 'task', data, taskHeaders())
  },

  delete(task_id) {
    return Vue.http.delete(API_ROOT + 'task/' + task_id, null, taskHeaders())
  },

  find(data) {
    return Vue.http.post(API_ROOT + 'task/find', data, taskHeaders())
  },

  update(task_id, data) {
    return Vue.http.put(API_ROOT + 'task/' + task_id, data, taskHeaders())
  },
  //bug 404
  updateOrder(project_id, data) {
    return Vue.http.post(API_ROOT + 'task/project/' + project_id + '/order', data, taskHeaders())
  },
  getAllTasks(project_id, group) {
    return Vue.http.get(API_ROOT + 'tasks/project/' + project_id, group, taskHeaders())
  },
  getTaskInfo(task_id) {
    return Vue.http.get(API_ROOT + 'task/' + task_id, null, taskHeaders())
  }
}

// ---- task project resources
export const TaskProjectResource = {
  create(data) {
    return Vue.http.post(API_ROOT + 'project', data, taskHeaders())
  },

  delete(project_id) {
    return Vue.http.delete(API_ROOT + 'project/' + project_id, null, taskHeaders())
  },

  getAll() {
    return Vue.http.get(API_ROOT + 'projects', null, taskHeaders())
  },

  update(project_id, data) {
    return Vue.http.put(API_ROOT + 'project/' + project_id, data, taskHeaders())
  },

  updateOrder(data) {
    return Vue.http.post(API_ROOT + 'project/order', data, taskHeaders())
  }
}

// ---- task comment resources
export const TaskCommentResource = {
  create(task_id, data) {
    return Vue.http.post(API_ROOT + 'comment/task/' + task_id, data, taskHeaders())
  },

  delete(comment_id) {
    return Vue.http.delete(API_ROOT + 'comment/' + comment_id, null, taskHeaders())
  },
  //bug 404
  getAll(task_id, data) {
    return Vue.http.get(API_ROOT + 'comment/task/' + task_id, data, taskHeaders())
  }
}

//----- task follow resources
export const TaskFollowResource = {
  create(task_id) {
    return Vue.http.post(API_ROOT + 'follow/task/' + task_id, null, taskHeaders())
  },

  delete(task_id) {
    return Vue.http.delete(API_ROOT + 'follow/task/' + task_id, null, taskHeaders())
  },

  getAll(task_id) {
    return Vue.http.get(API_ROOT + 'follows/task/' + task_id, null, taskHeaders())
  }
}

//----- task stream resources
export const TaskStreamResource = {
  getAll(task_id, data) {
    return Vue.http.get(API_ROOT + 'streams/task/' + task_id, data, taskHeaders())
  }
}

// ---- file resource
export const FileResource = {
  upload(file, options) {
    let data = new FormData()
    data.append('source', file, file.name)
    _.assign(options, taskHeaders())
    return Vue.http.post(API_ROOT + 'file/upload', data, options)
  }
}

