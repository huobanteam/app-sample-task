<template>
  <span @click.prevent.stop='handleClick'><slot></slot></span>
</template>

<script>
import {menuShow, menuToggle, menuHide} from 'src/vuex/actions'
import $ from 'zepto'

export default {

  name: 'menu_trigger',

  components: {

  },

  props: {
    onMenuItemChoose: {
      type: Function,
      default: () => {}
    },
    items: {
      type: Array,
      default: () => []
    },
    current: {
      type: Number,
      default: -1
    },
    clickInteraction: {
      type: Object,
      default: () => {
        // return {
        //   selector: 'li',
        //   toggleClass: 'a'
        // }
      }
    },
    menuTitle: {
      type: String,
      default: '',
      menuShowing: false
    },

    menuStyle: {
      type: Object,
      default: () => {}
    }
  },

  vuex: {
    actions: {
      menuShow, menuToggle, menuHide
    },
    getters: {

    }
  },

  data() {
    return {

    };
  },

  computed: {

  },

  methods: {
    handleClick(e) {
      if (this.menuShowing) {
        this.menuShowing = false
      } else {
        this.menuShowing = true
        this.menuShow(e.currentTarget || e.target, {
          items: this.items,
          onItemClick: this.handleMenuItemClick,
          current: this.current,
          menuTitle: this.menuTitle,
          menuStyle: this.menuStyle
        })
      }

      let interactionFlag = this.clickInteraction && this.clickInteraction.selector && this.clickInteraction.toggleClass

      let parent = null
      if (interactionFlag) {
        parent = $(e.target).parents(this.clickInteraction.selector)
        parent.addClass(this.clickInteraction.toggleClass)
      }

      if (!this._globalClickHandler) {
        this._globalClickHandler = () => {
          this.menuShowing = false
          if (interactionFlag) {
            parent.removeClass(this.clickInteraction.toggleClass)
          }
          $(document.body).off('click', this._globalClickHandler)
        }
      }

      $(document.body).on('click', this._globalClickHandler)
    },

    handleMenuItemClick(menuItemIndex) {
      this.$dispatch(
        'menu-item-chosen',
        menuItemIndex
      )
    }
  },

  beforeDestroy() {
    $(document.body).off('click', this._globalClickHandler)
  },

  route: {

  }
};
</script>
