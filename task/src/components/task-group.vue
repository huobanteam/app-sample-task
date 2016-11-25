<template>
  <div>
    <h2 v-if='false'>
      <user :has-drop-down='false'></user>
    </h2>
    <h2 v-if='task.group_name != "" && task.tasks.length > 0'>
      <span>{{task.group_name}}</span>
    </h2>
    <ol v-if='(group != "due_on" && group != "completed" && !$route.params.keyword) || task.group_name == "无到期时间"'
        :class='{"task_list": true, "cl": true, "task_done": isTaskDone, "task_dragfalse": $route.params.keyword}'
        v-sortable='sortOptions'>
      <li v-for='item in task.tasks' @click='onTaskClick(item.task_id, item.task_title)' :class='{"current": item.task_id == currentTaskId}'>
        <task-item :group='group' :item='item'></task-item>
      </li>
    </ol>
    <ol v-else class='task_list cl' :class='{"task_done": isTaskDone, "task_dragfalse": isTaskDrag}'>
      <li v-for='item in task.tasks' @click='onTaskClick(item.task_id, item.task_title)' :class='{"current": item.task_id == currentTaskId}'>
        <task-item :group='group' :item='item'></task-item>
      </li>
    </ol>
  </div>
</template>

<script>
import TaskItem from 'components/task-item'
import User from 'components/user'
import {isMobile} from 'huoban-app-sdk'
import {taskUpdateOrderAction, taskGetAllAction} from 'src/vuex/actions'
import _ from 'lodash'
export default {

  name: 'task-group',

  components: {
    TaskItem,
    User
  },

  props: ['task', 'group'],

  vuex: {
    actions: {
      taskUpdateOrderAction,
      taskGetAllAction
    },
    getters: {
    }
  },

  computed: {
    isTaskDone() {
      let ret = false
      if (this.group == 'completed') {
        ret = true
      }
      return ret
    },
    isTaskDrag() {
      let ret = false
      if (this.task.group_name == '有到期时间' || this.$route.params.keyword) {
        ret = true
      }
      return ret
    }
  },

  data() {
    let delay = 0
    if (isMobile) {
      delay = 100
    }
    return {
      sortOptions: {
        sort: true,
        ghostClass: 'ghost',
        onEnd: this.onEnd,
        delay: delay
      },
      currentTaskId: this.$route.params.item_id || -1
    }
  },

  methods: {
    onEnd(evt) {
      let newOrders = []
      let sortTaskId = null
      _.forEach(this.task.tasks, (task, index) => {
        if (index != evt.oldIndex) {
          newOrders.push(task.task_id)
        } else {
          sortTaskId = task.task_id
        }
      })
      newOrders.splice(evt.newIndex, 0, sortTaskId)
      let data = {
        task_ids: newOrders,
        group: this.group,
        group_id: this.task.group_id || 0
      }
      this.taskUpdateOrderAction(this.$route.params.project_id, data).then(data => {
        if (data.status == 200) {
          let params = {
            group: this.group
          }
          this.taskGetAllAction(this.$route.params.project_id, params, false)
        }
      })
    },
    onTaskClick(task_id, task_title) {
      this.currentTaskId = task_id
      if (this.$route.params.keyword) {
        this.$redirect({name: 'search-item-detail', params: {item_id: task_id, keyword: this.$route.params.keyword}})
      } else {
        this.$redirect({name: 'item-detail', params: {item_id: task_id, project_id: this.$route.params.project_id}})
      }
    }
  }
}
</script>