# 轻任务应用源码
## 源码结构
```
app_task/
  ├── api/ 后端 使用lumen框架
  |   ├── app/
  |   |   ├── Http/
  |   |   |   ├── Controllers/* 控制器层 主要做接口的业务逻辑
  |   |   |   ├── Middleware/Token.php  中间件 主要做设置ticket的操作
  |   |   |   ├── Request/Huoban.php  请求huoban接口的类文件
  |   |   |   ├── routes.php  接口层的路由文件 定义所有接口的路由
  |   |   ├── Comment.php 模型层文件 定义了跟接口交换数据的各个方法 如comment.create()等
  |   |   ├── *.php 模型层文件
  |   ├── bootstrap/
  |   ├── database/
  |   ├── public/
  |   |   ├── index.php  程序入口文件 定义了OPTIONS类型请求返回的头 以便前端可以进行跨域请求
  |   ├── resources/
  |   ├── storage/ 存储日志等 777权限
  |   |   ├── logs/* 程序错误日志、请求huoban日志等
  |   ├── tests/
  |   ├── vendor/
  ├── task/ 前端
  |   ├── build/
  |   |   ├──  打包相关配置，不需要修改的
  |   ├── config/
  |   |   ├── 可根据自身需要修改的打包相关配置，如端口，打包文件生成路径
  |   ├── node_modules/
  |   ├── src/
  |   |   ├── api/
  |   |   |   ├── 获取后台数据的相关ajax请求，基于VueResource
  |   |   ├── assets/
  |   |   |   ├── css fonts image等静态资源
  |   |   ├── components/
  |   |   |   ├── active-projects.vue
  |   |   |   ├── project-item.vue
  |   |   |   ├── ...
  |   |   |   ├── 相对细化的组件
  |   |   ├── views/
  |   |   |   ├── home.vue
  |   |   |   ├── error.vue
  |   |   |   ├── ...
  |   |   |   ├── 页面级别的组件
  |   |   ├── plugins/
  |   |   |   ├── 自定义Vue插件
  |   |   ├── utils/
  |   |   |   ├── 实用工具
  |   |   ├── vendor/
  |   |   |   ├── 第三方插件
  |   |   ├── vuex/
  |   |   |   ├── vuex架构相关的js
  |   |   ├── App.vue 根组件
  |   |   ├── index.html
  |   |   ├── main.js
  |   |   ├── routes.js
  |   ├── .babelrc babel配置
  |   ├── .editorconfig
  |   ├── .eslintrc.js  语法检查
  |   ├── package.json  npm包管理
  ├── .gitignore
  └── README.md
```
## 说明
#### 轻应用采用前后端分离的方式，./task 为前端代码，前端使用vue框架，采用es6语法，babel编译，webpack打包等；./api 为后端代码，后端使用php语言，使用lumen框架
#### 本开源代码用于第三方开发者参考伙伴云表格应用的官方开发方式
#### 若有任何不妥之处，请开issue