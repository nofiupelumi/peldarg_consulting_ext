import { PDFDocument } from 'pdf-lib'

const API = {
  upload: '/api/upload',
  list: '/api/documents',
  delete: (id) => `/api/documents/${id}`
}

function $(sel){ return document.querySelector(sel) }
function el(tag, attrs={}){ const e=document.createElement(tag); Object.assign(e, attrs); return e }

let totalPages = null
let pagesRequested = null
let parsingPdf = false

function getCreditBalance(){
  const form = $('#uploadForm')
  const raw = form?.dataset?.creditBalance
  const parsed = parseInt(String(raw ?? '0'), 10)
  return Number.isFinite(parsed) ? parsed : 0
}

function setCreditBalance(nextBalance){
  const form = $('#uploadForm')
  if (form) form.dataset.creditBalance = String(nextBalance)
}

function setGateMessage({ text, tone }){
  const gate = $('#creditGateMsg')
  if (!gate) return
  if (!text) {
    gate.textContent = ''
    gate.classList.add('hidden')
    gate.classList.remove('text-red-600', 'text-green-700', 'text-gray-700')
    return
  }
  gate.textContent = text
  gate.classList.remove('hidden')
  gate.classList.remove('text-red-600', 'text-green-700', 'text-gray-700')
  gate.classList.add(tone || 'text-gray-700')
}

function validateAndComputePagesRequested(){
  const pageError = $('#pageValidationError')
  const sp = parseInt($('#page_start')?.value?.trim() || '0', 10)
  const ep = parseInt($('#page_end')?.value?.trim() || '0', 10)

  if (sp && ep && ep < sp) {
    if (pageError) {
      pageError.textContent = 'End page must be greater than or equal to start page'
      pageError.classList.remove('hidden')
    }
    return null
  }

  if (pageError) pageError.classList.add('hidden')

  if (!totalPages || totalPages < 1) return null

  const effectiveStart = sp > 0 ? sp : 1
  const effectiveEnd = ep > 0 ? ep : totalPages

  if (effectiveStart < 1 || effectiveEnd < 1 || effectiveStart > effectiveEnd) return null
  if (effectiveEnd > totalPages) {
    if (pageError) {
      pageError.textContent = `End page cannot exceed total pages (${totalPages})`
      pageError.classList.remove('hidden')
    }
    return null
  }

  return (effectiveEnd - effectiveStart) + 1
}

function updateUploadGate(){
  const uploadBtn = $('#uploadBtn')
  if (!uploadBtn) return

  const file = $('#file')?.files?.[0]
  if (!file) {
    uploadBtn.disabled = true
    setGateMessage({ text: '', tone: null })
    pagesRequested = null
    return
  }

  if (parsingPdf) {
    uploadBtn.disabled = true
    setGateMessage({ text: 'Counting PDF pages…', tone: 'text-gray-700' })
    pagesRequested = null
    return
  }

  const computed = validateAndComputePagesRequested()
  pagesRequested = computed
  if (!computed) {
    uploadBtn.disabled = true
    setGateMessage({ text: '', tone: null })
    return
  }

  const balance = getCreditBalance()
  if (balance < computed) {
    uploadBtn.disabled = true
    setGateMessage({
      text: `Need ${computed} credits, you have ${balance}.`,
      tone: 'text-red-600',
    })
    return
  }

  uploadBtn.disabled = false
  setGateMessage({
    text: `Need ${computed} credits, you have ${balance}.`,
    tone: 'text-green-700',
  })
}

// ---------------------------------------------------------------------------
// Adaptive polling (auto-refresh) for documents list
// ---------------------------------------------------------------------------
let pollTimer = null
let pollInFlight = false
let pollDelayMs = 4000

function hasProcessing(list){
  return Array.isArray(list) && list.some(d => String(d?.status || '').toLowerCase() === 'processing')
}

function stopPolling(){
  if (pollTimer) clearTimeout(pollTimer)
  pollTimer = null
  pollDelayMs = 4000
}

function scheduleNextPoll(nextDelayMs){
  if (pollTimer) clearTimeout(pollTimer)
  pollTimer = setTimeout(() => {
    // avoid overlapping requests
    if (!pollInFlight) loadDocs({ fromPoll: true })
    else scheduleNextPoll(Math.min((nextDelayMs || pollDelayMs) + 2000, 30000))
  }, nextDelayMs)
}

function adjustDelay({ anyProcessing, fromPoll }){
  // Base behavior:
  // - While processing: poll fast (4s -> 30s backoff)
  // - When complete: stop polling
  // - When tab hidden: slow down a lot
  const hidden = document.hidden === true
  if (!anyProcessing) return null

  if (!fromPoll) {
    // After a user action (upload/delete), poll quickly.
    pollDelayMs = 3000
  } else {
    // Back off gradually during long processing runs.
    pollDelayMs = Math.min(Math.round(pollDelayMs * 1.25), 30000)
  }

  if (hidden) {
    pollDelayMs = Math.max(pollDelayMs, 20000)
  }
  return pollDelayMs
}

document.addEventListener('DOMContentLoaded', () => {
  const y = document.getElementById('year'); if (y) y.textContent = new Date().getFullYear();
  loadDocs({ fromPoll: false })

  const fileInput = $('#file')
  const pageStart = $('#page_start')
  const pageEnd = $('#page_end')

  if (fileInput) {
    fileInput.addEventListener('change', async () => {
      const file = fileInput.files?.[0]
      totalPages = null
      pagesRequested = null
      if (!file) {
        updateUploadGate()
        return
      }

      parsingPdf = true
      updateUploadGate()
      try {
        const buf = await file.arrayBuffer()
        const pdf = await PDFDocument.load(buf, { ignoreEncryption: true })
        totalPages = pdf.getPageCount()
      } catch (e) {
        totalPages = null
        const msg = $('#uploadMsg')
        if (msg) {
          msg.textContent = 'Unable to read PDF page count. Please re-export the PDF and try again.'
          msg.className = 'mt-3 text-sm text-red-600'
        }
      } finally {
        parsingPdf = false
        updateUploadGate()
      }
    })
  }

  if (pageStart) pageStart.addEventListener('input', () => updateUploadGate())
  if (pageEnd) pageEnd.addEventListener('input', () => updateUploadGate())

  updateUploadGate()

  // If user returns to the tab and there are still items processing,
  // the next poll will happen with a shorter delay.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && pollTimer) {
      // trigger a near-immediate refresh
      scheduleNextPoll(800)
    }
  })

  const up = $('#uploadForm');
  if (up) up.addEventListener('submit', async (e) => {
    e.preventDefault()
    const f = $('#file').files[0]
    if (!f) return

    updateUploadGate()
    if ($('#uploadBtn')?.disabled) return
    
    const sp = parseInt($('#page_start')?.value?.trim() || '0', 10)
    const ep = parseInt($('#page_end')?.value?.trim() || '0', 10)
    
    const fd = new FormData()
    fd.append('file', f)
    if ($('#session')?.value) fd.append('session', $('#session').value)
    if (sp > 0) fd.append('start_page', sp)
    if (ep > 0) fd.append('end_page', ep)
    
    // Get CSRF token
    const csrfToken = document.querySelector('input[name="_token"]')?.value

    const uploadBtn = $('#uploadBtn')
    const uploadProgress = $('#uploadProgress')
    const progressBar = $('#progressBar')
    const progressText = $('#progressText')
    const msg = $('#uploadMsg')
    
    if (!csrfToken) {
      if (msg) {
        msg.textContent = 'Security token missing. Please refresh the page.'
        msg.className = 'mt-3 text-sm text-red-600'
      }
      return
    }
    
    // Show progress bar
    uploadBtn.disabled = true
    uploadProgress.classList.remove('hidden')
    progressBar.style.width = '0%'
    progressText.textContent = 'Uploading PDF...'
    if (msg) msg.textContent = ''

    try {
      // Simulate progress during upload
      let progress = 0
      const progressInterval = setInterval(() => {
        progress += 5
        if (progress <= 90) {
          progressBar.style.width = progress + '%'
        }
      }, 200)

      const r = await fetch(API.upload, { 
        method:'POST', 
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: fd 
      })

      if (r.status === 413) {
        throw new Error('File is too large for server upload limit. Please reduce PDF size or increase server limits (Nginx client_max_body_size / PHP upload_max_filesize, post_max_size).')
      }
      
      clearInterval(progressInterval)
      progressBar.style.width = '100%'
      
      // Check if response is JSON
      const contentType = r.headers.get('content-type')
      if (!contentType || !contentType.includes('application/json')) {
        throw new Error('Server returned non-JSON response. Check if you are logged in.')
      }
      
      const result = await r.json()
      
      if (r.ok) {
        progressText.textContent = 'Upload complete! Processing...'
        if (msg) {
          msg.textContent = 'Queued for processing. Refresh documents shortly.'
          msg.className = 'mt-3 text-sm text-green-700'
        }

        if (typeof result?.credit_balance === 'number') {
          setCreditBalance(result.credit_balance)
        }

        setTimeout(() => {
          uploadProgress.classList.add('hidden')
          loadDocs({ fromPoll: false })
          up.reset()
          totalPages = null
          pagesRequested = null
          updateUploadGate()
        }, 2000)
      } else {
        throw new Error(result.message || result.error || 'Upload failed')
      }
    } catch(err){
      uploadProgress.classList.add('hidden')
      uploadBtn.disabled = false
      if (msg) {
        msg.textContent = 'Upload failed: ' + (err.message || 'Unknown error')
        msg.className = 'mt-3 text-sm text-red-600'
      }
    }
  })
})

async function loadDocs(opts = {}){
  try {
    pollInFlight = true
    const r = await fetch(API.list, {
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    const list = await r.json()
    renderDocs(list)

    const any = hasProcessing(list)
    const next = adjustDelay({ anyProcessing: any, fromPoll: !!opts.fromPoll })
    if (next == null) {
      stopPolling()
    } else {
      scheduleNextPoll(next)
    }
  } catch(err){
    // ignore
  } finally {
    pollInFlight = false
  }
}

function renderDocs(list){
  const tbody = document.querySelector('#docsTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''
  list.forEach(d => {
    const tr = el('tr')
    tr.append(
      td(d.id),
      td(d.filename),
      td(d.session||''),
      td(d.status),
      tdLink(d.csv_download, 'csv'),
      tdLink(d.xlsx_download, 'xlsx'),
      td(new Date(d.created_at).toLocaleString()),
      tdDelete(d.id)
    )
    tbody.appendChild(tr)
  })
}

async function deleteDoc(id) {
  if (!confirm('Delete this document and its extracted data?')) return
  try {
    const csrfToken = document.querySelector('input[name="_token"]')?.value
    if (!csrfToken) {
      alert('Security token missing. Please refresh the page.')
      return
    }

    const r = await fetch(API.delete(id), { 
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    const contentType = r.headers.get('content-type') || ''
    if (!contentType.includes('application/json')) {
      throw new Error('Server returned non-JSON response')
    }
    const result = await r.json()
    if (!r.ok) {
      throw new Error(result.message || result.error || 'Delete failed')
    }
    loadDocs({ fromPoll: false })
  } catch(err) {
    alert('Delete failed: ' + (err.message || 'Unknown error'))
  }
}

function renderResults(rows){
  const tbody = document.querySelector('#resultsTable tbody')
  if (!tbody) return
  tbody.innerHTML = ''
  rows.forEach(r => {
    const tr = el('tr')
    tr.append(
      td(r.surname),
      td(r.first_name),
      td(r.other_name||''),
      td(r.course_studied||''),
      td(r.faculty||''),
      td(r.grade||''),
      td(r.qualification_obtained||''),
      td(r.session||'')
    )
    tbody.appendChild(tr)
  })
}

function td(v){ const d=document.createElement('td'); d.textContent=v??''; d.className='p-2 border-b'; return d }
function tdLink(url, format){
  const d=document.createElement('td'); d.className='p-2 border-b'
  if(url){
    const a=document.createElement('a');
    a.href=url;
    a.className='text-lime-700 underline';
    a.textContent = 'Download';
    a.setAttribute('download', ''); // hint browser to download
    d.appendChild(a)
  }
  return d
}
function tdDelete(id){
  const d=document.createElement('td'); d.className='p-2 border-b'
  const btn=document.createElement('button');
  btn.textContent='Delete';
  btn.className='text-red-600 hover:text-red-800 underline text-sm';
  btn.onclick = () => deleteDoc(id);
  d.appendChild(btn);
  return d;
}

