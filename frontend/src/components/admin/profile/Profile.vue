<template>
  <div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="card-title mb-4 text-center">
            <i class="bi bi-person-fill"></i>
            Profile
          </h4>

          <form :autocomplete="false" @submit.prevent="submitForm">
            <div class="mb-3">
              <label for="first_name" class="form-label">First Name</label>
              <input
                id="first_name"
                class="form-control"
                v-model="first_name"
                placeholder="Enter first name"
              />
              <small class="text-danger">{{ first_name_error }}</small>
            </div>

            <div class="mb-3">
              <label for="last_name" class="form-label">Last Name</label>
              <input
                id="last_name"
                class="form-control"
                v-model="last_name"
                placeholder="Enter last name"
              />
              <small class="text-danger">{{ last_name_error }}</small>
            </div>

            <div class="mb-3 form-check">
              <input
                id="change_password"
                type="checkbox"
                class="form-check-input"
                v-model="change_password"
              />
              <label class="form-check-label" for="change_password"
                >Change Password</label
              >
            </div>

            <div v-if="change_password" class="mb-3">
              <hr />
            </div>

            <div v-if="change_password" class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input
                id="password"
                type="password"
                class="form-control"
                v-model="password"
                placeholder="Enter password"
              />
              <small class="text-danger">{{ password_error }}</small>
            </div>

            <div v-if="change_password" class="mb-3">
              <label for="confirm_password" class="form-label"
                >Confirm Password</label
              >
              <input
                id="confirm_password"
                type="password"
                class="form-control"
                v-model="confirm_password"
                placeholder="Confirm password"
              />
              <small class="text-danger">{{ confirm_password_error }}</small>
            </div>

            <div class="col-12 d-flex justify-content-end">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Save
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { useForm, useField } from "vee-validate";
import { inject } from "vue";
import * as yup from "yup";
import api from "@/services/axios";
import { useRouter } from "vue-router";

const router = useRouter();

const updateHeaderData = inject("updateHeaderData");
updateHeaderData({ title: "Profile", icon: "bi-person-fill" });

const schema = yup.object({
  first_name: yup
    .string()
    .required("First name is required")
    .min(2, "Minimum 2 characters")
    .max(30, "Maximum 30 characters"),
  last_name: yup
    .string()
    .required("Last name is required")
    .min(2, "Minimum 2 characters")
    .max(30, "Maximum 30 characters"),
  change_password: yup.boolean(),
  password: yup.string().when("change_password", {
    is: true,
    then: (s) =>
      s
        .required("Password is required")
        .min(8, "Password must be at least 8 characters")
        .matches(/[A-Z]/, "Must contain at least one uppercase letter")
        .matches(/[a-z]/, "Must contain at least one lowercase letter")
        .matches(/[0-9]/, "Must contain at least one number")
        .matches(/[@$!%*?&]/, "Must contain at least one special character"),
    otherwise: (s) => s.notRequired(),
  }),
  confirm_password: yup.string().when("change_password", {
    is: true,
    then: (s) =>
      s
        .required("Confirm Password is required")
        .oneOf([yup.ref("password")], "Passwords must match"),
    otherwise: (s) => s.notRequired(),
  }),
});

// 1) crea el form ANTES de los fields
const { handleSubmit } = useForm({
  validationSchema: schema,
  initialValues: {
    first_name: sessionStorage.getItem('first_name'),
    last_name: sessionStorage.getItem('last_name'),
    change_password: false,
    password: "",
    confirm_password: "",
  },
});

// 2) ahora crea los fields
const { value: first_name, errorMessage: first_name_error } =
  useField("first_name");
const { value: last_name, errorMessage: last_name_error } =
  useField("last_name");
const { value: change_password } = useField("change_password");
const { value: password, errorMessage: password_error } = useField("password");
const { value: confirm_password, errorMessage: confirm_password_error } =
  useField("confirm_password");

// 3) submit
const submitForm = handleSubmit(async (values) => {
  try {
    const id = sessionStorage.getItem("id");
    await api.put(`/users/${id}`, values);

    sessionStorage.setItem('last_name', last_name.value)
    sessionStorage.setItem('first_name', first_name.value)

    router.push("/admin");
  } catch (error) {
    console.log(error);
  }


});
</script>
