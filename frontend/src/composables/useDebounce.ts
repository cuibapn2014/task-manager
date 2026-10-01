import { ref, watch } from 'vue'
import type { Ref } from 'vue'

/**
 * Returns a debounced copy of `value` that only updates after `delayMs`
 * milliseconds of inactivity.
 *
 * Usage:
 *   const search = ref('')
 *   const debouncedSearch = useDebounce(search, 300)
 *   watch(debouncedSearch, () => fetchData())
 */
export function useDebounce<T>(value: Ref<T>, delayMs = 300): Ref<T> {
  const debounced = ref<T>(value.value) as Ref<T>
  let timer: ReturnType<typeof setTimeout>

  watch(value, (newVal) => {
    clearTimeout(timer)
    timer = setTimeout(() => {
      debounced.value = newVal
    }, delayMs)
  })

  return debounced
}
