<template>
  <div>
    <div class="mask" :style='{display: show ? "" : "none"}'></div>
    <div v-el:dialog class="dialog" :style='{display: "block", left: position.left, top: position.top, visibility: show ? "visible": "hidden"}'>
      <div class="dialog_header cl">
        <div class="dialog_title">{{title}}</div>
      </div>
      <div class="dialog_content">
        <div class="dialog_container">
          <div class="dialog_message">
            <h3>{{subject}}</h3>
            <p>{{content}}</p>
          </div>
        </div>
      </div>
      <div class="dialog_footer cl">
        <div class="dialog_button">
          <button class="pn" :class='button.classes' v-for='button of buttons' @click='handleClick($index)'>{{button.label}}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash'
import $ from 'zepto'

export default {

  name: 'component_name',

  vuex: {
    actions: {
      hide: require('src/vuex/actions').dialogHide
    },
    getters: {
      show: state => state.dialog.show,
      title: state => state.dialog.title,
      subject: state => state.dialog.subject,
      content: state => state.dialog.content,
      buttons: state => state.dialog.buttons,
      onDialogShow: state => state.dialog.onDialogShow,
      onDialogHide: state => state.dialog.onDialogHide,
      onDialogButtonClick: state => state.dialog.onDialogButtonClick
    }
  },

  data() {
    return {
      position: {
        left: 0,
        top: 0
      }
    }
  },

  watch: {
    show: function(v, old) {
      if (v) {
        this.centerDialog()
      }
    }
  },

  methods: {
    handleClick(index) {
      if (this.onDialogButtonClick && _.isFunction(this.onDialogButtonClick)) {
        this.onDialogButtonClick(index)
      }
      this.hide()
      if (this.onDialogHide && _.isFunction(this.onDialogHide)) {
        this.onDialogHide()
      }
    },

    centerDialog() {
      let dialog = $(this.$els.dialog)
      let left = (window.document.documentElement.clientWidth - dialog.width()) / 2
      let top = (window.document.documentElement.clientHeight - dialog.height()) * 0.3

      // console.log(window.document.documentElement.clientWidth, dialog.width(), left, top)
      this.position = {
        left: left + 'px',
        top: top + 'px'
      }
    },

    handleGlobalKeyup(e) {
      if (e.key === 'Escape' && this.show) {
        this.hide()
      }
    },

    handleWindowResize() {
      this.centerDialog()
    }
  },

  created() {
    $(document.body).on('keyup', this.handleGlobalKeyup)
    $(window).on('resize', this.centerDialog)
  },

  destroyed() {
    $(document.body).off('keyup', this.handleGlobalKeyup)
    $(window).off('resize', this.centerDialog)
  }
}
</script>
