<template>
  <ul :class='{"project_menu":true, "cl": true, "clear_hover": clearHover}'
      v-k-sortable='sortOption'
      v-el:sort-list
      @mouseenter='handleMouseEnter'
      style="position:relative;">
    <li v-for='item in activeProjectList'>
      <project-item
      :item='item'
      track-by='project_id'>
      </project-item>
    </li>
  </ul>
  <project-item-add :is-no-project='isNoProject'></project-item-add>
  <div v-el:cover-ele v-show='isCover' class="project_mask"></div>
</template>

<script>
import ProjectItem from 'components/project-item'
import ProjectItemAdd from 'components/project-item-add'
import {taskProjectUpdateOrderAction} from 'src/vuex/actions'
import {isMobile} from 'huoban-app-sdk'
export default {

  name: 'active-projects',

  components: {
    ProjectItem,
    ProjectItemAdd
  },

  vuex: {
    actions: {
      taskProjectUpdateOrderAction
    },
    getters: {
      editing: state => state.project.editing,
      activeProjectList: state => state.project.activeProjectList
    }
  },

  data() {
    return {
      clearHover: false,
      isCover: false,
      sortOption: {
        sort: true,
        animation: 150,
        onStart: this.handleSortStart,
        onEnd: this.handleSortEnd,
        ghostClass: 'ghost',
        disabled: false,
        delay: isMobile ? 200 : 0,
        handle: '.my-handle'
      }
    }
  },

  computed: {
    isNoProject() {
      if (this.activeProjectList.length == 0) {
        return true
      } else {
        return false
      }
    }
  },
  methods: {
    handleSortStart(evt) {
      this.clearHover = true
    },
    handleSortEnd(evt) {
      this.sortOption.disabled = !this.sortOption.disabled
      this.isCover = true
      this.computeCoverAttr()
      this.clearHover = true
      let projectIdList = []
      this.activeProjectList.forEach((item) => {
        projectIdList.push(item.project_id)
      })
      let tempId = projectIdList.splice(evt.oldIndex,1)[0]
      projectIdList.splice(evt.newIndex, 0, tempId)
      this.taskProjectUpdateOrderAction(projectIdList, true).then(data => {
        this.sortOption.disabled = !this.sortOption.disabled
        this.isCover = false
      })
    },
    handleMouseEnter() {
      this.clearHover = false
    },
    computeCoverAttr() {
      let sortElePosition = $(this.$els.sortList).position()
      $(this.$els.coverEle).width($(this.$els.sortList).width())
      $(this.$els.coverEle).height($(this.$els.sortList).height())
      $(this.$els.coverEle).css({
        left: sortElePosition.left,
        top: sortElePosition.top
      })
    }
  }
}
</script>
