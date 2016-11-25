<template>
  <div :class='classNames'
      @dblclick='handleEdit'
      @click='handleClick'>
    <div v-show='!isEditing'>
      <div class="drop">
        <menu-trigger :items='["编辑名称", "归档项目", "删除项目"]'
                      :click-interaction='{selector: 'li', toggleClass: 'a'}'
                      @menu-item-chosen='handleMenuItemClick'>
          <i>&#xe904;</i>
        </menu-trigger>
      </div>
      <div class="option">
        <menu-trigger :items='["编辑名称", "归档项目", "删除项目"]'
                      @menu-item-chosen='handleMenuItemClick'>
          <i>&#xe91b;</i>
        </menu-trigger>
      </div>
      <div class="icon"></div>
      <div class="name">{{itemName}}</div>
      <div class="num" v-if='item.uncompleted_num!=0'>{{item.uncompleted_num}}</div>
    </div>
    <input
      type="text" class="px"
      @keyup.enter='handleCommit'
      @blur='handleBlur'
      @keyup.esc='handleBlur'
      v-model='itemName'
      v-if='isEditing'/>
  </div>
</template>

<script>
import MenuTrigger from 'components/common/menu-trigger'
import deleteProject from 'components/delete-project'
import Vue from 'vue'
import {taskProjectDeleteAction,
        taskProjectToggleArchivedAction,
        taskProjectUpdateAction,
        dialogShow,
        errorShow
      } from 'src/vuex/actions'

let tempName = ''
export default {
  name: 'project-item',

  components: {
    MenuTrigger
  },

  props: {
    item: Object
  },

  mixins: [deleteProject],

  vuex: {
    actions: {
      taskProjectDeleteAction,
      taskProjectToggleArchivedAction,
      taskProjectUpdateAction,
      dialogShow,
      errorShow
    },
    getters: {
      currentProjectId: (state) => Number(state.route.params.project_id)
    }
  },

  data() {
    return {
      isEditing: false
    }
  },

  computed: {
    classNames() {
      let ret = {}
      if (this.isEditing) {
        ret['edit'] = true
      } else {
        ret.current = (this.item.project_id === this.currentProjectId)
        ret['my-handle'] = true
      }
      ret['menu_item'] = true
      return ret
    },
    itemName: {
      get() {
        return this.item.name
      },

      set(val) {
        tempName = val
      }
    }
  },

  methods: {
    handleMenuItemClick(menuItemIndex) {
      switch (menuItemIndex) {
        case 0:
          this.isEditing = true
          Vue.nextTick(() => {
            this.$el.getElementsByTagName('input')[0].focus()
          })
          $(this.$el).find('input').show().focus()
          break
        case 1:
          this.taskProjectToggleArchivedAction(this.item)
          break
        case 2:
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

    handleEdit() {
      this.isEditing = true
      Vue.nextTick(() => {
        this.$el.getElementsByTagName('input')[0].focus()
      })
      $(this.$el).find('input').show().focus()
    },

    handleCommit() {
      this.isEditing = false
      if (tempName.trim() != '') {
        if (tempName.trim().length > 20) {
          tempName = tempName.substring(0,20)
        }
        this.taskProjectUpdateAction(this.item, tempName)
      } else {
        this.itemName = this.item.name
      }
    },

    handleBlur(e) {
      this.isEditing = false
      this.handleCommit()
    },

    handleClick(evt) {
      if (evt.target.nodeName.toLowerCase() != 'input') {
        this.isEditing = false
        this.$redirect({name: 'item-list',params: {project_id: this.item.project_id}})
      }
    }
  }
}
</script>