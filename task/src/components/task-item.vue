<template>
  <div :class='{"item": true, "item_done": item.task_status == "completed"}'>
    <div @click.stop='onSelectUser' v-if='temUser' :class='{"item_assign": true, "show": isShowUser}'>
      <img :src='comUserLink' />
    </div>
    <div @click.stop='onSelectUser' v-else :class='{"item_assign": true, "show": isShowUser}'>
      <i>&#xe909;</i>
    </div>
    <div @click.stop='onSelectDate' v-if='item.task_due_on != "" ' :class='dateClass'>
      {{tempDate}}
    </div>
    <div @click.stop='onSelectDate' v-else :class='{"item_date": true, "show": isShowDate}'>
      <i>&#xe90a;</i>
    </div>
    <div @click.stop='onTaskCheck(item.task_id, item.task_status)' :class='checkBoxClass'><i>&#xe90d;</i></div>
    <div class="item_title"><span>{{item.task_title}}</span></div>
  </div>
</template>

<script>
import {taskUpdateAction, taskGetAllAction, taskFindAction} from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
import dateUtil from 'src/utils/date'
import _ from 'lodash'

export default {

  name: 'task-item',
  props: ['item', 'group'],
  vuex: {
    actions: {
      taskUpdateAction,
      taskGetAllAction,
      taskFindAction
    },
    getters: {

    }
  },
  data() {
    return {
      showUserAndDate: false,
      isCheck: this.item.task_status == 'completed',
      tempUserLink: '',
      tempDate: this._dueOnFriendly(this.item.task_due_on || ''),
      temUser: this.item.task_executor
    }
  },
  ready() {
    this.client = SDK.client()
  },
  computed: {
    comUserLink() {
      let ret
      if (this.item.task_executor) {
        ret = this.item.task_executor.avatar
      } else {
        if (this.tempUserLink != '') {
          ret = this.tempUserLink
        }
        this.temUser = null
      }
      return ret
    },
    isShowUser() {
      let ret = false
      if (this.item.task_due_on != '' || this.item.task_executor || this.showUserAndDate) {
        ret = true
      }
      return ret
    },
    isShowDate() {
      let ret = false
      if (this.item.task_due_on != '' || this.showUserAndDate) {
        ret = true
      }
      return ret
    },
    checkBoxClass() {
      let ret = {
        'checkbox': true,
        'c_now': this.item.task_due_status == 'tomorrow',
        'checked': this.isCheck,
        'c_future': this.item.task_due_status == 'today',
        'c_overdue': this.item.task_due_status == 'expired'
      }
      if (this.isCheck) {
        ret.c_now = false
        ret.c_future = false
        ret.c_overdue = false
      } else {
        ret.c_now = this.item.task_due_status == 'tomorrow'
        ret.c_future = this.item.task_due_status == 'today'
        ret.c_overdue = this.item.task_due_status == 'expired'
      }
      return ret
    },
    dateClass() {
      let ret = {
        'item_date': true,
        'show': this.isShowDate,
        'd_overdue': this.item.task_due_status == 'expired' && this.item.task_status != 'completed',
        'd_now': this.item.task_due_status == 'tomorrow' && this.item.task_status != 'completed',
        'd_future': this.item.task_due_status == 'today' && this.item.task_status != 'completed'
      }
      return ret
    }
  },
  methods: {
    onTaskCheck(task_id, task_status) {
      let status = task_status == 'completed' ? 'uncompleted' : 'completed'
      if (status == 'completed') {
        this.isCheck = true
      } else {
        this.isCheck = false
      }
      let data = {
        task_status: status
      }
      this.taskUpdateAction(task_id, data).then(res => {
        if (this.$route.params.project_id) {
          this.taskGetAllAction(this.$route.params.project_id, {group: this.group}, false)
        }
      })
    },
    onSelectUser(e) {
      if (!SDK.isMobile) {
        this.showUserAndDate = true
        let opt = {
          values: []
        }
        if (this.item.task_executor) {
          opt.values.push(this.temUser)
        }
        this.client.openUserPicker(opt, this.onGetUser, e)
      }
    },
    onGetUser(data, err) {
      if (data) {
        let postData = {
          task_executor_id: null
        }
        if (data.users.length > 0 && data.users[0].user_id) {
          this.tempUserLink = data.users[0].avatar.medium_link ? data.users[0].avatar.medium_link : data.users[0].avatar
          this.temUser = data.users[0]
          postData.task_executor_id = data.users[0].user_id
        } else {
          this.temUser = null
        }
        this.taskUpdateAction(this.item.task_id, postData).then(res => {
          this.showUserAndDate = false
          if (this.$route.params.project_id) {
            this.taskGetAllAction(this.$route.params.project_id, {group: this.group}, false)
          }
        })
      } else if (err && err.cancelled) {
        this.showUserAndDate = false
      }
    },
    onSelectDate(e) {
      if (!SDK.isMobile) {
        this.showUserAndDate = true
        let opt = {
          type: 'date'
        }
        opt.value = this.item.task_due_on
        this.client.openDatePicker(opt, this.onGetDate, e)
      }
    },
    onGetDate(value, err) {
      if (value) {
        let data = {
          task_due_on: value.date
        }
        this.tempDate = this._dueOnFriendly(value.date)
        this.taskUpdateAction(this.item.task_id, data).then(res => {
          this.showUserAndDate = false
          if (this.$route.params.project_id) {
            this.taskGetAllAction(this.$route.params.project_id, {group: this.group}, false)
          }
        })
      } else if (err && err.cancelled) {
        this.showUserAndDate = false
      }
    },
    _dueOnFriendly(dueOn) {
      if (!dueOn) {
        return ''
      }

      let map = {1: '一', 2: '二', 3: '三', 4: '四', 5: '五', 6: '六', 7: '日'}
      let dueOnTs = dateUtil.toTime(dueOn)
      let dueOnYear = dateUtil.format('Y', dueOnTs)
      let now = _.now() / 1000
      let todayEarliestTs = dateUtil.toTime(dateUtil.format('Y-m-d 00:00:00', now))
      let todayWeek = dateUtil.format('w', now)
      // 周日最大，不跟国外一样
      if (todayWeek == 0) {
        todayWeek = 7
      }
      let thisWeekEarliestTs = todayEarliestTs - 86400 * (todayWeek - 1)
      let nextWeekEarliestTs = thisWeekEarliestTs + 86400 * 7
      let nextWeekLatestTs = nextWeekEarliestTs + 86400 * 6
      let thisYear = dateUtil.format('Y', now)

      if (dueOnTs < todayEarliestTs) {
        if (dueOnTs == todayEarliestTs - 86400) {
          return '昨天'
        } else if (dueOnTs >= thisWeekEarliestTs) {
          return `周${map[(dueOnTs - thisWeekEarliestTs) / 86400 + 1]}`
        }
      } else if (dueOnTs == todayEarliestTs) {
        return '今天'
      } else {
        if (dueOnTs == todayEarliestTs + 86400) {
          return '明天'
        } else if (dueOnTs < nextWeekEarliestTs) {
          return `周${map[(dueOnTs - thisWeekEarliestTs) / 86400 + 1]}`
        } else if (dueOnTs <= nextWeekLatestTs) {
          return `下周${map[(dueOnTs - nextWeekEarliestTs) / 86400 + 1]}`
        }
      }

      if (dueOnYear == thisYear) {
        return dateUtil.format('n.j', dueOnTs)
      } else {
        return dateUtil.format('Y.n.j', dueOnTs)
      }
    }
  },
  watch: {
    item: {
      deep: true,
      handler: function(o, n) {
        this.tempUserLink = ''
        this.tempDate = this._dueOnFriendly(this.item.task_due_on || '')
        this.isCheck = (n.task_status === 'completed')
      }
    }
  }
}
</script>