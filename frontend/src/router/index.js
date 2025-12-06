import { createWebHistory, createRouter } from "vue-router";

import Home from "@/components/home/Home.vue";
import Admin from "@/components/admin/Admin.vue";
import Login from "@/components/home/login/Login.vue";
import Dashboard from "@/components/admin/dashboard/Dashboard.vue";
import Users from "@/components/admin/config/users/Users.vue";
import Client from "@/components/admin/client/Client.vue";
import Reservations from "@/components/admin/reservations/Reservations.vue";
import Reports from "@/components/admin/reports/Reports.vue";
import Roles from "@/components/admin/config/roles/Roles.vue";
import Menu from "@/components/admin/config/menu/Menu.vue";
import ResetPassword from "@/components/home/login/ResetPassword.vue";
import Profile from "@/components/admin/profile/Profile.vue";
import MetropolitanAreas from "@/components/admin/areas/metropolitan-areas/MetropolitanAreas.vue";
import Counties from "@/components/admin/areas/counties/Counties.vue";
import Cities from "@/components/admin/areas/cities/Cities.vue";
import PostalCode from "@/components/admin/areas/postal-code/PostalCode.vue";
import JamTypes from "@/components/admin/services/jam-types/JamTypes.vue";
import Prices from "@/components/admin/services/prices/Prices.vue";
import AddOns from "@/components/admin/services/add-ons/AddOns.vue";
import TypeAddons from "@/components/admin/services/type-addons/TypeAddons.vue";
import PromoCodes from "@/components/admin/promo-codes/PromoCodes.vue";
import AbandonedCarts from "@/components/admin/abandoned-carts/AbandonedCarts.vue";
import NotFound from "@/components/not-found/NotFound.vue";

const routes = [
  {
    path: "/",
    name: "home",
    component: Home,
  },
  { path: "/login", name: "login", component: Login },
  { path: "/reset-password", name: "reset-password", component: ResetPassword },
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
        ],
      },
      {
        path: "config",
        children: [
          { path: "users", component: Users },
          { path: "roles", component: Roles },
          { path: "menus", component: Menu },
        ],
      },
      {
        path: "services",
        children: [
          { path: "jam-types", component: JamTypes },
          { path: "prices", component: Prices },
          { path: "type-addons", component: TypeAddons },
          { path: "addons", component: AddOns },
        ],
      },
      { path: "promo-codes", component: PromoCodes },
      { path: "abandoned-carts", component: AbandonedCarts },
    ],
  },

  {
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: NotFound,
  },
];

const router = createRouter({
  history: createWebHistory('/'),
  routes,
});

// Guard global (middleware)
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token");

  if (to.matched.some((record) => record.meta.requiresAuth)) {
    if (!token) {
      next("/login");
      return;
    }

    // Verificar si el token ha expirado
    try {
      const decoded = JSON.parse(atob(token.split('.')[1]));
      const currentTime = Date.now() / 1000;

      if (decoded.exp && decoded.exp < currentTime) {
        localStorage.clear();
        next("/login");
        return;
      }
    } catch (error) {
      console.error("Error validating token:", error);
      localStorage.clear();
      next("/login");
      return;
    }
  }

  next();
});

export default router;
