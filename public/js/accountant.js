(async function () {
  // Helpers
  const peso = c => `₱${(c/100).toFixed(2)}`;
  const $ = sel => document.querySelector(sel);
  const $$ = sel => [...document.querySelectorAll(sel)];

  // If on queue page
  if (location.pathname.endsWith('/accountant_queue.php')) {
    // Load filter dropdowns (businesses/branches) + queue
    await loadFilters();
    await loadQueue();

    $('#btnFilter').addEventListener('click', async (e) => {
      e.preventDefault();
      await loadQueue();
    });

    async function loadFilters() {
      // Minimal lightweight: derive from pending API
      const res = await fetch('/api/accounting_pending.php');
      const data = await res.json();
      const busSel = $('#f_business');
      const brSel  = $('#f_branch');

      const businesses = [...new Map(data.items.map(i => [i.business_id, {id:i.business_id, name:i.business_name}])).values()];
      busSel.innerHTML = `<option value="">All</option>` + businesses.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
      const branches = [...new Map(data.items.map(i => [i.branch_id, {id:i.branch_id, name:i.branch_name, business_id:i.business_id}])).values()];
      brSel.innerHTML = `<option value="">All</option>` + branches.map(b => `<option data-business="${b.business_id}" value="${b.id}">${b.name}</option>`).join('');
      busSel.addEventListener('change', () => {
        const bid = busSel.value;
        $$('#f_branch option').forEach(op => {
          if (!op.value) return;
          op.hidden = (bid && op.dataset.business !== bid);
        });
        brSel.value = '';
      });
    }

    async function loadQueue() {
      const params = new URLSearchParams();
      const b = $('#f_business').value; if (b) params.set('business_id', b);
      const br = $('#f_branch').value; if (br) params.set('branch_id', br);
      const f = $('#f_from').value; if (f) params.set('from', f);
      const t = $('#f_to').value; if (t) params.set('to', t);

      const res = await fetch('/api/accounting_pending.php?' + params.toString());
      const data = await res.json();

      const wrap = $('#queueContainer');
      if (!data.items.length) {
        wrap.innerHTML = `<div class="alert alert-secondary">No pending reports.</div>`;
        return;
      }
      // Group by business → branch → date
      const groups = {};
      for (const it of data.items) {
        groups[it.business_name] ??= {};
        groups[it.business_name][it.branch_name] ??= [];
        groups[it.business_name][it.branch_name].push(it);
      }

      let html = '';
      for (const [biz, branches] of Object.entries(groups)) {
        html += `<div class="mb-3"><h5 class="mb-2">${biz}</h5>`;
        for (const [brName, items] of Object.entries(branches)) {
          html += `<div class="card mb-2"><div class="card-header fw-semibold">${brName}</div><div class="list-group list-group-flush">`;
          items.sort((a,b)=> (a.report_date > b.report_date) ? -1 : 1).forEach(r => {
            const badge = r.has_discrepancy ? `<span class="badge bg-danger ms-2">Discrepancy</span>` : '';
            html += `<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        href="/accountant_report.php?id=${r.report_id}">
                      <div>
                        <div>${r.report_date} — <strong>${r.shift}</strong>${badge}</div>
                        <small class="text-muted">Status: ${r.status}</small>
                      </div>
                      <div class="text-end">
                        <div>Total Sales: ${peso(r.total_sales_cents)}</div>
                        <small class="text-muted">E-wallet: ${peso(r.total_ewallet_cents)} | Expenses: ${peso(r.total_expenses_cents)}</small>
                      </div>
                    </a>`;
          });
          html += `</div></div>`;
        }
        html += `</div>`;
      }
      wrap.innerHTML = html;
    }
    return; // end queue page
  }

  // If on report page
  if (location.pathname.endsWith('/accountant_report.php')) {
    const rid = +$('#reportRoot').dataset.reportId;
    if (!rid) return;

    await loadReport();

    $('#btnFinalize').addEventListener('click', async () => {
      if (!confirm('Finalize this report? This will complete it.')) return;
      const res = await fetch('/api/dsir_finalize.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ report_id: rid })
      });
      const data = await res.json();
      if (!res.ok) return showAlert('danger', data.error || 'Finalize failed');
      showAlert('success', 'Report finalized.');
      await loadReport();
    });

    $('#btnCarry').addEventListener('click', async () => {
      if (!confirm('Carry discrepancy to next shift?')) return;
      const res = await fetch('/api/discrepancy_carry.php', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ report_id: rid })
      });
      const data = await res.json();
      if (!res.ok) return showAlert('danger', data.error || 'Carry-over failed');
      showAlert('success', data.message || 'Discrepancy flagged for next shift.');
      await loadReport();
    });

    async function loadReport() {
      const res = await fetch('/api/dsir_get.php?id=' + rid);
      const data = await res.json();
      if (!res.ok) { showAlert('danger', data.error || 'Failed to load'); return; }

      // Header
      $('#headerInfo').innerHTML = `
        <div class="row">
          <div class="col-md-3"><strong>Business</strong><br>${data.header.business_name}</div>
          <div class="col-md-3"><strong>Branch</strong><br>${data.header.branch_name}</div>
          <div class="col-md-3"><strong>Date/Shift</strong><br>${data.header.report_date} ${data.header.shift}</div>
          <div class="col-md-3"><strong>Status</strong><br>${data.header.status}</div>
        </div>`;

      // Totals
      const t = data.totals;
      $('#totals').innerHTML = `
        <div class="row">
          <div class="col-md-3">Total Sales: <strong>${peso(t.sales_cents)}</strong></div>
          <div class="col-md-3">Expenses: <strong>${peso(t.expenses_cents)}</strong></div>
          <div class="col-md-3">E-wallet: <strong>${peso(t.ewallet_cents)}</strong></div>
          <div class="col-md-3">Discounts: <strong>${peso(t.discounts_cents)}</strong></div>
          <div class="col-md-3 mt-2">Expected Cash: <strong>${peso(t.expected_cash_cents)}</strong></div>
          <div class="col-md-3 mt-2">Actual Cash: <strong>${peso(t.actual_cash_cents)}</strong></div>
          <div class="col-md-3 mt-2">Discrepancy: <strong class="${t.discrepancy_cents!==0?'text-danger':''}">${peso(t.discrepancy_cents)}</strong></div>
        </div>`;

      // Lines
      const tbody = $('#linesTable tbody');
      tbody.innerHTML = data.lines.map(r => `
        <tr>
          <td>${r.product_name}</td>
          <td>${r.beginning}</td><td>${r.in_wh}</td><td>${r.in_transfer}</td><td>${r.out_transfer}</td>
          <td>${r.ending}</td><td>${r.usage_qty}</td>
          <td>${peso(r.srp_cents)}</td><td>${peso(r.sales_cents)}</td>
        </tr>
      `).join('');

      // E-wallet
      const ewBody = $('#ewTable tbody');
      ewBody.innerHTML = data.ewallet.map(w => `
        <tr>
          <td>${w.platform}</td>
          <td>${w.reference}</td>
          <td>${peso(w.amount_cents)}</td>
          <td>${w.received ? 'Yes' : 'No'}</td>
          <td>${w.received ? '' : `<button class="btn btn-sm btn-outline-primary" data-ew="${w.id}">Mark Received</button>`}</td>
        </tr>
      `).join('');
      ewBody.querySelectorAll('button[data-ew]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id = +btn.dataset.ew;
          const res = await fetch('/api/ewallet_received.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id, received: 1 })
          });
          const out = await res.json();
          if (!res.ok) return showAlert('danger', out.error || 'Update failed');
          showAlert('success','Marked as received.');
          await loadReport();
        });
      });

      // Expenses
      $('#expTable tbody').innerHTML = data.expenses.map(e => `
        <tr><td>${e.label}</td><td>${peso(e.amount_cents)}</td></tr>
      `).join('');

      // Discounts
      $('#discTable tbody').innerHTML = data.discounts.map(d => `
        <tr><td>${d.customer_name}</td><td>${d.id_number}</td><td>${d.product_name}</td><td>${d.qty}</td><td>${peso(d.discount_cents)}</td></tr>
      `).join('');
    }

    function showAlert(type, msg) {
      $('#alertBox').innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
      setTimeout(()=> $('#alertBox').innerHTML='', 3000);
    }
  }
})();
