import { createWebHashHistory, createRouter } from "vue-router";

import Home from "@/components/home/Home.vue";
import Admin from "@/components/admin/Admin.vue";
import Login from "@/components/home/login/Login.vue";
import Dashboard from "@/components/admin/dashboard/Dashboard.vue";
import Users from "@/components/admin/config/users/Users.vue";
import Client from "@/components/admin/client/Client.vue";
import Reservations from "@/components/admin/reservations/Reservations.vue";
import Reports from "@/components/admin/reports/Reports.vue";
import Services from "@/components/admin/services/Services.vue";
import Prices from "@/components/admin/prices/Prices.vue";
import Roles from "@/components/admin/config/roles/Roles.vue";
import Menu from "@/components/admin/config/menu/Menu.vue";
import ResetPassword from "@/components/home/login/ResetPassword.vue";
import Profile from "@/components/admin/profile/Profile.vue";
import MetropolitanAreas from "@/components/admin/areas/metropolitan-areas/MetropolitanAreas.vue";
import Counties from "@/components/admin/areas/counties/Counties.vue";
import Cities from "@/components/admin/areas/cities/Cities.vue";
import PostalCode from "@/components/admin/areas/postal-code/PostalCode.vue";

const routes = [
  {
    path: "/",
    name: "home",
    component: Home,
    children: [
      { path: "login", component: Login },
      { path: "reset-password", component: ResetPassword },
    ],
  },
  {
    path: "/admin",
    name: "admin",
    component: Admin,
    meta: { requiresAuth: true },
    redirect: "/admin/dashboard",
    children: [
      { path: "dashboard", component: Dashboard },
      { path: "reservations", component: Reservations },
      { path: "clients", component: Client },
      { path: "reports", component: Reports },
      { path: "profile", component: Profile },
      {
        path: "areas",
        children: [
          { path: "metropolitan-areas", component: MetropolitanAreas },
          { path: "counties", component: Counties },
          { path: "cities", component: Cities },
          { path: "postal-codes", component: PostalCode },
        ]
      },
      {
        path: "config",
        children: [
          { path: "users", component: Users },
          { path: "roles", component: Roles },
          { path: "menus", component: Menu },
          { path: "services", component: Services },
          { path: "prices", component: Prices },
        ],
      },
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
