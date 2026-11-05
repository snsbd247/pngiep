/** Maps a Laravel 422 response onto react-hook-form; anything else becomes a root error. */
export function applyServerErrors(e, setError, fieldMap = (k) => k) {
  if (e?.isValidation) {
    Object.entries(e.errors).forEach(([k, m], i) => setError(fieldMap(k), { type: 'server', message: m[0] }, { shouldFocus: i === 0 }))
  } else {
    setError('root', { type: 'server', message: e?.message || 'Something went wrong. Please try again.' })
  }
}
