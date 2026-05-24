<!-- Vue 3 Composition API Example -->
<template>
  <div class="auth-wrapper">
    <div v-if="!user">
      <h2>Login</h2>
      <input v-model="form.email" placeholder="Email" />
      <input v-model="form.password" type="password" placeholder="Password" />
      <button @click="handleLogin">Login</button>
    </div>
    <div v-else>
      <h2>Welcome, {{ user.name }}</h2>
      <button @click="logout">Logout</button>
    </div>

    <div class="product-list">
      <h2>Product List</h2>
      <div v-for="product in products" :key="product.id">
        {{ product.name }} - ${{ product.price }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const API_URL = 'http://localhost/tokoemas/api/v1';
const user = ref(null);
const products = ref([]);
const form = ref({ email: '', password: '' });

// Axios Interceptor for Auth
const api = axios.create({ baseURL: API_URL });
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

const handleLogin = async () => {
  try {
    const res = await axios.post(`${API_URL}/auth/login`, form.value);
    localStorage.setItem('token', res.data.data.token);
    user.value = res.data.data.user;
    alert('Login success');
  } catch (err) {
    alert('Login failed: ' + err.response.data.message);
  }
};

const fetchProducts = async () => {
  const res = await api.get('/products');
  products.value = res.data.data.products;
};

const logout = () => {
  localStorage.removeItem('token');
  user.value = null;
};

onMounted(fetchProducts);
</script>
