const API = {
  logs: '/api/booklet-logs',
}

function $(sel){ return document.querySelector(sel) }
function td(v){ const cell = document.createElement('td'); cell.className = 'p-2 border-b'; cell.textContent = v ?? ''; return cell }

function numberFmt(v){
  const n = Number(v || 0)
  if (!Number.isFinite(n)) return '0'
  try { return n.toLocaleString() } catch { return String(n) }
}

function fmtDate(v){
  try { return v ? new Date(v).toLocaleString() : '' } catch { return '' }
}

function setSummary(summary){
  const overall = summary?.overall || {}
  const filtered = summary?.filtered || {}

  $('#overallUploads').textContent = numberFmt(overall.uploaded_total)
  $('#overallSuccessful').textContent = numberFmt(overall.successful_total)
  $('#overallRows').textContent = numberFmt(overall.student_rows_total)

  $('#filteredUploads').textContent = numberFmt(filtered.uploaded_total)
  $('#filteredSuccessful').textContent = numberFmt(filtered.successful_total)
  $('#filteredRows').textContent = numberFmt(filtered.student_rows_total)
}

function renderRows(rows){
  const tbody = $('#bookletLogTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''

  for (const d of (Array.isArray(rows) ? rows : [])) {
    const pages = `${d.page_start || ''}-${d.page_end || ''} (${d.pages_requested || 0}) / ${d.pages_processed ?? ''}`
    const results = `${d.pages_with_results ?? ''}`

    const tr = document.createElement('tr')
    tr.append(
      td(d.id),
      td(d.filename || ''),
      td(d.session || ''),
      td(d.status || ''),
      td(pages),
      td(results),
      td(numberFmt(d.students_rows || 0)),
      td(d.api_tier || ''),
      td(fmtDate(d.created_at)),
    )
    tbody.appendChild(tr)
  }
}

async function loadBookletLogs(){
  const year = ($('#filterYear')?.value || '').trim()
  const month = ($('#filterMonth')?.value || '').trim()
  const msg = $('#bookletLogMsg')

  const params = new URLSearchParams()
  if (year) params.set('year', year)
  if (month) params.set('month', month)

  const url = params.toString() ? `${API.logs}?${params.toString()}` : API.logs

  try {
    if (msg) { msg.textContent = 'Loading booklet logs...'; msg.className = 'mt-3 text-sm text-slate-600' }

    const r = await fetch(url, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })

    const data = await r.json()
    if (!r.ok) {
      throw new Error(data?.message || data?.error || 'Failed to load booklet log')
    }

    setSummary(data?.summary || {})
    renderRows(data?.rows || [])

    const rowCount = Array.isArray(data?.rows) ? data.rows.length : 0
    if (msg) {
      msg.textContent = `Loaded ${numberFmt(rowCount)} booklet record(s).`
      msg.className = 'mt-3 text-sm text-amber-700'
    }
  } catch (e) {
    if (msg) {
      msg.textContent = e?.message || 'Failed to load booklet log'
      msg.className = 'mt-3 text-sm text-red-600'
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const form = $('#bookletLogFilterForm')
  const clearBtn = $('#clearFilterBtn')

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault()
      await loadBookletLogs()
    })
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', async () => {
      const year = $('#filterYear')
      const month = $('#filterMonth')
      if (year) year.value = ''
      if (month) month.value = ''
      await loadBookletLogs()
    })
  }

  loadBookletLogs()
})
