<template>
<div class='wrap'>
  <div :class="containerClasses">
    <div class="menu">
      <div class="menu_inner">
        <search @search-commit='handleSearch' :is-up='true'></search>
        <div class="menu_scroll">
          <h3><i class="icon">&#xe913;</i>项目</h3>
          <active-projects></active-projects>
          <h3 v-if='isArchivedEmpty' @click='handleFold'>
            <span><i class="icon">&#xe90f;</i>归档的项目</span>
            <i class="drop" v-if='isFold'>&#xe917;</i>
            <i class="drop" v-if='!isFold'>&#xe905;</i>
          </h3>
          <archived-projects v-if='isFold'></archived-projects>
        </div>
        <search @search-commit='handleSearch' :is-up='false'></search>
      </div>
    </div>
    <router-view v-if='!isHome'></router-view>
    <div class='task' v-if='isHome'>
      <div class="task_body">
        <template v-if='hasNoProjects'>
          <task-input></task-input>
          <div class="task_none"></div>
        </template>
      </div>
    </div>
    <div class='detail' v-if='isHome'>
    </div>
  </div>
</div>
</template>

<script>
import $ from 'zepto'
import ActiveProjects from 'components/active-projects'
import ArchivedProjects from 'components/archived-projects'
import Search from 'components/search'
import TaskInput from 'components/task-input'
import {taskProjectGetAllAction, taskStreamGetAllAction, taskProjectSetCurrentAction, taskStreamClearAction, errorShow} from 'src/vuex/actions'
import _ from 'lodash'
// import Vue from 'vue'

export default {
  name: 'task-home',
  components: {
    ActiveProjects,
    ArchivedProjects,
    Search,
    TaskInput
  },

  data() {
    return {
      isFold: false,
      containerClasses: {
        container: true
      }
    }
  },

  computed: {
    isHome() {
      return this.$route.name === 'home'
    },
    isArchivedEmpty() {
      if (this.archivedProjectList.length === 0) {
        return false
      } else {
        return true
      }
    },
    hasNoProjects() {
      if (!this.loading &&
        (this.activeProjectList.length > 0 || this.archivedProjectList.length > 0)) {
        return true
      } else {
        return false
      }
    }
  },

  vuex: {
    getters: {
      archivedProjectList: state => state.project.archivedProjectList,
      activeProjectList: state => state.project.activeProjectList,
      tableId: state => state.auth.tableId,
      currentProjectId: state => state.project.currentProjectId,
      projectsLoading: state => state.project.loading
    },
    actions: {
      taskProjectGetAllAction,
      taskStreamGetAllAction,
      taskStreamClearAction,
      taskProjectSetCurrentAction,
      errorShow
    }
  },

  route: {
    activate(transition) {
      transition.next()
    },
    data(transition) {
      if (this.$route.name === 'item-list') {
        let projectId = Number(this.$route.params.project_id)
        if (projectId) {
          this.taskProjectSetCurrentAction({project_id: projectId})
        }
      }
      $(document.body).removeClass('wrap_item').addClass('wrap_list')

      if (this.$route.name !== 'home') {
        this.containerClasses = {
          container: true,
          task_show: true
        }
      } else {
        this.containerClasses = {
          container: true
        }
      }

      transition.next()
    }
  },

  created() {
    this.taskProjectGetAllAction(this.tableId).then(({normal = [], archived = []}) => {
      let currentProjectId = this.currentProjectId
      let projects = normal.concat(archived)
      if (projects.length == 0) {
        this.$redirect({
          name: 'item-list',
          params: {project_id: 0}
        }, {ignore: true})
      }
      let find = _.find(projects, {project_id: currentProjectId})
      if (!find) {
        if (projects.length > 0) {
          currentProjectId = projects[0].project_id
        } else {
          currentProjectId = null
        }
      }

      if (currentProjectId && this.$route.name === 'home') {
        this.$redirect({
          name: 'item-list',
          params: {project_id: currentProjectId},
          query: this.$route.query
        }, {ignore: true})
      }
    })
  },

  ready() {
    // $(window).on('orientationchange', () => {

    // }, false)
  },

  beforeDestroy() {
    // $(window).off('orientationchange')
  },

  methods: {
    handleFold() {
      this.isFold = !this.isFold
    },

    handleSearch(keyword) {
      this.$redirect({name: 'item-search',params: {keyword: keyword}})
    },

    handleErrorClick() {
      this.errorShow('123')
    }
  }
}
</script>
