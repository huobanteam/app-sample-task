import Sortable from 'sortablejs'

export default {
  install: function(Vue) {
    Vue.directive('k-sortable' , {
      deep: true,
      update(options) {
        if (!this.sortable) {
          this.sortable = new Sortable(this.el, options)
        } else {
          for (var valName in options) {
            this.sortable.options[valName] = options[valName]
          }
        }
      }
    })
  }
}