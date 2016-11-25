<template>
  <div class="popover"
       :style='style'>
    <div class="popover-content">
      <p v-if='menuTitle'>{{menuTitle}}</p>
      <ul class="pop_menu cl">
        <template v-for='item of items'>
          <li v-if='item !== "--"' :class='{current: current === $index}'><a href="#" @click.prevent='handleClick($index)'>{{item}}</a></li>
          <li v-else class='line'></li>
        </template>
      </ul>
    </div>
  </div>
</template>

<script>
import $ from 'zepto'
import {menuHide} from 'src/vuex/actions'
import * as SDK from 'huoban-app-sdk'
import _ from 'lodash'

export default {

  name: 'menu',

  vuex: {
    actions: {
      menuHide
    },
    getters: {
      show: state => state.menu.show,
      position: state => state.menu.position,
      items: state => state.menu.items,
      onItemClick: state => state.menu.onItemClick,
      current: state => state.menu.current,
      menuTitle: state => state.menu.menuTitle,
      menuStyle: state => state.menu.menuStyle
    }
  },

  computed: {
    left() {
      let ret = this.position.left
      let $el = $(this.$el)
      if (SDK.isMobile && ret + $el.width() > window.document.documentElement.clientWidth) {
        ret = window.document.documentElement.clientWidth - $el.width()
      }
      return ret
    },
    style() {
      let ret = {
        left: this.left + 'px',
        top: this.position.top + 'px',
        display: 'block',
        visibility: this.show ? 'visible' : 'hidden'
      }
      _.assign(ret, this.menuStyle)
      return ret
    }
  },

  methods: {
    handleGlobalMouseDown(e) {
      const insideMenu = $(e.target).parents('.popover').length > 0
      if (!insideMenu) {
        this.menuHide()
      }
    },
    handleClick(index) {
      this.onItemClick(index)
      this.menuHide()
    }
  },

  created() {
    $(document.body).on('mousedown', this.handleGlobalMouseDown)
    $(document.body).on('touchend', this.handleGlobalMouseDown)
  },

  destroyed() {
    $(document.body).off('mousedown', this.handleGlobalMouseDown)
    $(document.body).off('touchend', this.handleGlobalMouseDown)
  }
}
</script>