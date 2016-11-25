export default {
  methods: {
    deleteProject() {
      this.taskProjectDeleteAction(this.item).then(() => {
        // 如果删除的是当前project
        if (this.currentProjectId === this.item.project_id) {
          let projects = $('li[project-id]')
          if (projects.length > 0) {
            this.$redirect({
              name: 'item-list',
              params: {
                project_id: projects.attr('project-id')
              },
              query: this.$route.query
            }, {ignore: true})
          } else {
            this.$redirect({
              name: 'item-list',
              params: {
                project_id: 0
              }
            }, {ignore: true})
          }
        }
      })
    }
  }
};
