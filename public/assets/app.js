(() => {
  const root = document.documentElement
  const stored = localStorage.getItem('le_theme')
  if (stored === 'dark' || stored === 'light') root.dataset.theme = stored

  document.addEventListener('click', (e) => {
    const target = e.target instanceof Element ? e.target.closest('[data-action]') : null
    if (!target) return
    const action = target.getAttribute('data-action')
    if (action === 'toggle-theme') {
      const next = root.dataset.theme === 'dark' ? 'light' : 'dark'
      root.dataset.theme = next
      localStorage.setItem('le_theme', next)
    }
  })

  window.leApi = async (path, options = {}) => {
    const headers = Object.assign({}, options.headers || {})
    if (window.__CSRF__ && !headers['x-csrf-token']) headers['x-csrf-token'] = window.__CSRF__
    const token = localStorage.getItem('le_token')
    if (token && !headers['authorization']) headers['authorization'] = `Bearer ${token}`
    if (!headers['content-type'] && options.body && !(options.body instanceof FormData)) headers['content-type'] = 'application/json'
    const res = await fetch(path, Object.assign({}, options, { headers }))
    const contentType = res.headers.get('content-type') || ''
    const data = contentType.includes('application/json') ? await res.json() : await res.text()
    return { ok: res.ok, status: res.status, data }
  }
})()
