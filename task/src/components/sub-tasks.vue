<template>
  <div class="subtask">
    <ol class="task_list cl">
      <li v-for='item in subTasks'>
        <sub-task-item :item='item'></sub-task-item>
      </li>
      <sub-task-add v-show='isSub' :project-id='projectId'></sub-task-add>
    </ol>
  </div>
</template>

<script>
import SubTaskItem from 'components/sub-task-item'
import SubTaskAdd from 'components/sub-task-add'
export default {

  name: 'sub-tasks',

  components: {
    SubTaskAdd,
    SubTaskItem
  },

  props: {
    isSub: Boolean,
    subTasks: Array,
    projectId: Number
  }
}
</script>