<template>
  <div class="comment_write focus">
    <div class="reply" v-if='replyTo'>
      <em>回复：</em>
      <span>{{replyTo.created_by.name}}</span>
      <i @click='handleRemoveReplyTo'>&#xe910;</i>
    </div>
    <textarea class="pt comment-input" placeholder="添加评论" v-model='comment' style="height: 2.2em; overflow: hidden" v-el:textarea></textarea>
    <p v-show='initialized'>
      <button @click='handleCommit' :disabled='sending'><span>评论</span></button>
      <a href="#" class="at" @click.prevent.stop='handleInserAt'><i>&#xe918;</i></a>
    </p>
  </div>
</template>

<script>
import commentize from 'src/vendor/commentize'
import _ from 'lodash'
import * as SDK from 'huoban-app-sdk'

let CommentHandler = {}

export default {

  name: 'task-comment-input',

  data() {
    return {
      comment: '',
      replyTo: null,
      sending: false,
      initialized: false
    }
  },

  methods: {
    handleCommit() {
      if (_.trim(this.comment)) {
        this.sending = true
        this.$dispatch('comment-form-commit', this.comment, this.replyTo)
        CommentHandler.focus()
      }
    },

    handleRemoveReplyTo() {
      this.replyTo = null
    },
    // -------------------- for testing
    handleTest() {
      this.sending = !this.sending
      CommentHandler.clean()
    },

    handleTest2() {
      console.log(this.comment)
    },

    handleTest3() {
      CommentHandler.insertAt()
    },

    handleTest4() {
      CommentHandler.referencePeople('xxx')
    },

    handleTest5() {
      CommentHandler.referencePeople('\u00A0')
    },

    handleTest6() {
      // console.log(this.$client)
      console.log(this.$client.getSpaceMembers())
    },
    // -------------------- for testing

    handleInserAt() {
      CommentHandler.insertAt()
    },

    focus() {
      CommentHandler.focus()
    },

    finish() {
      this.sending = false
    },

    clean() {
      this.replyTo = null
      CommentHandler.clean()
    },

    setReplyTo(stream) {
      this.replyTo = stream
    },

    referencePeople(name) {
      CommentHandler.referencePeople(name)
    },

    uncommentize() {
      console.log('uncommentize')
      CommentHandler.destroy()
    },

    handleCommentizeInitialized() {
      this.initialized = true
    }
  },

  attached() {
    CommentHandler = commentize($, _, SDK, $(this.$els.textarea), {
      afterHeight: '80px',
      onInitialized: this.handleCommentizeInitialized
    })
  },

  beforeDestroy() {
    this.uncommentize()
  }
}
</script>
