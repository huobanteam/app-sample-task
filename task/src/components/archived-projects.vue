<template>
  <ul class="archive_menu cl">
    <li v-for='item in archivedProjectList'>
      <archived-project-item
        :item='item'
        track-by='project_id'>
      </archived-project-item>
    </li>
  </ul>
</template>

<script>
import ArchivedProjectItem from 'components/archived-project-item'
import {taskProjectUpdateOrderAction} from 'src/vuex/actions'
export default {

  name: 'archived-projects',

  components: {
    ArchivedProjectItem
  },

  vuex: {
    actions: {
      taskProjectUpdateOrderAction
    },
    getters: {
      archivedProjectList: state => state.project.archivedProjectList
    }
  },

  computed: {
  },

  methods: {
  }
}
</script>
