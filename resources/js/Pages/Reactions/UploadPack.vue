<!-- resources/js/Pages/Reactions/UploadPack.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3'
import TextInput from '../Components/TextInput.vue'
import axios from 'axios'
import { ref, onMounted } from 'vue'

const form = useForm({
  pack_id: null,
  type_id: null,
  zip_file: null,
})

const packs = ref([])
const types = ref([])

onMounted(() => {
  axios.get(route('api.reaction-packs')).then(res => {
    packs.value = res.data
    if (res.data.length > 0) form.pack_id = res.data[0].id
  })

  axios.get(route('api.reaction-types')).then(res => {
    types.value = res.data
    if (res.data.length > 0) form.type_id = res.data[0].id
  })
})

const handleFileChange = (e) => {
  form.zip_file = e.target.files[0]
}

const submit = () => {
  console.log('pack_id:', form.pack_id);
  console.log('type_id:', form.type_id);
  console.log('zip_file:', form.zip_file);
  const formData = new FormData()
  formData.append('pack_id', form.pack_id)
  formData.append('type_id', form.type_id)
  formData.append('zip_file', form.zip_file)

  axios.post(route('reactions.upload-pack'), formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
    .then(response => {
      alert('✅ ' + response.data.message)
      form.reset()
    })
    .catch(error => {
      console.log(error)
      const msg = error.response?.data?.error || 'Ошибка при загрузке'
      alert('❌ ' + msg)
    })
}
</script>

<template>

  <Head title="Добавить реакции в пак" />

  <div class="max-w-2xl mx-auto p-6">
    <h1 class="text-2xl mb-6">Добавить реакции в пак</h1>

    <div class="py-5 grid gap-1">
      <p>Инструкция по применению</p>
      <p>Архив должен быть ZIP</p>
      <p>В архиве должны находиться файлы форматов webp,png,jpg,webm</p>
      <p>Размер допустимого архива 1гб</p>
    </div>

    <form @submit.prevent="submit" enctype="multipart/form-data" class="gap-4 grid">
      <!-- Выбор пака -->
      <div>
        <label class="block mb-2">Пак реакций</label>
        <select v-model="form.pack_id" class="w-full p-2 border rounded MainThemeInput">
          <option v-for="pack in packs" :value="pack.id" :key="pack.id">
            {{ pack.name }}
          </option>
        </select>
      </div>

      <!-- Выбор типа -->
      <div>
        <label class="block mb-2">Тип реакции</label>
        <select v-model="form.type_id" class="w-full p-2 border rounded MainThemeInput">
          <option v-for="type in types" :value="type.id" :key="type.id">
            {{ type.name }} {{ type.sticker ? ' (стикер)' : '' }}
          </option>
        </select>
      </div>

      <!-- ZIP файл -->
      <div>
        <label class="block mb-2">ZIP-архив с изображениями (png, jpg, webp,webm)</label>
        <input type="file" accept=".zip" @change="handleFileChange" class="w-full p-2 border rounded MainThemeInput" />
        <p class="text-sm text-gray-500 mt-1">
          Имена файлов станут названиями реакций (например: happy_smile.webp,webm → "happy smile")
        </p>
      </div>

      <button type="submit" class="primary-btn MainThemeInput"
        :disabled="!form.zip_file || !form.pack_id || !form.type_id">
        Добавить реакции
      </button>
    </form>
  </div>
</template>