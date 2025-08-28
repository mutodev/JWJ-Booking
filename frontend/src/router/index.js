import { createWebHashHistory, createRouter } from "vue-router";

import Home from "@/components/home/Home.vue";
import Admin from "@/components/admin/Admin.vue";
import Login from "@/components/home/login/Login.vue";
import Dashboard from "@/components/admin/dashboard/Dashboard.vue";
import User from "@/components/admin/user/User.vue";

const routes = [
  {
    path: "/",
    name: "home",
    component: Home,
    children: [{ path: "/login", component: Login }],
  },
  {
    path: "/admin",
    name: "admin",
    component: Admin,
    children: [
      { path: "/", component: Dashboard },
      { path: "/users", component: User },
    ],
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
