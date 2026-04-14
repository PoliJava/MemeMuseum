import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import BoardList from '../components/BoardList.vue'
import BoardView from '../components/BoardView.vue'
import ThreadView from '../components/ThreadView.vue'

const routes: Array<RouteRecordRaw> = [
  { path: '/', name: 'home', component: BoardList },
  { path: '/board/:slug', name: 'board', component: BoardView, props: true },
  { path: '/thread/:id', name: 'thread', component: ThreadView, props: true },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router