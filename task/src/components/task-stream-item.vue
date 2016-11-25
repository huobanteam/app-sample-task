<template>
  <li stream-id='{{item.stream_id}}'>
    <template v-if='item.action === "comment_created"'>
      <div class="avatar" @click='handleUserClick'>
        <img :src='item.created_by.avatar' />
      </div>
      <div class="c_h">
        <a href="#" class="y" @click.prevent='handleReply'>
          <i>&#xe91a;</i>
          <span>回复</span>
        </a>
        <strong @click='handleUserClick'>{{item.created_by.name}}</strong>
        <i>&#xe91d;</i>
        <em>{{commentCreatedOn}}</em>
      </div>
      <div class="c_c">
        <p>
          <template v-if='hasParentComment'>回复 {{item.data.comment.parent_comment.created_by.name}}：</template>
          {{{item.text}}}
        </p>
      </div>
    </template>
    <template v-else>
      <div class="icon">
        <i>&#xe919;</i>
      </div>
      <div class="c_c">
        <p>
          <span>{{item.created_by.name}}</span>
          <span>{{item.text}}</span>
          <span v-if='item.action === "task_description_updated" || item.action === "task_title_updated"'>
            <a href="#" @click.prevent='handleDiff'>查看修改</a>
          </span>
          <span>{{{streamCreatedOn}}}</span>
        </p>
      </div>
      <div>
      </div>
    </template>
  </li>
</template>

<script>
import _ from 'lodash'
import * as SDK from 'huoban-app-sdk'
import dateUtil from 'src/utils/date'

export default {

  name: 'task-stream-item',

  props: {
    item: Object
  },

  ready() {
    this.client = SDK.client()
  },

  computed: {
    commentCreatedOn() {
      return dateUtil.friendly(this.item.created_on, _.now() / 1000, true).text
    },
    streamCreatedOn() {
      // 友好化展示创建时间
      return dateUtil.friendly(this.item.created_on, _.now() / 1000)
    },
    hasParentComment() {
      if (this.item.action === 'comment_created' && !(this.item.data.comment.parent_comment instanceof Array)) {
        return true
      }
      return false
    }
  },

  methods: {
    handleReply() {
      this.$dispatch('stream-item-reply', this.item)
    },
    handleUserClick() {
      if (this.item.action === 'comment_created') {
        this.$dispatch('stream-item-reference', this.item.created_by)
      }
    },
    handleDiff() {
      // alert('handleDiff clicked!')
      let diffData = this.item.data.diff
      let opts = {
        field_id: diffData.field_id,
        created_by: this.item.created_by,
        updated_on: this.item.created_on
      }
      this.client.openItemDiff(this.$route.params.item_id, diffData.from_revision_id, diffData.to_revision_id, opts)
    }
  }
}
</script>
