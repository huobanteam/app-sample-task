<template>
  <span @click='handleClick'>
    <slot></slot>
  </span>
</template>

<script>
export default {

  name: 'component_name',

  vuex: {
    actions: {
      show: require('src/vuex/actions').dialogShow,
      hide: require('src/vuex/actions').dialogHide
    },
    getters: {

    }
  },

  props: {
    title: String,
    subject: String,
    content: String,
    buttons: Array,
    onDialogShow: {
      type: Function,
      default: () => {}
    },
    onDialogHide: {
      type: Function,
      default: () => {}
    },
    onDialogButtonClick: {
      type: Function,
      default: () => {}
    },
  },

  methods: {
    handleClick() {
      this.show({
        title: this.title,
        subject: this.subject,
        content: this.content,
        buttons: this.buttons,
        onDialogShow: this.onDialogShow,
        onDialogHide: this.onDialogHide,
      }).then(this.onDialogButtonClick)
    }
  }
}
</script>