<template>
<div :class='{"project_create": true, "project_none": isNoProject}'>
  <div class="form" v-show='isEditing'>
    <input type="text" class="px"
           @keyup.enter='handleEnter'
           @keypress.enter='handleEnter'
           v-model='name'
           @blur='handleCommit'/>
  </div>
  <div class="btn">
    <span  @click='handleAdd'>
      <i>&#xe902;</i>
      <em>添加新项目</em>
    </span>
  </div>
</div>
</template>

<script>
import {taskProjectCreateAction, errorShow} from 'src/vuex/actions'

export default {

  name: 'project-item-add',
  props: {
    isNoProject: Boolean
  },

  vuex: {
    actions: {
      taskProjectCreateAction,
      errorShow
    },
    getters: {
      tableId: state => state.auth.tableId,
      tableInfo: state => state.auth.table
    }
  },

  data() {
    return {
      isEditing: false,
      name: ''
    };
  },

  computed: {

  },

  methods: {
    handleAdd() {
      this.isEditing = true
      $(this.$el).children('.form').show()
      this.$el.getElementsByTagName('input')[0].focus()
    },

    handleCommit() {
      this.isEditing = false
      if (this.name.trim() != '') {
        if (this.name.trim().length > 20) {
          this.errorShow('项目名称长度不应大于20')
          this.isEditing = true
          this.$el.getElementsByTagName('input')[0].focus()
        } else {
          this.taskProjectCreateAction(this.name).then((data) => {
            this.name = ''
            this.$redirect({name: 'item-list', params: {project_id: data.project_id}, query: this.$route.query}, {ignore: true})
          })
        }
      }
    },

    handleEnter() {
      this.$el.getElementsByTagName('input')[0].blur()
    }
  }
}
</script>