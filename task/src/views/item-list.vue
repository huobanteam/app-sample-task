<template>
  <div class="task">
    <div v-if='$route.params.keyword' class="task_head">
      <span>{{$route.params.keyword}}的搜索结果</span>
    </div>
    <div class="task_body">
      <h2>
        <a v-if='$route.params.project_id != 0' href="#" class="sort" >
          <menu-trigger v-if='!keyword'
                        :items='groupByItems'
                        menu-title='选择分组方式'
                        :current='groupByCurrent'
                        @menu-item-chosen='onMenuItemChoose'
                        :menu-style='{"min-width": "130px"}'>
            <i>&#xe900;</i>
            <span>{{groupByItems[groupByCurrent]}}</span>
          </menu-trigger>
        </a>
        <span v-if='$route.params.project_id != 0'>{{totalTasks}}</span>
      </h2>
      <task-input v-if='!keyword && this.group != "completed"' :group='group'></task-input>
      <div v-show='(!loading && unCompletedTasks.length == 0) || $route.params.project_id == 0' :style='{height: taskNoneHeight}' class="task_none"></div>
      <div class="page_loader" v-if='loading'><span class="loader"></span></div>
      <div v-else v-show='$route.params.project_id != 0' v-for='task in unCompletedTasks'>
        <task-group :task='task' :group='group'></task-group>
      </div>
    </div>
  </div>

  <div class='detail'>
    <router-view v-if='!isListPage'></router-view>
    <div class="detail_none" v-if='isListPage'>
      <div class="icon">
        <i>&#xe914;</i>
        <span>任务详情</span>
      </div>
    </div>
  </div>
</template>

<script>
import TaskInput from 'components/task-input'
import TaskGroup from 'components/task-group'
import MenuTrigger from 'components/common/menu-trigger'
import _ from 'lodash'
import * as SDK from 'huoban-app-sdk'
import {taskGetAllAction, taskFindAction} from 'src/vuex/actions'

export default {

  name: 'item-list',

  components: {
    TaskInput,
    TaskGroup,
    MenuTrigger
  },

  vuex: {
    actions: {
      taskGetAllAction,
      taskFindAction
    },
    getters: {
      unCompletedTasks: state => state.task.tasks,
      loading: state => state.task.getAllTaskLoading,
      projectId: state => state.route.params.project_id,
      activeProjectList: state => state.project.activeProjectList,
      archivedProjectList: state => state.project.archivedProjectList,
      keyword: state => state.route.params.keyword,
      group: state => state.task.group
    }
  },

  data() {
    return {
      groupByItems: ['按优先级分组', '按执行人分组', '按到期时间分组', '--', '已完成的任务'],
      groupByCurrent: 0,
      taskNoneHeight: window.innerHeight -160 + 'px'
    }
  },

  ready() {
    this.client = SDK.client()
    this.client.on('broadcast', result => {
      if (result.action === 'refresh' && SDK.isMobile) {
        // window.location.reload()
        this.loadItems()
      }
    })
    this.client.on('broadcast', result => {
      if (result.action === 'refresh_item' && SDK.isMobile) {
        // window.location.reload()
        this.loadItems()
      }
    })
    this.loadItems()
  },

  computed: {
    isListPage() {
      return this.$route.name === 'item-list' || !this.$route.params.item_id
    },
    totalTasks() {
      let ret = 0
      _.forEach(this.unCompletedTasks, task => {
        if (task && task.tasks) {
          ret += task.tasks.length
        }
      })
      _.forEach(this.completedTasks, task => {
        if (task && task.tasks) {
          ret += task.tasks.length
        }
      })
      return `共${ret}条`
    }
  },

  watch: {
    'projectId': {
      deep: true,
      handler: function(n, o) {
        if (o !== n) {
          this.loadItems()
          this.groupByCurrent = 0
        }
      }
    },
    'keyword': {
      deep: true,
      handler: function(n, o) {
        if (n && o !== n && n.trim() != '') {
          let params = {
            keywords: n
          }
          this.taskFindAction(params)
        }
      }
    },
    'activeProjectList': {
      deep: true,
      handler: function(n, o) {
        if (n && o !== n) {
          _.forEach(n, project => {
            if (project.project_id == this.$route.params.project_id) {
              this.client.setTitle(project.name)
            }
          })
        }
      }
    },
    'archivedProjectList': {
      deep: true,
      handler: function(n, o) {
        if (n && o !== n) {
          _.forEach(this.archivedProjectList, project => {
            if (project.project_id == this.$route.params.project_id) {
              this.client.setTitle(project.name)
            }
          })
        }
      }
    }
  },

  methods: {
    onMenuItemChoose(index) {
      let data = {
        group: 'priority'
      }
      switch (index) {
        case 0:
          data.group = 'priority'
          break
        case 1:
          data.group = 'executor'
          break
        case 2:
          data.group = 'due_on'
          break
        case 4:
        default:
          data.group = 'completed'
          break
      }
      this.taskGetAllAction(this.$route.params.project_id, data)
      this.groupByCurrent = index
    },

    loadItems() {
      let data = {
        group: 'priority'
      }
      if (this.$route.name !== 'item-search') {
        if (this.$route.params.project_id && this.$route.params.project_id != 0) {
          this.taskGetAllAction(this.$route.params.project_id, data)
        }
      } else {
        let params = {
          keywords: this.$route.params.keyword
        }
        this.taskFindAction(params)
      }
    }
  }
}
</script>