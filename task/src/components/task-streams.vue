<template>
  <ul class="cl">
    <task-stream-item v-for='item in streamsList' :item='item'></task-stream-item>
    <div class="more" v-if='isLoading'>
      <span>加载中...</span>
    </div>
    <div class="more" v-show='hasStreams && !isLoading'>
      <a @click='handleMore'>查看更多</a>
    </div>
  </ul>
</template>

<script>
import TaskStreamItem from 'components/task-stream-item'
import {taskStreamGetAllAction, taskStreamClearAction} from 'src/vuex/actions'
export default {

  name: 'task-streams',

  components: {
    TaskStreamItem
  },

  vuex: {
    actions: {
      taskStreamGetAllAction,
      taskStreamClearAction
    },
    getters: {
      hasStreams: state => state.stream.hasStreams,
      streamsList: state => state.stream.streamsList
    }
  },

  data() {
    return {
      isLoading: false
    };
  },

  methods: {
    handleMore() {
      this.isLoading = true
      let len = this.streamsList.length
      let lastStreamId = this.streamsList[len-1].stream_id
      let task_id = this.$route.params.item_id
      this.taskStreamGetAllAction(task_id, {limit: 20, last_stream_id: lastStreamId}).then(data => {
        this.isLoading = false
      })
    }
  }
}
</script>