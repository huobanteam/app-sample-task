<template>
  <li class="add">
    <i>&#xe902;</i>
    <input @blur='onBlur($event)' @keyup.enter='addSubTask($event) | debounce 500'
           type="text" class="px" placeholder="添加子任务" />
  </li>
</template>

<script>
import {subTaskCreateAction} from 'src/vuex/actions'
import {isMobile} from 'huoban-app-sdk'
export default {

  name: 'sub-task-add',

  components: {

  },

  props: {
    projectId: Number
  },

  vuex: {
    actions: {
      subTaskCreateAction
    },
    getters: {

    }
  },

  methods: {
    addSubTask(e) {
      if (this.$route.params.item_id && e.target.value != '') {
        let data = {
          task_title: e.target.value,
          task_project_id: this.projectId,
          task_parent_task_id: this.$route.params.item_id
        }
        this.subTaskCreateAction(data).then((data) => {
          this.$dispatch('update-stream')
        })
        e.target.value = ''
      }
    },
    onBlur(e) {
      if (isMobile) {
        this.addSubTask(e)
      }
    }
  }
};
</script>
