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

  paymentHistory: (params = '') => `/api/admin/payment-history${params ? `?${params}` : ''}`,
  ledger: '/api/admin/ledger',
  audit: '/api/admin/audit',
  activityStreams: (params = '') => `/api/admin/activity-streams${params ? `?${params}` : ''}`,
  reconciliation: (params = '') => `/api/admin/reconciliation${params ? `?${params}` : ''}`,

  settingsGet: '/api/admin/settings',
  settingsUpdate: '/api/admin/settings',
}

const API_TIERS = ['paid_1']

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
// Payment history
// ---------------------------------------------------------------------------
function paymentSummaryText(summary) {
  return `${summary?.invoice_count ?? 0} payments | ${summary?.requested_credits ?? 0} credits | $${summary?.requested_amount_usd ?? '0.00'}`
}

function renderPaymentItems(items) {
  if (!Array.isArray(items) || items.length === 0) {
    return '<span class="text-xs text-gray-500">No payments for this period.</span>'
  }

  return items.slice(0, 5).map((item) => {
    const reference = item.payment_reference || item.gateway_reference || '—'
    return `<div class="mb-2"><div class="font-medium">${item.invoice_number}</div><div class="text-xs text-gray-600">${reference} | ${item.requested_credits} credits | $${item.requested_amount_usd} | ${fmtDate(item.payment_at || item.fulfilled_at || item.paid_at || item.created_at)}</div></div>`
  }).join('')
}

function renderPaymentHistory(payload) {
  const tbody = $('#paymentHistoryTable tbody')
  const select = $('#paymentHistoryUser')
  const msg = $('#paymentHistoryMsg')
  if (!tbody) return
  tbody.innerHTML = ''

  const users = Array.isArray(payload?.users) ? payload.users : []

  if (select) {
    const selected = select.value
    select.innerHTML = '<option value="">All recent users</option>'
    for (const entry of users) {
      const option = el('option', { value: String(entry.user_id), textContent: `${entry.user_name} (${entry.user_email})` })
      if (selected === String(entry.user_id)) option.selected = true
      select.appendChild(option)
    }
  }

  if (users.length === 0) {
    const tr = document.createElement('tr')
    tr.appendChild(tdHtml('<span class="text-sm text-gray-500">No payment history found.</span>'))
    tr.firstChild.colSpan = 5
    tbody.appendChild(tr)
    setMsg(msg, 'No payment history found for the selected filters.')
    return
  }

  for (const entry of users) {
    const history = entry.payment_history || {}
    const tr = document.createElement('tr')
    tr.append(
      tdHtml(`<div class="font-medium">${entry.user_name}</div><div class="text-xs text-gray-600">${entry.user_email}</div>`),
      td(paymentSummaryText(history.summary?.selected_period)),
      td(paymentSummaryText(history.summary?.current_month)),
      td(paymentSummaryText(history.summary?.current_year)),
      tdHtml(renderPaymentItems(history.items)),
    )
    tbody.appendChild(tr)
  }

  setMsg(msg, `${users.length} user payment history record(s) loaded.`, 'text-amber-700')
}

async function loadPaymentHistory() {
  const params = new URLSearchParams()
  const userId = $('#paymentHistoryUser')?.value || ''
  const year = $('#paymentHistoryYear')?.value || ''
  const month = $('#paymentHistoryMonth')?.value || ''
  if (userId) params.set('user_id', userId)
  if (year) params.set('year', year)
  if (month) params.set('month', month)

  try {
    const payload = await apiFetch(API.paymentHistory(params.toString()))
    renderPaymentHistory(payload)
  } catch (e) {
    console.error(e)
    setMsg($('#paymentHistoryMsg'), e.message || 'Failed to load payment history', 'text-red-600')
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
      td(l.partner_request_id || ''),
      td(l.partner_domain || ''),
      td(l.partner_user_id || ''),
      td(`res ${l.reserved_credits || 0} / cons ${l.consumed_credits || 0} / ref ${l.refunded_credits || 0}`),
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
      td(a.partner_request_id || ''),
      td(a.partner_domain || ''),
      td(a.partner_user_id || ''),
      td(`res ${a.reserved_credits || 0} / cons ${a.consumed_credits || 0} / ref ${a.refunded_credits || 0}`),
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
// Activity streams
// ---------------------------------------------------------------------------
function renderActivityStreams(payload) {
  const tbody = $('#activityStreamsTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  const list = Array.isArray(payload?.streams) ? payload.streams : []
  if ($('#activitySummary')) {
    const pg = payload?.pagination || {}
    $('#activitySummary').textContent = `${pg.total ?? list.length} stream(s) | page ${pg.current_page ?? 1}/${pg.last_page ?? 1}`
  }

  for (const row of list) {
    const events = Array.isArray(row.events) ? row.events : []
    const timeline = events.length
      ? events.map((ev) => `#${ev.sequence} ${ev.event_key} (${ev.status || 'n/a'})`).join(' | ')
      : '—'

    const tr = document.createElement('tr')
    tr.append(
      td(row.partner_request_id || ''),
      td(row.partner_name || ''),
      td(row.user_email || ''),
      td(row.extraction_type || ''),
      td(`${row.status || ''} / ${row.phase || ''}`),
      td(`${row.pages_processed || 0}/${row.pages_requested || 0}`),
      td(`res ${row.credits_reserved || 0} / cons ${row.credits_consumed || 0} / ref ${row.credits_refunded || 0}`),
      td(`${row.last_event_key || '—'} @ ${fmtDate(row.last_event_at)}`),
      td(timeline),
    )
    tbody.appendChild(tr)
  }
}

async function loadActivityStreams() {
  const params = new URLSearchParams()
  const dateFrom = $('#activity_date_from')?.value || ''
  const dateTo = $('#activity_date_to')?.value || ''
  const partner = $('#activity_partner')?.value?.trim() || ''
  const user = $('#activity_user')?.value?.trim() || ''
  const extractionType = $('#activity_extraction_type')?.value || ''
  const status = $('#activity_status')?.value || ''
  const creditOutcome = $('#activity_credit_outcome')?.value || ''
  const requestId = $('#activity_partner_request_id')?.value?.trim() || ''

  if (dateFrom) params.set('date_from', dateFrom)
  if (dateTo) params.set('date_to', dateTo)
  if (partner) params.set('partner', partner)
  if (user) params.set('user', user)
  if (extractionType) params.set('extraction_type', extractionType)
  if (status) params.set('status', status)
  if (creditOutcome) params.set('credit_outcome', creditOutcome)
  if (requestId) params.set('partner_request_id', requestId)

  try {
    const payload = await apiFetch(API.activityStreams(params.toString()))
    renderActivityStreams(payload)
  } catch (e) {
    console.error(e)
    if ($('#activitySummary')) $('#activitySummary').textContent = e.message || 'Failed to load activity streams'
  }
}

// ---------------------------------------------------------------------------
// Reconciliation
// ---------------------------------------------------------------------------
function renderReconciliation(data) {
  if (!data) return

  const peldarg = data.peldarg || {}
  const partner = data.partner || {}
  const variance = data.variance || {}

  if ($('#reconRange')) $('#reconRange').textContent = `${data.date_from || '—'} to ${data.date_to || '—'}`
  if ($('#reconPeldargProcessed')) $('#reconPeldargProcessed').textContent = String(peldarg.pages_processed_total ?? 0)
  if ($('#reconPartnerProcessed')) $('#reconPartnerProcessed').textContent = String(partner.processed_pages_total ?? 0)
  if ($('#reconDelta')) $('#reconDelta').textContent = String(variance.processed_pages_delta ?? 0)
  if ($('#reconConsumedDelta')) $('#reconConsumedDelta').textContent = String(variance.consumed_vs_processed_delta ?? 0)
  if ($('#reconPeldargCredits')) {
    $('#reconPeldargCredits').textContent = `Reserved ${peldarg.reserved_credits_total ?? 0} | Consumed ${peldarg.consumed_credits_total ?? 0} | Refunded ${peldarg.refunded_credits_total ?? 0}`
  }
  if ($('#reconPartnerBreakdown')) {
    $('#reconPartnerBreakdown').textContent = partner.available === false
      ? (partner.error || 'Partner reconciliation unavailable')
      : `Booklet ${partner.booklet_pages_total ?? 0} | Certificate ${partner.certificate_pages_total ?? 0} | Docs ${partner.completed_documents_total ?? 0}`
  }
}

async function loadReconciliation() {
  const params = new URLSearchParams()
  const dateFrom = $('#recon_date_from')?.value || ''
  const dateTo = $('#recon_date_to')?.value || ''
  if (dateFrom) params.set('date_from', dateFrom)
  if (dateTo) params.set('date_to', dateTo)

  try {
    const data = await apiFetch(API.reconciliation(params.toString()))
    renderReconciliation(data)
  } catch (e) {
    console.error(e)
    if ($('#reconPartnerBreakdown')) $('#reconPartnerBreakdown').textContent = e.message || 'Failed to load reconciliation'
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
  $('#refreshPaymentHistory')?.addEventListener('click', () => loadPaymentHistory())
  $('#paymentHistoryUser')?.addEventListener('change', () => loadPaymentHistory())
  $('#paymentHistoryMonth')?.addEventListener('change', () => loadPaymentHistory())
  $('#paymentHistoryYear')?.addEventListener('change', () => loadPaymentHistory())
  $('#refreshLedger')?.addEventListener('click', () => loadLedger())
  $('#refreshAudit')?.addEventListener('click', () => loadAudit())
  $('#activityStreamForm')?.addEventListener('submit', (e) => { e.preventDefault(); loadActivityStreams() })
  $('#refreshActivityStreams')?.addEventListener('click', () => loadActivityStreams())
  $('#reconciliationForm')?.addEventListener('submit', (e) => { e.preventDefault(); loadReconciliation() })
  $('#refreshReconciliation')?.addEventListener('click', () => loadReconciliation())

  const now = new Date()
  if ($('#paymentHistoryYear') && !$('#paymentHistoryYear').value) $('#paymentHistoryYear').value = String(now.getFullYear())
  if ($('#paymentHistoryMonth') && !$('#paymentHistoryMonth').value) $('#paymentHistoryMonth').value = String(now.getMonth() + 1)

  // initial
  loadSettings()
  loadUsers()
  loadInvoices()
  loadDocs()
  loadPaymentHistory()
  loadLedger()
  loadAudit()
  loadActivityStreams()
  loadReconciliation()
})
