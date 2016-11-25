<template>
  <div class="detail_action">
    <a href="#" class="del">
      <i @click='handleDel'>&#xe903;</i>
      <span @click='handleDel'>删除</span>
    </a>
    <div class="follow">
      <a
         href="#"
         @click='handleFollow'
         :class='{"a": isFollow}'
         :title='tips'><i></i></a>
      <span>{{followNum}}人关注</span>
    </div>
  </div>
</template>

<script>
import {taskFollowCreateAction,
        taskFollowDeleteAction,
        taskFollowGetAllAction,
        taskDeleteAction,
        taskGetAllAction,
        dialogShow} from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
export default {

  name: 'task-action-bar',

  vuex: {
    actions: {
      taskFollowCreateAction,
      taskFollowDeleteAction,
      taskFollowGetAllAction,
      taskDeleteAction,
      taskGetAllAction,
      dialogShow
    },
    getters: {
      group: (state) => state.task.group
    }
  },

  props: {
    isFollow: Boolean,
    item: Object,
    followNum: Number
  },

  computed: {
    tips() {
      if (this.isFollow) {
        return '取消关注'
      } else {
        return '关注后，有变化会通知您'
      }
    }
  },

  ready() {
    this.client = SDK.client()
  },

  methods: {
    handleFollow() {
      this.$dispatch('action-bar-follow-toggle')
    },
    handleDel() {
      this.dialogShow({
        title: '删除任务',
        subject: '确定删除此任务？',
        content: (this.item.task_sub_tasks && this.item.task_sub_tasks.length > 0) ? '其包含的子任务也将一起删除' : '',
        buttons: [
          {
            label: '取消',
            classes: {'pn_normal': true}
          },
          {
            label: '删除',
            classes: {'pn_delete': true}
          }
        ]
      }).then((index) => {
        if (index === 1) {
          this.taskDeleteAction(this.$route.params.item_id, !!this.item.task_parent_task).then(() => {
            this.client.broadcast('refresh')
            this.$redirect({name: 'item-list',
              params: {
                project_id: this.$route.params.project_id
              },
              query: this.$route.query
            }, {close: true})
          })
        }
      })
    }
  }
}
</script>
