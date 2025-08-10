document.addEventListener('DOMContentLoaded', async () => {
  const tableBody    = document.querySelector('#productTable tbody');
  const expensesBody = document.querySelector('#expensesTable tbody');
  const ewalletBody  = document.querySelector('#ewalletTable tbody');
  const discountBody = document.querySelector('#discountTable tbody');
  const form         = document.getElementById('dsirForm');

  let products = [];
  try {
    const res = await fetch('/api/products_for_user.php');
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Load products failed');
    products = data.products || [];
  } catch (e) {
    alert('Could not load products.'); console.error(e);
    products = [];
  }

  // Render products
  products.forEach(p => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${p.sku || ''}</td>
      <td>${p.name}</td>
      <td><input type="number" class="form-control" name="beginning" value="0"></td>
      <td><input type="number" class="form-control" name="in_wh" value="0"></td>
      <td><input type="number" class="form-control" name="in_transfer" value="0"></td>
      <td><input type="number" class="form-control" name="out_transfer" value="0"></td>
      <td><input type="number" class="form-control" name="ending" value="0"></td>
      <td class="usage">0</td>
      <td>${(p.srp_cents/100).toFixed(2)}</td>
      <td class="sales">0.00</td>
    `;
    tableBody.appendChild(row);
    row.querySelectorAll('input').forEach(inp => {
      inp.addEventListener('input', () => {
        const beg=+row.querySelector('input[name="beginning"]').value||0;
        const inWH=+row.querySelector('input[name="in_wh"]').value||0;
        const inTr=+row.querySelector('input[name="in_transfer"]').value||0;
        const outT=+row.querySelector('input[name="out_transfer"]').value||0;
        const end=+row.querySelector('input[name="ending"]').value||0;
        const usage = Math.max(0, beg+inWH+inTr-outT-end);
        row.querySelector('.usage').innerText = usage;
        row.querySelector('.sales').innerText = ((usage * p.srp_cents)/100).toFixed(2);
      });
    });
  });

  // Cash breakdown auto row totals
  document.querySelectorAll('#cashTable .pcs').forEach(inp => {
    inp.addEventListener('input', () => {
      const denom=+inp.dataset.value, pcs=+inp.value||0;
      inp.closest('tr').querySelector('.amount').innerText = (pcs*denom).toFixed(2);
    });
  });

  // Add rows handlers
  document.getElementById('addExpense').addEventListener('click', () => {
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><input class="form-control" name="label"></td>
      <td><input type="number" class="form-control" name="amount_cents" value="0"></td>
      <td><button class="btn btn-sm btn-danger removeRow" type="button">x</button></td>`;
    expensesBody.appendChild(tr);
  });

  document.getElementById('addEwallet').addEventListener('click', () => {
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><select class="form-control" name="platform">
        <option>FoodPanda</option><option>Grab</option><option>GCash</option><option>PayMaya</option><option>Other</option>
      </select></td>
      <td><input class="form-control" name="reference"></td>
      <td><input type="number" class="form-control" name="amount_cents" value="0"></td>
      <td><input class="form-control" name="note"></td>
      <td><button class="btn btn-sm btn-danger removeRow" type="button">x</button></td>`;
    ewalletBody.appendChild(tr);
  });

  document.getElementById('addDiscount').addEventListener('click', () => {
    const tr=document.createElement('tr');
    tr.innerHTML=`<td><input class="form-control" name="customer_name"></td>
      <td><input class="form-control" name="id_number"></td>
      <td><select class="form-control" name="product_id">
        ${products.map(p=>`<option value="${p.id}">${p.name}</option>`).join('')}
      </select></td>
      <td><input type="number" class="form-control" name="qty" value="1"></td>
      <td><input type="file" class="form-control" name="id_image"></td>
      <td><button class="btn btn-sm btn-danger removeRow" type="button">x</button></td>`;
    discountBody.appendChild(tr);
  });

  document.addEventListener('click', e => {
    if (e.target.classList.contains('removeRow')) e.target.closest('tr').remove();
  });

  // Save draft
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const payload = collectForm();
    localStorage.setItem('dsirDraft', JSON.stringify(payload));
    try {
      const res = await fetch('/api/dsir_sync.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const data=await res.json(); if (!res.ok) throw new Error(data.error||'Save failed');
      alert('Saved to server');
    } catch(err){ alert('Offline: draft saved locally'); console.error(err); }
  });

  // Submit & Lock
  document.getElementById('submitBtn').addEventListener('click', async () => {
    const payload = collectForm();
    try {
      const res = await fetch('/api/dsir_submit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
      const data=await res.json(); if (!res.ok) throw new Error(data.error||'Submit failed');
      alert(`Submitted!\nSales: ₱${(data.totals.sales_cents/100).toFixed(2)}\nExpenses: ₱${(data.totals.expenses_cents/100).toFixed(2)}\nE-Wallet: ₱${(data.totals.ewallet_cents/100).toFixed(2)}\nDiscounts: ₱${(data.totals.discounts_cents/100).toFixed(2)}\nDiscrepancy: ₱${(data.totals.discrepancy_cents/100).toFixed(2)}`);
    } catch(err){ alert('Could not submit (offline or server error).'); console.error(err); }
  });

  function collectForm(){
    const payload={report_date:form.report_date.value,shift:form.shift.value,lines:[],expenses:[],ewallet:[],discounts:[],cash_on_hand_cents:0,partial_remit_cents:0};
    // Lines
    tableBody.querySelectorAll('tr').forEach((row,idx)=>{
      payload.lines.push({
        product_id: products[idx]?.id, beginning:+row.querySelector('input[name="beginning"]').value||0,
        in_wh:+row.querySelector('input[name="in_wh"]').value||0, in_transfer:+row.querySelector('input[name="in_transfer"]').value||0,
        out_transfer:+row.querySelector('input[name="out_transfer"]').value||0, ending:+row.querySelector('input[name="ending"]').value||0
      });
    });
    // Expenses
    expensesBody.querySelectorAll('tr').forEach(tr=>{
      payload.expenses.push({label:tr.querySelector('input[name="label"]').value, amount_cents:+tr.querySelector('input[name="amount_cents"]').value||0});
    });
    // Ewallet
    ewalletBody.querySelectorAll('tr').forEach(tr=>{
      payload.ewallet.push({
        platform:tr.querySelector('select[name="platform"]').value,
        reference:tr.querySelector('input[name="reference"]').value,
        amount_cents:+tr.querySelector('input[name="amount_cents"]').value||0,
        note:tr.querySelector('input[name="note"]').value
      });
    });
    // Discounts
    discountBody.querySelectorAll('tr').forEach(tr=>{
      const pid=+tr.querySelector('select[name="product_id"]').value;
      const p=products.find(x=>x.id===pid); const qty=+tr.querySelector('input[name="qty"]').value||1;
      payload.discounts.push({
        customer_name:tr.querySelector('input[name="customer_name"]').value,
        id_number:tr.querySelector('input[name="id_number"]').value,
        product_id:pid, qty:qty, discount_cents: Math.round((p?.srp_cents||0)*0.2)*qty
      });
    });
    // Cash
    let cashTotal=0;
    document.querySelectorAll('#cashTable .pcs').forEach(inp=>{ cashTotal += (+inp.value||0) * (+inp.dataset.value); });
    cashTotal += (+document.getElementById('coins').value||0);
    document.getElementById('cash_on_hand').value = cashTotal.toFixed(2);
    payload.cash_on_hand_cents = Math.round(cashTotal*100);
    payload.partial_remit_cents = Math.round(((+document.getElementById('partial_remit').value)||0)*100);
    return payload;
  }

  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').catch(console.error);
  }
});
