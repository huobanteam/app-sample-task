<template>
  <div :class='classNames'
       @click='handleCurrent'>
    <div class="drop">
      <menu-trigger :items='['恢复项目', '删除项目']'
                    :click-interaction='{selector: "li", toggleClass: "a"}'
                    @menu-item-chosen='handleMenuItemClick'>
        <i>&#xe904;</i>
      </menu-trigger>
    </div>
    <div class="option">
      <menu-trigger :items='["恢复项目", "删除项目"]'
                    @menu-item-chosen='handleMenuItemClick'>
        <i>&#xe91b;</i>
      </menu-trigger>
    </div>
    <div class="name">{{item.name}}</div>
  </div>
</template>

<script>
import MenuTrigger from 'components/common/menu-trigger'
import deleteProject from 'components/delete-project'
import {taskProjectSetCurrentAction,
        taskProjectToggleArchivedAction,
        dialogShow,
        taskProjectDeleteAction
      } from 'src/vuex/actions'
export default {

  name: 'archived-project-item',

  components: {
    MenuTrigger
  },

  props: {
    item: Object
  },

  mixins: [deleteProject],

  vuex: {
    actions: {
      taskProjectSetCurrentAction,
      taskProjectToggleArchivedAction,
      dialogShow,
      taskProjectDeleteAction
    },
    getters: {
      currentProjectId: (state) => Number(state.route.params.project_id)
    }
  },

  data() {
    return {
      isCurrent: false
    };
  },

  computed: {
    classNames() {
      let ret = {}
      ret.current = this.item.project_id === this.currentProjectId
      ret['my-handle'] = true
      ret['menu_item'] = true
      return ret
    }
  },

  methods: {
    handleMenuItemClick(menuItemIndex) {
      switch (menuItemIndex) {
        case 0:
          this.taskProjectToggleArchivedAction(this.item)
          break
        case 1:
          this.dialogShow({
            title: '删除项目',
            subject: '确定删除此项目？',
            content: '其包含的任务也将一起删除',
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
              this.deleteProject()
            }
          })
      }
    },
    handleCurrent() {
      this.$redirect({name: 'item-list', params: {project_id: this.item.project_id}})
    }
  }
}
</script>