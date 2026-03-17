const API = {
  users: '/api/admin/users',
  createUser: '/api/admin/users',
  resetPassword: (id) => `/api/admin/users/${id}/reset-password`,
  updateUserApiTiers: (id) => `/api/admin/users/${id}/api-tiers`,
  addCredits: (id) => `/api/admin/users/${id}/credits/add`,
  deductCredits: (id) => `/api/admin/users/${id}/credits/deduct`,
  setCap: (id) => `/api/admin/users/${id}/credit-cap`,

  invoices: (status) => `/api/admin/credit-invoices${status ? `?status=${encodeURIComponent(status)}` : ''}`,
  approveInvoice: (id) => `/api/admin/credit-invoices/${id}/approve`,
  rejectInvoice: (id) => `/api/admin/credit-invoices/${id}/reject`,

  documents: '/api/documents',
  deleteDocument: (id) => `/api/documents/${id}`,

  ledger: '/api/admin/ledger',
  audit: '/api/admin/audit',

  settingsGet: '/api/admin/settings',
  settingsUpdate: '/api/admin/settings',
}

const API_TIERS = ['paid_1', 'paid_2', 'paid_3']

function $(sel) { return document.querySelector(sel) }
function el(tag, attrs = {}) { const e = document.createElement(tag); Object.assign(e, attrs); return e }

function csrf() { return $('#csrf')?.value || '' }

async function apiFetch(url, opts = {}) {
  const headers = Object.assign({
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': csrf(),
  }, opts.headers || {})

  const res = await fetch(url, Object.assign({ credentials: 'same-origin', headers }, opts))
  const contentType = res.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const data = isJson ? await res.json() : null

  if (!res.ok) {
    const message = (data && (data.message || data.error))
      ? (data.message || data.error)
      : `Request failed (${res.status})`

    const err = new Error(message)
    err.status = res.status
    err.data = data
    throw err
  }

  return data
}

function setMsg(node, text, tone = 'text-gray-600') {
  if (!node) return
  node.textContent = text || ''
  node.className = `text-sm ${tone}`
}

function td(text) {
  const d = document.createElement('td')
  d.className = 'p-2 border-b align-top'
  d.textContent = text ?? ''
  return d
}

function tdHtml(html) {
  const d = document.createElement('td')
  d.className = 'p-2 border-b align-top'
  d.innerHTML = html
  return d
}

function fmtDate(v) {
  try { return v ? new Date(v).toLocaleString() : '' } catch { return '' }
}

function normalizeTiers(raw) {
  if (!Array.isArray(raw)) return []
  const lower = raw.map((v) => String(v || '').trim().toLowerCase())
  return API_TIERS.filter((tier) => lower.includes(tier))
}

function tierLabel(tier) {
  return String(tier || '').toUpperCase().replace(/_/g, ' ')
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------
async function loadSettings() {
  const msg = $('#settingsMsg')
  try {
    const s = await apiFetch(API.settingsGet)
    if ($('#unit_price_usd')) $('#unit_price_usd').value = s.unit_price_usd
    if ($('#fx_rate_ngn')) $('#fx_rate_ngn').value = s.fx_rate_ngn
    if ($('#max_upload_mb')) $('#max_upload_mb').value = s.max_upload_mb
    if ($('#admin_2fa_required')) $('#admin_2fa_required').checked = !!s.admin_2fa_required
    setMsg(msg, 'Loaded', 'text-amber-700')
    setTimeout(() => setMsg(msg, ''), 1200)
  } catch (e) {
    setMsg(msg, e.message || 'Failed to load settings', 'text-red-600')
  }
}

async function saveSettings() {
  const msg = $('#settingsMsg')
  const btn = $('#settingsSaveBtn')

  const payload = {
    unit_price_usd: $('#unit_price_usd')?.value,
    fx_rate_ngn: $('#fx_rate_ngn')?.value,
    max_upload_mb: $('#max_upload_mb')?.value,
    admin_2fa_required: $('#admin_2fa_required')?.checked ? 1 : 0,
  }

  try {
    btn && (btn.disabled = true)
    await apiFetch(API.settingsUpdate, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    setMsg(msg, 'Saved', 'text-amber-700')
  } catch (e) {
    setMsg(msg, e.message || 'Failed to save', 'text-red-600')
  } finally {
    btn && (btn.disabled = false)
  }
}

// ---------------------------------------------------------------------------
// Users
// ---------------------------------------------------------------------------
function renderUsers(list) {
  const tbody = $('#usersTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  for (const u of (Array.isArray(list) ? list : [])) {
    const tr = document.createElement('tr')

    const actions = document.createElement('td')
    actions.className = 'p-2 border-b align-top'

    const wrap = document.createElement('div')
    wrap.className = 'flex flex-col gap-2 min-w-[220px]'

    const row1 = document.createElement('div')
    row1.className = 'flex items-center gap-2'

    const capInput = el('input', { type: 'number', min: '0', step: '1', value: String(u.credit_cap ?? 0) })
    capInput.className = 'w-24 rounded border border-gray-300 px-2 py-1 text-sm'

    const capBtn = el('button', { type: 'button', textContent: 'Set cap' })
    capBtn.className = 'text-sm underline'
    capBtn.onclick = async () => {
      try {
        await apiFetch(API.setCap(u.id), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ credit_cap: capInput.value }),
        })
        await loadUsers()
      } catch (e) {
        alert(e.message || 'Failed')
      }
    }

    const resetBtn = el('button', { type: 'button', textContent: 'Reset password' })
    resetBtn.className = 'text-sm underline text-red-700'
    resetBtn.onclick = async () => {
      if (!confirm(`Reset password for ${u.email}?`)) return
      try {
        await apiFetch(API.resetPassword(u.id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ force_active: 1 }) })
        alert('Password reset email sent.')
      } catch (e) {
        alert(e.message || 'Failed')
      }
    }

    row1.append(capInput, capBtn)

    const row2 = document.createElement('div')
    row2.className = 'flex items-center gap-2'

    const creditInput = el('input', { type: 'number', min: '1', step: '1', placeholder: 'Credits' })
    creditInput.className = 'w-24 rounded border border-gray-300 px-2 py-1 text-sm'

    const addBtn = el('button', { type: 'button', textContent: 'Add' })
    addBtn.className = 'text-sm underline'
    addBtn.onclick = async () => {
      const v = parseInt(String(creditInput.value || '0'), 10)
      if (!v || v < 1) return
      try {
        await apiFetch(API.addCredits(u.id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ credits: v, reason: 'Admin add (console)' }) })
        await loadUsers(); await loadLedger(); await loadAudit();
      } catch (e) {
        alert(e.message || 'Failed')
      }
    }

    const deductBtn = el('button', { type: 'button', textContent: 'Deduct' })
    deductBtn.className = 'text-sm underline text-red-700'
    deductBtn.onclick = async () => {
      const v = parseInt(String(creditInput.value || '0'), 10)
      if (!v || v < 1) return
      try {
        await apiFetch(API.deductCredits(u.id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ credits: v, reason: 'Admin deduct (console)' }) })
        await loadUsers(); await loadLedger(); await loadAudit();
      } catch (e) {
        alert(e.message || 'Failed')
      }
    }

    row2.append(creditInput, addBtn, deductBtn)

    const row3 = document.createElement('div')
    row3.className = 'flex items-center gap-2 flex-wrap'

    const tierSelect = el('select', { multiple: true, size: 3 })
    tierSelect.className = 'rounded border border-gray-300 px-2 py-1 text-xs min-w-[130px]'

    const currentTiers = normalizeTiers(u.allowed_api_tiers)
    for (const tier of API_TIERS) {
      const opt = el('option', { value: tier, textContent: tierLabel(tier) })
      if (currentTiers.includes(tier)) opt.selected = true
      tierSelect.appendChild(opt)
    }

    const saveTierBtn = el('button', { type: 'button', textContent: 'Save tiers' })
    saveTierBtn.className = 'text-sm underline'
    saveTierBtn.onclick = async () => {
      const picked = Array.from(tierSelect.selectedOptions).map((o) => o.value)
      if (picked.length < 1) {
        alert('Select at least one API tier')
        return
      }

      try {
        await apiFetch(API.updateUserApiTiers(u.id), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ allowed_api_tiers: picked }),
        })
        await loadUsers(); await loadAudit()
      } catch (e) {
        alert(e.message || 'Failed to save API tiers')
      }
    }

    row3.append(tierSelect, saveTierBtn)

    wrap.append(row1, row2, row3, resetBtn)
    actions.appendChild(wrap)

    const tierCell = td(normalizeTiers(u.allowed_api_tiers).map(tierLabel).join(', '))

    tr.append(
      td(u.id),
      td(u.company_name || u.name || ''),
      td(u.email || ''),
      td(u.credit_balance),
      td(u.credit_cap),
      td(u.status),
      tierCell,
      actions,
    )

    tbody.appendChild(tr)
  }
}

async function loadUsers() {
  try {
    const list = await apiFetch(API.users)
    renderUsers(list)
  } catch (e) {
    console.error(e)
  }
}

async function createUser(form) {
  const msg = $('#createUserMsg')
  const fd = new FormData(form)

  const password = String(fd.get('password') || '').trim()

  const payload = {
    company_name: fd.get('company_name'),
    email: fd.get('email'),
    credit_cap: fd.get('credit_cap') || 0,
    credit_balance: fd.get('credit_balance') || 0,
    is_admin: fd.get('is_admin') ? 1 : 0,
    allowed_api_tiers: normalizeTiers(fd.getAll('allowed_api_tiers')),
  }

  if (!payload.allowed_api_tiers.length) {
    payload.allowed_api_tiers = ['paid_1']
  }

  if (password) payload.password = password

  try {
    setMsg(msg, 'Creating…')
    await apiFetch(API.createUser, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })
    form.reset()
    setMsg(msg, 'Created (email sent)', 'text-amber-700')
    await loadUsers(); await loadAudit();
  } catch (e) {
    const errors = e?.data?.errors
    const details = errors ? Object.values(errors).flat().join(' ') : ''
    setMsg(msg, (e.message || 'Failed') + (details ? ` — ${details}` : ''), 'text-red-600')
  }
}

// ---------------------------------------------------------------------------
// Invoices
// ---------------------------------------------------------------------------
function renderInvoices(list) {
  const tbody = $('#invoicesTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  const msg = $('#invoiceMsg')
  setMsg(msg, Array.isArray(list) ? `${list.length} invoice(s)` : '')

  for (const inv of (Array.isArray(list) ? list : [])) {
    const tr = document.createElement('tr')

    const actions = document.createElement('td')
    actions.className = 'p-2 border-b align-top'

    if (String(inv.status) === 'pending') {
      const approveBtn = el('button', { type: 'button', textContent: 'Approve' })
      approveBtn.className = 'text-sm underline'
      approveBtn.onclick = async () => {
        try {
          await apiFetch(API.approveInvoice(inv.id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) })
          await loadInvoices(); await loadUsers(); await loadLedger(); await loadAudit();
        } catch (e) {
          alert(e.message || 'Failed')
        }
      }

      const rejectBtn = el('button', { type: 'button', textContent: 'Reject' })
      rejectBtn.className = 'text-sm underline text-red-700 ml-3'
      rejectBtn.onclick = async () => {
        const note = prompt('Rejection note (required):')
        if (!note) return
        try {
          await apiFetch(API.rejectInvoice(inv.id), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_note: note }) })
          await loadInvoices(); await loadAudit();
        } catch (e) {
          alert(e.message || 'Failed')
        }
      }

      actions.append(approveBtn, rejectBtn)
    } else {
      actions.textContent = inv.admin_note ? String(inv.admin_note).slice(0, 80) : ''
    }

    tr.append(
      td(inv.invoice_number),
      td(inv.user_company_name || inv.user_name || inv.user_id),
      td(inv.requested_credits),
      td(inv.requested_amount_usd),
      td(inv.status),
      td(fmtDate(inv.created_at)),
      actions,
    )

    tbody.appendChild(tr)
  }
}

async function loadInvoices() {
  const status = $('#invoiceStatus')?.value || ''
  try {
    const list = await apiFetch(API.invoices(status))
    renderInvoices(list)
  } catch (e) {
    console.error(e)
  }
}

// ---------------------------------------------------------------------------
// Documents
// ---------------------------------------------------------------------------
function renderDocs(list) {
  const tbody = $('#adminDocsTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  for (const d of (Array.isArray(list) ? list : [])) {
    const tr = document.createElement('tr')

    const csvCell = document.createElement('td')
    csvCell.className = 'p-2 border-b'
    if (d.csv_download) {
      const a = el('a', { href: d.csv_download, textContent: 'Download' })
      a.className = 'underline'
      a.setAttribute('download', '')
      csvCell.appendChild(a)
    }

    const xlsxCell = document.createElement('td')
    xlsxCell.className = 'p-2 border-b'
    if (d.xlsx_download) {
      const a = el('a', { href: d.xlsx_download, textContent: 'Download' })
      a.className = 'underline'
      a.setAttribute('download', '')
      xlsxCell.appendChild(a)
    }

    const delCell = document.createElement('td')
    delCell.className = 'p-2 border-b'
    const delBtn = el('button', { type: 'button', textContent: 'Delete' })
    delBtn.className = 'text-sm underline text-red-700'
    delBtn.onclick = async () => {
      if (!confirm(`Delete doc ${d.id}?`)) return
      try {
        await apiFetch(API.deleteDocument(d.id), { method: 'DELETE' })
        await loadDocs();
      } catch (e) {
        alert(e.message || 'Failed')
      }
    }
    delCell.appendChild(delBtn)

    tr.append(
      td(d.id),
      td(d.user_company_name || d.user_name || d.user_id),
      td(d.filename),
      td(d.status),
      td(`${d.credit_status || ''} (res ${d.credits_reserved || 0})`),
      td(`${d.page_start || ''}-${d.page_end || ''} (${d.pages_requested || 0})`),
      csvCell,
      xlsxCell,
      td(fmtDate(d.created_at)),
      delCell,
    )

    tbody.appendChild(tr)
  }
}

async function loadDocs() {
  try {
    const list = await apiFetch(API.documents)
    renderDocs(list)
  } catch (e) {
    console.error(e)
  }
}

// ---------------------------------------------------------------------------
// Ledger
// ---------------------------------------------------------------------------
function renderLedger(list) {
  const tbody = $('#ledgerTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  for (const l of (Array.isArray(list) ? list : [])) {
    const tr = document.createElement('tr')
    tr.append(
      td(l.id),
      td(l.user_company_name || l.user_name || l.user_id),
      td(l.action_type),
      td(l.credits),
      td(l.balance_before),
      td(l.balance_after),
      td(l.document_id ?? ''),
      td(fmtDate(l.created_at)),
    )
    tbody.appendChild(tr)
  }
}

async function loadLedger() {
  try {
    const list = await apiFetch(API.ledger)
    renderLedger(list)
  } catch (e) {
    console.error(e)
  }
}

// ---------------------------------------------------------------------------
// Audit
// ---------------------------------------------------------------------------
function renderAudit(list) {
  const tbody = $('#auditTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  for (const a of (Array.isArray(list) ? list : [])) {
    const tr = document.createElement('tr')
    const entity = a.entity_type ? `${a.entity_type}:${a.entity_id ?? ''}` : ''
    tr.append(
      td(a.id),
      td(a.event_key),
      td(a.actor_company_name || a.actor_name || a.actor_user_id || ''),
      td(a.target_company_name || a.target_name || a.target_user_id || ''),
      td(entity),
      td(fmtDate(a.created_at)),
    )
    tbody.appendChild(tr)
  }
}

async function loadAudit() {
  try {
    const list = await apiFetch(API.audit)
    renderAudit(list)
  } catch (e) {
    console.error(e)
  }
}

// ---------------------------------------------------------------------------
// Init
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
  $('#settingsForm')?.addEventListener('submit', (e) => { e.preventDefault(); saveSettings() })
  $('#refreshUsers')?.addEventListener('click', () => loadUsers())
  $('#createUserForm')?.addEventListener('submit', (e) => { e.preventDefault(); createUser(e.currentTarget) })

  $('#refreshInvoices')?.addEventListener('click', () => loadInvoices())
  $('#invoiceStatus')?.addEventListener('change', () => loadInvoices())

  $('#refreshDocs')?.addEventListener('click', () => loadDocs())
  $('#refreshLedger')?.addEventListener('click', () => loadLedger())
  $('#refreshAudit')?.addEventListener('click', () => loadAudit())

  // initial
  loadSettings()
  loadUsers()
  loadInvoices()
  loadDocs()
  loadLedger()
  loadAudit()
})
