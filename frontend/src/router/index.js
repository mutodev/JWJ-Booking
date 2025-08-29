import { createWebHashHistory, createRouter } from "vue-router";

import Home from "@/components/home/Home.vue";
import Admin from "@/components/admin/Admin.vue";
import Login from "@/components/home/login/Login.vue";
import Dashboard from "@/components/admin/dashboard/Dashboard.vue";
import Users from "@/components/admin/users/Users.vue";
import Client from "@/components/admin/client/Client.vue";
import Reservations from "@/components/admin/reservations/Reservations.vue";
import Reports from "@/components/admin/reports/Reports.vue";
import Services from "@/components/admin/services/Services.vue";
import Prices from "@/components/admin/prices/Prices.vue";
import Roles from "@/components/admin/roles/Roles.vue";
import Menu from "@/components/admin/menu/Menu.vue";

const routes = [
  {
    path: "/",
    name: "home",
    component: Home,
    children: [{ path: "login", component: Login }],
  },
  {
    path: "/admin",
    name: "admin",
    component: Admin,
    meta: { requiresAuth: true },
    children: [
      { path: "dashboard", component: Dashboard },
      { path: "reservations", component: Reservations },
      { path: "clients", component: Client },
      { path: "reports", component: Reports },
      { path: "config/users", component: Users },
      { path: "config/services", component: Services },
      { path: "config/prices", component: Prices },
      { path: "config/roles", component: Roles },
      { path: "config/menus", component: Menu },
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
    next("/login");
  } else {
    next();
  }
});

export default router;
