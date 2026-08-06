import { ref } from 'vue'
import { defineStore } from 'pinia'
import { catalogApi } from '@/api/catalog'
import type { Meta } from '@/types'

export const useAppStore = defineStore('app', () => {
  const meta = ref<Meta>({ tmdb_enabled: false, google_oauth_enabled: false })
  const loaded = ref(false)
  const catalogResetToken = ref(0)

  async function loadMeta(): Promise<void> {
    if (loaded.value) return
    meta.value = await catalogApi.meta()
    loaded.value = true
  }

  function requestCatalogReset(): void {
    catalogResetToken.value += 1
  }

  return { meta, loaded, catalogResetToken, loadMeta, requestCatalogReset }
})
