// resources/js/composables/useMediaQuery.ts
import { ref, onMounted, onUnmounted } from 'vue'

export function useMediaQuery(query: string) {
  const matches = ref(false)
  let mediaQuery: MediaQueryList

  const updateMatches = () => {
    matches.value = mediaQuery.matches
  }

  onMounted(() => {
    mediaQuery = window.matchMedia(query)
    updateMatches()
    mediaQuery.addEventListener('change', updateMatches)
  })

  onUnmounted(() => {
    mediaQuery.removeEventListener('change', updateMatches)
  })

  return matches
}
