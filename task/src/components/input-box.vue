<template>
  <div class="text"
       contentEditable="true"
       @paste='handlePaste'
       @keydown='handleKeyDown'
       @keyup='handleKeyPress'
       @focus='handleFocus'
       @blur='handleBlur'
       ></div>
</template>

<script>
import _ from 'lodash'
import {nl2br} from 'src/utils/functions'
export default {

  name: 'input-box',

  props: {
    text: String,
    preventKeys: {
      type: Array,
      default: () => []
    },
    placeholder: {
      type: String,
      default: () => ''
    }
  },

  data() {
    return {
      focused: false,
      _text: '',
      _lastText: ''
    }
  },

  methods: {
    handlePaste(evt) {
      evt.preventDefault()
      let data = evt.clipboardData.getData('text/plain')
      let selection = window.getSelection()
      let range = selection.getRangeAt(0)
      range.deleteContents()
      let fragment = range.createContextualFragment('')
      fragment.textContent = data
      let replacementEnd = fragment.lastChild
      range.insertNode(fragment)
      // Set cursor at the end of the replaced content, just like browsers do.
      range.setStartAfter(replacementEnd)
      range.collapse(true)
      selection.removeAllRanges()
      selection.addRange(range)
    },

    handleKeyDown(evt) {
      if (this.preventKeys && this.preventKeys.indexOf(evt.keyCode) > -1) {
        evt.preventDefault()
        evt.stopPropagation()
        this.$el.blur()
        return
      }
      let forbiddenKeys = [
        66, // bold
        73  // italic
      ]
      if ((evt.ctrlKey || evt.metaKey) && forbiddenKeys.indexOf(evt.keyCode)>-1) {
        evt.preventDefault()
        evt.stopPropagation()
      }
    },

    handleKeyPress(evt) {
    },

    handleFocus() {
      this.focused = true
      this.$el.innerHTML = this.getDisplayValue()
    },

    handleBlur() {
      this.focused = false
      this.dispatch()
      this.$el.innerHTML = this.getDisplayValue()
      window.getSelection().removeAllRanges()
    },

    dispatch() {
      this._text = this.$el.innerText
      this.$dispatch('input-box-change', this._text)
    },

    getDisplayValue() {
      let escapeText = _.escape(this._text)
      escapeText = nl2br(escapeText)
      let ret = escapeText
      if (!this.focused) {
        ret = escapeText || this.placeholder
      }
      return ret
    },

    // 不能在插值里直接设置值，因为contenteditable里是dom，直接使用插值会有意想不到的情况发生
    updateDisplayValue() {
      let value = this.getDisplayValue()
      if (!this.focused) {
        this.$el.innerHTML = value
      }
    },

    debounceDispatch() {
      return _.debounce((text) => {
        this.$dispatch('input-box-change', text)
      }, 500)
    },

    restoreValue() {
      this._text = this._lastText
      this.updateDisplayValue()
    }
  },

  watch: {
    text: function(newValue, oldValue) {
      this._text = newValue
      this._lastText = newValue
      this.updateDisplayValue()
    }
  },

  ready() {
    this._text = this.text
    this._lastText = this.text
    this.updateDisplayValue()
  },

  beforeDestroy() {
  }
}
</script>