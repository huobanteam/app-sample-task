<template>
  <li @click='handleClick'>
    <div :class='{"item": true, "item_done": item.task_status == "completed"}'>
      <div class="more"><i>&#xe906;</i></div>
      <div
          @click.stop='selectUser'
          v-if='selected'
          :class='{"item_assign": true, "show": isShowUser}'>
        <img :src='imgLink' />
      </div>
      <div
          @click.stop='selectUser'
          v-else
          :class='{"item_assign": true, "show": isShowUser}'>
        <i>&#xe909;</i>
      </div>
      <div
          @click.stop='selectDate'
          v-if='item.task_due_on != ""'
          :class='dateClass'>
        {{friendDate}}
      </div>
      <div
          @click.stop='selectDate'
          v-else
          :class='{"item_date": true, "show": isShowDate}'>
        <i>&#xe90a;</i>
      </div>
      <div
          @click.stop='onCheckItem(item.task_id, item.task_status)'
          :class='classObj'>
        <i>&#xe90d;</i>
      </div>
      <div class="item_title">
        <span>{{item.task_title}}</span>
      </div>
    </div>
  </li>
</template>

<script>
import * as SDK from 'huoban-app-sdk'
import {taskUpdateAction,
        taskGetAllAction,
        subTaskUpdateAction} from 'src/vuex/actions'
import _ from 'lodash'
import dateUtil from 'src/utils/date'
export default {

  name: 'sub-task-item',

  props: ['item'],

  vuex: {
    actions: {
      taskUpdateAction,
      taskGetAllAction,
      subTaskUpdateAction
    },
    getters: {

    }
  },

  data() {
    return {
      isClicked: false,
      showUser: false,
      showDate: false,
      selected: this.item.task_executor,
      imgLink: this.item.task_executor ? this.item.task_executor.avatar : ''
    };
  },

  computed: {
    isShowUser() {
      let ret = false
      if (this.item.task_executor || this.showUser || this.showDate || this.item.task_due_on != '') {
        ret = true
      }
      return ret
    },
    isShowDate() {
      let ret = false
      if (this.item.task_due_on != '' || this.showDate) {
        ret = true
      }
      return ret
    },
    classObj() {
      let ret = {
        'checkbox': true,
        'c_now': this.item.task_due_status == 'tomorrow',
        'checked': this.item.task_status == 'completed',
        'c_future': this.item.task_due_status == 'today',
        'c_overdue': this.item.task_due_status == 'expired'
      }
      if (this.item.task_status == 'completed') {
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
    friendDate() {
      return this._dueOnFriendly(this.item.task_due_on)
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

  ready() {
    this.client = SDK.client()
  },

  methods: {
    onCheckItem(task_id, task_status) {
      if (!this.isClicked) {
        this.isClicked = true
        let status = task_status == 'completed' ? 'uncompleted' : 'completed'
        let data = {
          task_status: status
        }
        this.subTaskUpdateAction(this.item.task_id, data).then(data => {
          this.isClicked = false
        })
      }
    },
    selectUser(e) {
      let opt = {
        values: []
      }
      if (this.item.task_executor) {
        opt.values.push(this.item.task_executor)
      }
      this.client.openUserPicker(opt, this.onGetUser, e)
    },
    onGetUser(ret) {
      if (ret) {
        let data = {
          task_executor_id: null
        }
        if (ret.users.length > 0 && ret.users[0].user_id) {
          data.task_executor_id = ret.users[0].user_id
          this.selected = true
          this.imgLink = ret.users[0].avatar.small_link ? ret.users[0].avatar.small_link : ret.users[0].avatar
        }
        this.showUser = true
        this.subTaskUpdateAction(this.item.task_id, data).then(data => {
          this.client.broadcast('refresh')
        })
      }
    },
    selectDate(e) {
      let opt = {
        type: 'date'
      }
      if (this.item.task_due_on != '') {
        opt.value = this.item.task_due_on
      }
      this.client.openDatePicker(opt, this.onGetDate, e)
    },
    onGetDate(value) {
      if (value) {
        let data = {
          task_due_on: value.date
        }
        this.showDate = true
        this.subTaskUpdateAction(this.item.task_id, data).then(data => {
          this.client.broadcast('refresh')
        })
      }
    },
    handleClick() {
      let name = this.$route.name
      this.$redirect({name: name, params: {item_id: this.item.task_id}})
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
  }
}
</script>