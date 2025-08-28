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
    meta: { requiresAuth: true },
    children: [
      { path: "/dashboard", component: Dashboard },
      { path: "/users", component: User },
    ],
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

// Guard global (middleware)
router.beforeEach((to, from, next) => {
  const token = sessionStorage.getItem("token");

  if (to.matched.some((record) => record.meta.requiresAuth) && !token) {
    // 🚨 Si cualquier ruta del "match" necesita auth y no hay token
    next("/login");
  } else {
    next();
  }
});

export default router;
