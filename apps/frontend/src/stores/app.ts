import { ref } from 'vue'
import { defineStore } from 'pinia'
import { catalogApi } from '@/api/catalog'
import type { Meta } from '@/types'

export const useAppStore = defineStore('app', () => {
  const meta = ref<Meta>({ tmdb_enabled: false, google_oauth_enabled: false })
  const loaded = ref(false)

  async function loadMeta(): Promise<void> {
    if (loaded.value) return
    meta.value = await catalogApi.meta()
    loaded.value = true
  }

  return { meta, loaded, loadMeta }
})
