import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import BoardList from '../components/BoardList.vue'
import BoardView from '../components/BoardView.vue'
import ThreadView from '../components/ThreadView.vue'
import SearchView from '../components/SearchView.vue'

const routes: Array<RouteRecordRaw> = [
  { path: '/', name: 'home', component: BoardList },
  { path: '/board/:slug', name: 'board', component: BoardView, props: true },
  { path: '/thread/:id', name: 'thread', component: ThreadView, props: true },
  { path: '/search', name: 'search', component: SearchView },
  { path: '/tag/:name', redirect: (to) => ({ path: '/search', query: { tag: to.params.name } }) },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router