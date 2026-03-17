function csrfToken() {
  return document.querySelector('input[name="_token"]')?.value || ''
}

async function apiPost(url, body) {
  const token = csrfToken()
  const r = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': token,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body || {}),
  })

  const contentType = r.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const data = isJson ? await r.json() : null
  if (!r.ok) {
    throw new Error(data?.message || data?.error || 'Request failed')
  }
  return data
}

function setMsg(node, text, tone) {
  if (!node) return
  node.textContent = text || ''
  node.className = `ml-3 text-sm ${tone || 'text-gray-600'}`
}

document.addEventListener('DOMContentLoaded', () => {
  const y = document.getElementById('year')
  if (y) y.textContent = new Date().getFullYear()

  const profileForm = document.getElementById('profileForm')
  const passwordForm = document.getElementById('passwordForm')

  profileForm?.addEventListener('submit', async (e) => {
    e.preventDefault()
    const msg = document.getElementById('profileMsg')
    const btn = document.getElementById('profileSaveBtn')

    try {
      btn && (btn.disabled = true)
      setMsg(msg, 'Saving…', 'text-gray-600')

      const companyName = document.getElementById('company_name')?.value || ''
      const email = document.getElementById('email')?.value || ''

      await apiPost('/api/account/profile', {
        company_name: companyName,
        email,
      })

      setMsg(msg, 'Saved.', 'text-amber-700')
    } catch (err) {
      setMsg(msg, err?.message || 'Save failed', 'text-red-600')
    } finally {
      btn && (btn.disabled = false)
    }
  })

  passwordForm?.addEventListener('submit', async (e) => {
    e.preventDefault()
    const msg = document.getElementById('passwordMsg')
    const btn = document.getElementById('passwordSaveBtn')

    try {
      btn && (btn.disabled = true)
      setMsg(msg, 'Updating…', 'text-gray-600')

      const currentPassword = document.getElementById('current_password')?.value || ''
      const newPassword = document.getElementById('new_password')?.value || ''

      await apiPost('/api/account/password', {
        current_password: currentPassword,
        new_password: newPassword,
      })

      ;(document.getElementById('current_password')).value = ''
      ;(document.getElementById('new_password')).value = ''
      setMsg(msg, 'Password updated.', 'text-amber-700')
    } catch (err) {
      setMsg(msg, err?.message || 'Update failed', 'text-red-600')
    } finally {
      btn && (btn.disabled = false)
    }
  })
})
