<template>
  <div :class='{"task_form": true, "focus": isFocus}'>
    <div class="action"></div>
    <i class="icon">&#xe902;</i>
    <input type="text" @focus='onFocus($event)' @blur='onBlur($event)' @keypress.enter='onAddTask($event)' placeholder="添加任务"/>
  </div>
</template>

<script>
import {taskCreateAction, taskProjectCreateAction, taskGetAllAction} from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
export default {

  name: 'task-input',

  props: ['group'],

  vuex: {
    actions: {
      taskCreateAction,
      taskProjectCreateAction,
      taskGetAllAction
    },
    getters: {
    }
  },

  data() {
    return {
      user: null,
      isFocus: false
    }
  },

  ready() {
    this.client = SDK.client()
  },

  methods: {
    onAddTask(e) {
      if (e.target.value.trim() != '') {
        let data = {
          'task_title': e.target.value
        }
        if (this.$route.params.project_id != 0) {
          data.task_project_id = this.$route.params.project_id
          this.taskCreateAction(data).then(ret => {
            this.taskGetAllAction(this.$route.params.project_id, {group: this.group}, false)
            if (ret.data && ret.data.task_id) {
              this.$redirect({
                name: 'item-detail',
                params: {project_id: this.$route.params.project_id, item_id: ret.data.task_id}
              }, {ignore: true})
            }
          })
        } else {
          this.taskProjectCreateAction('默认项目').then(res => {
            if (res.project_id) {
              data.task_project_id = res.project_id
              this.taskCreateAction(data).then(ret => {
                this.taskGetAllAction(res.project_id, {group: this.group}, false)
                if (ret.data && ret.data.task_id) {
                  this.$redirect({
                    name: 'item-detail',
                    params: {project_id: res.project_id, item_id: ret.data.task_id}
                  }, {ignore: true})
                }
              })
            }
          })
        }
      }
      e.target.value = ''
      if (SDK.isMobile) {
        this.$el.getElementsByTagName('input')[0].blur()
      }
    },
    onFocus(e) {
      this.isFocus = true
    },
    onBlur(e) {
      this.isFocus = false
      if (SDK.isMobile) {
        this.onAddTask(e)
      }
    }
  }
}
</script>