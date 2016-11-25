<template>
<div class="detail_container">
  <task-action-bar v-if='isItemPage && !loading' :is-follow='followed' :item='taskInfo' :follow-num='followNum'></task-action-bar>
    <div class="page_loader" v-if='loading'><span class="loader"></span></div>
    <template v-else>
      <div v-if='taskInfo' class="detail_body">
        <div v-if='taskInfo.task_parent_task && taskInfo.task_parent_task.title' class="detail_back">
          <a @click.prevent='onReturnParentTask(taskInfo.task_parent_task.task_id)' href="#"><i>&#xe915;</i><span>{{taskInfo.task_parent_task.title}}</span></a>
        </div>
        <div class="detail_title">
          <div :class='classObj'
               @click='handleToggleComplete'>
            <i>&#xe90d;</i>
          </div>
          <input-box
            v-ref:title-box
            :text='taskInfo.task_title'
            :prevent-keys='[13]'
            @input-box-change='handleTaskTitleChange'></input-box>
        </div>
        <div class="detail_describe">
          <input-box
            v-ref:description-box
            :text='taskInfo.task_description'
            @input-box-change='handleTaskDescriptionChange'
            placeholder='任务描述'></input-box>
        </div>
        <div class="detail_section">
          <ul class="cl">
            <li>
              <div class="label">执行人：</div>
              <div class="value">
                <p v-if='taskInfo.task_executor'>
                  <user @click.prevent='selectUser' :has-drop-down='true' :user='taskInfo.task_executor'></user>
                </p>
                <p v-else>
                  <span @click.prevent='selectUser' class="add"><i>+</i><span>添加执行人</span></span>
                </p>
              </div>
            </li>
            <li>
              <div class="label">到期时间：</div>
              <div class="value">
                <p v-if='taskInfo.task_due_on != ""'>
                  <span @click.prevent='selectDate' class="date"><span>{{taskInfo.task_due_on}}</span><i>&#xe904;</i></span>
                </p>
                <p v-else>
                  <span @click.prevent='selectDate' class="add"><i>+</i><span>添加时间</span></span>
                </p>
              </div>
            </li>
            <li>
              <div class="label">附件：</div>
              <div class="value">
                <p v-for="file in taskInfo.task_files">
                  <span class="file">
                    <a href="#" @click.prevent='handleFileOpen(file)'>{{file.name}}</a>
                    <i class="del" @click='handleFileDelete(file)'>&#xe910;</i>
                  </span>
                </p>
                <uploader :task-id='taskInfo.task_id' @uploader-finished='handleUploaderFinished' v-ref:uploader>
                  <span class="add"><span>添加附件</span></span>
                </uploader>
              </div>
            </li>
          </ul>
        </div>
        <sub-tasks
                  :project-id='taskInfo.task_project'
                  :is-sub='isSub'
                  :sub-tasks='taskInfo.task_sub_tasks'
                  @update-stream='handleStreamsChange'></sub-tasks>
        <div class="comments">
          <!-- <h3>动态</h3> -->
          <task-comment-form v-if='isItemPage'
                             v-ref:comment
                             @comment-form-commit='handleCommentFormCommit'></task-comment-form>
          <task-streams></task-streams>
        </div>
      </div>
      <div class="detail_bottom" v-if='!isItemPage'>
        <task-comment-form @comment-form-commit='handleCommentFormCommit' v-ref:comment></task-comment-form>
        <task-action-bar :is-follow='followed' :item='taskInfo' :follow-num='followNum'></task-action-bar>
      </div>
    </template>
  </div>
</template>

<script>
import $ from 'zepto'
import TaskActionBar from 'components/task-action-bar'
import TaskCommentForm from 'components/task-comment-form'
import TaskStreams from 'components/task-streams'
import SubTasks from 'components/sub-tasks'
import User from 'components/user'
import Uploader from 'components/common/uploader'
import InputBox from 'components/input-box'
import {taskGetInfo, taskUpdateAction,
        taskCommentCreateAction,
        taskStreamGetAllAction,
        taskStreamClearAction,
        taskGetAllAction,
        updateTaskInfoStatusAction,
        taskDeleteFileAction,
        taskChangedAction,
        taskFollowGetAllAction,
        taskFollowCreateAction,
        taskFollowDeleteAction
      } from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
import _ from 'lodash'

export default {

  name: 'item-detail',

  components: {
    TaskActionBar,
    TaskCommentForm,
    TaskStreams,
    SubTasks,
    User,
    Uploader,
    InputBox
  },

  vuex: {
    actions: {
      taskGetInfo,
      taskUpdateAction,
      taskCommentCreateAction,
      taskStreamGetAllAction,
      taskStreamClearAction,
      taskGetAllAction,
      updateTaskInfoStatusAction,
      taskDeleteFileAction,
      taskChangedAction,
      taskFollowGetAllAction,
      taskFollowCreateAction,
      taskFollowDeleteAction
    },
    getters: {
      taskInfo: state => {
        if (state.task.taskInfo) {
          return state.task.taskInfo
        }
        let tasks = []
        state.task.tasks.forEach(group => {
          tasks = tasks.concat(group.tasks)
        })
        let currentId = Number(state.route.params.item_id)
        let task = _.find(tasks, {task_id: currentId})
        return task
      },
      loading: state => state.task.getLoading,
      group: state => state.task.group
    }
  },

  data() {
    return {
      followNum: 0,
      isFirstLoad: true,
      isClicked: false //用于单击时，先改变checkbox状态,再发请求
    }
  },

  ready() {
    this.client = SDK.client()
    this.client.on('broadcast', result => {
      if (result.action === 'refresh' && SDK.isMobile) {
        if (SDK.isAndroid) {
          this.taskGetInfo(this.$route.params.item_id, true).then(data => {
            if (data.task_title) {
              this.client.setTitle(data.task_title)
            }
          })
        } else {
          location.reload()
        }
      }
    })
  },

  created() {
    if (this.$route.name != 'item') {
      this.getAllStreams()
    }
  },

  computed: {
    isItemPage() {
      return this.$route.name === 'item'
    },
    classObj() {
      let isCompleted = this.taskInfo.task_status == 'completed'
      let ret = {
        'checkbox': true,
        'c_now': isCompleted ? false : this.taskInfo.task_due_status == 'tomorrow',
        'checked': isCompleted,
        'c_future': isCompleted ? false : this.taskInfo.task_due_status == 'today',
        'c_overdue': isCompleted ? false : this.taskInfo.task_due_status == 'expired'
      }
      return ret
    },
    isSub() {
      if (this.taskInfo.task_parent_task) {
        return false
      } else {
        return true
      }
    },
    followed() {
      if (this.taskInfo) {
        return this.taskInfo.followed
      }
      return false
    }
  },

  methods: {
    handleStreamsChange() {
      this.taskStreamGetAllAction(this.$route.params.item_id, {limit: 20}, true)
    },
    handleToggleComplete() {
      if (!this.isClicked) {
        this.isClicked = true
        let status = this.taskInfo.task_status == 'completed' ? 'uncompleted' : 'completed'
        this.updateTaskInfoStatusAction({task_status: status})//为了快速反馈
        this.updateItem({task_status: status})
      }
    },
    handleTaskTitleChange(text) {
      if (text.trim() != '') {
        this.updateItem({task_title: text})
      } else {
        this.$refs.titleBox.restoreValue()
      }
    },
    handleTaskDescriptionChange(text) {
      this.updateItem({task_description: text})
    },
    onReturnParentTask(task_id) {
      if (SDK.isMobile) {
        this.client.closeWebPage()
      } else {
        if (this.$route.name === 'search-item-detail') {
          this.$redirect({name: 'search-item-detail', params: {item_id: task_id}})
        } else if (this.$route.name === 'item-detail') {
          this.$redirect({name: 'item-detail', params: {item_id: task_id}})
        } else {
          this.$redirect({name: 'item', params: {item_id: task_id}})
        }
      }

    },
    selectUser(e) {
      let opt = {
        title: '选择执行人',
        values: []
      }
      if (this.taskInfo.task_executor && this.taskInfo.task_executor.user_id) {
        opt.values.push(this.taskInfo.task_executor.user_id)
      }
      this.client.openUserPicker(opt, this.onGetUser, e)
    },
    onGetUser(user) {
      if (user) {
        let data = {
          task_executor_id: null
        }
        if (user.users.length > 0 && user.users[0].user_id) {
          data.task_executor_id = user.users[0].user_id
        }
        this.updateItem(data)
      }
    },
    selectDate(e) {
      let opt = {
        type: 'date'
      }
      if (this.taskInfo.task_due_on != '') {
        opt.value = this.taskInfo.task_due_on
      }
      this.client.openDatePicker(opt, this.onGetDate, e)
    },
    onGetDate(value) {
      if (value) {
        let data = {
          task_due_on: value.date
        }
        this.updateItem(data)
      }
    },
    handleFileDelete(file) {
      let fileIds = _.map(this.taskInfo.task_files.filter(f => f.file_id !== file.file_id), 'file_id')
      this.updateItem({task_files: fileIds})
      this.taskDeleteFileAction(file.file_id)
    },
    handleFileOpen(file) {
      this.client.openAttachment(file)
    },
    handleUploaderFinished(files, uploadItems) {
      let fileIds = _.map(this.taskInfo.task_files.concat(files), 'file_id')
      this.updateItem({task_files: fileIds}).then(() => {
        if (this.$refs.uploader) {
          this.$refs.uploader.finishItems(uploadItems)
        }
      })
    },

    updateItem(data) {
      let updateFields = _.keys(data)
      return this.taskUpdateAction(this.$route.params.item_id, data).then(() => {
        this.client.broadcast('refresh')
        this.handleStreamsChange()
      })
      .then(data => {
        this.isClicked = false
        if (!this.group) {
          return data
        }
        let _fields = []
        switch (this.group) {
          case 'priority':
            _fields = ['task_created_on', 'task_status']
            break
          case 'executor':
            _fields = ['task_executor_id', 'task_status']
            break
          case 'due_on':
            _fields = ['task_due_on', 'task_status']
            break
          case 'completed':
            _fields = ['task_status']
            break
        }
        // 搜索结果列表永远不因为详情更改了刷新
        if (this.$route.params.project_id && _.intersection(updateFields, _fields).length > 0) {
          this.taskGetAllAction(this.$route.params.project_id, {group: this.group}, false)
        }
      })
    },

    handleCommentFormCommit(comment, replyTo) {
      let data = {content: comment}
      if (replyTo) {
        data.parent_comment_id = replyTo.data.comment.comment_id
      }
      this.taskCommentCreateAction(this.$route.params.item_id, data).then(() => {
        this.handleStreamsChange()
        if (this.$refs.comment) {
          this.$refs.comment.finish()
        }
      })
      // when comment commit finished
      if (this.$refs.comment) {
        this.$refs.comment.clean()
      }
    },

    getAllStreams() {
      let task_id = this.$route.params.item_id
      this.taskStreamClearAction()
      this.taskStreamGetAllAction(task_id, {limit: 20}, false)
    }
  },

  events: {
    'stream-item-reply': function(stream) {
      if (this.$refs.comment) {
        this.$refs.comment.setReplyTo(stream)
        this.$refs.comment.focus()
      }
    },
    'stream-item-reference': function(username) {
      this.$refs.comment.referencePeople(username)
    },
    'action-bar-follow-toggle': function() {
      if (!this.taskInfo) {
        return
      }

      if (this.taskInfo.followed) {
        this.followNum -= 1
        this.taskFollowDeleteAction(this.taskInfo.task_id)
      } else {
        this.followNum += 1
        this.taskFollowCreateAction(this.taskInfo.task_id)
      }
    }
  },

  route: {
    data(t) {
      this.client = SDK.client()
      this.taskChangedAction()
      this.$nextTick(() => {
        // console.log('task find', !!this.taskInfo, this.taskInfo.task_title)
        this.taskGetInfo(t.to.params.item_id, !this.taskInfo).then(data => {
          if (data.task_title) {
            // if (!this.client) {
            //   this.client = SDK.client()
            // }
            this.client.setTitle(data.task_title)
          }
        })
      })

      this.followNum = 0
      this.taskFollowGetAllAction(this.$route.params.item_id).then((data) => {
        this.followNum = data.length
        // console.log('followNum', this.followNum)
      })

      if (this.isItemPage) {
        $(document.body).addClass('wrap_item').removeClass('wrap_list')
      }
      if (this.$refs.comment) {
        this.$refs.comment.clean()
      }
      if (this.$refs.uploader) {
        this.$refs.uploader.cleanItems()
      }

      this.getAllStreams()
      t.next()
    }
  }
}
</script>
