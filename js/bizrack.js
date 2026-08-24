/*********************************
 FRONTEND DEMO DATA (TEMPORARY)
**********************************/
let products =  [];
let purchases =  [];
let sales =  [];

/*********************************
 INITIAL LOAD
**********************************/
window.onload = () => {
  showSection("purchaseSection");
  //renderPurchaseTable();
  updateStockTable();
  populateProductDatalist();
  clearPurchaseTable();
};

/*********************************
 TEMP DATA SAVE (FRONTEND ONLY)
**********************************/
function saveData() {
  localStorage.setItem("inv_products", JSON.stringify(products));
  localStorage.setItem("inv_purchases", JSON.stringify(purchases));
  localStorage.setItem("inv_sales", JSON.stringify(sales));
}

/*********************************
 SECTION NAVIGATION
**********************************/
function showSection(sectionId) {
  document.querySelectorAll(".section").forEach(sec => {
    sec.style.display = "none";
  });

  const active = document.getElementById(sectionId);
  if (active) active.style.display = "block";

  if (sectionId === "reportSection") generateReport();
  if (sectionId === "dashboardSection") renderRecentTransactions();
}

/*********************************
 PURCHASE MANAGEMENT
**********************************/

let editPurchaseIndex = null;
let filteredPurchases = [];
document.getElementById("purchaseForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const name = productName.value.trim();
  const qty = Number(purchaseQty.value);
  const rate = Number(purchaseRate.value);
  const sell = Number(sellingPrice.value);
  const date = purchaseDate.value;
  const pid = purchaseId.value;

  if (!name || qty <= 0 || rate <= 0 || sell <= 0 || !date) {
    alert("Invalid purchase details");
    return;
  }

  let product = products.find(
    p => p.name.toLowerCase() === name.toLowerCase()
  );

  if (!product) {
    product = {
      name,
      sellingPrice: sell,
      totalPurchased: 0,
      totalSold: 0,
      currentStock: 0,
      minStock: 5,
      avgCost: rate
    };
    products.push(product);
  } else {
    product.sellingPrice = sell;
    product.avgCost =
      ((product.avgCost * product.currentStock) + (rate * qty)) /
      (product.currentStock + qty);
  }

  product.totalPurchased += qty;
  product.currentStock += qty;

  const purchaseObj = {
    date,
    pid,
    name,
    qty,
    rate,
    sell,
    total: qty * rate
  };

  //  THIS IS THE KEY FIX
  if (editPurchaseIndex !== null) {
    purchases.splice(editPurchaseIndex, 0, purchaseObj);
    editPurchaseIndex = null;
  } else {
    purchases.push(purchaseObj);
  }

  saveData();
  renderPurchaseTable();
  updateStockTable();
  populateProductDatalist();
  this.reset();
});

function editPurchase(index) {
  const p = purchases[index];
  if (!p) return;

  const product = products.find(prod =>
    prod.name.toLowerCase() === p.name.toLowerCase()
  );

  // Reverse stock
  if (product) {
    product.currentStock = Math.max(0, product.currentStock - p.qty);
    product.totalPurchased = Math.max(0, product.totalPurchased - p.qty);
  }

  // Fill form
  purchaseDate.value = p.date;
  purchaseId.value = p.pid;
  productName.value = p.name;
  purchaseQty.value = p.qty;
  purchaseRate.value = p.rate;
  sellingPrice.value = p.sell;

  // Remove old record temporarily
  purchases.splice(index, 1);
  editPurchaseIndex = index;

  saveData();
  updateStockTable();
  clearPurchaseTable();
}

// ---- DATE FILTER ----
function filterPurchases() {
  const from = document.getElementById("filterFromDate").value;
  const to = document.getElementById("filterToDate").value;

  if (!from || !to) {
    alert("Please select both dates");
    return;
  }

  const filtered = purchases.filter(p =>
    p.date >= from && p.date <= to
  );

  if (filtered.length === 0) {
    alert("No purchases in this date range");
    clearPurchaseTable();
    return;
  }

  renderPurchaseTable(filtered);
}
// ---- CLEAR FILTER ----
function clearPurchaseFilter() {
  document.getElementById("filterFromDate").value = "";
  document.getElementById("filterToDate").value = "";
  clearPurchaseTable();
}

// ---- SEARCH ----
function searchPurchase() {
  const input = document.getElementById("searchPurchase");
  if (!input) return;

  const keyword = input.value.trim().toLowerCase();

  if (keyword === "") {
    clearPurchaseTable(); // show message: select date range
    return;
  }

  const result = purchases.filter(p =>
    p.name.toLowerCase().includes(keyword) ||
    p.pid.toString().includes(keyword)
  );

  if (result.length === 0) {
    alert("No matching purchase found");
    return;
  }

  renderPurchaseTable(result);
}

// ---- TABLE RENDER ----
function renderPurchaseTable(data=[]) {
  const tbody = document.querySelector("#purchaseTable tbody");
  tbody.innerHTML = "";

  if (data.length === 0) {
    tbody.innerHTML =
      `<tr><td colspan="7" style="text-align:center">No records found</td></tr>`;
    return;
  }

  data.forEach((p, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${p.date}</td>
      <td>${p.pid}</td>
      <td>${p.name}</td>
      <td>${p.qty}</td>
      <td>${p.rate}</td>
      <td>${p.sell}</td>
      <td>${p.total.toFixed(2)}</td>
      <td>
        <button onclick="editPurchase(${index})">Edit</button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// ---- EMPTY TABLE MESSAGE ----
function clearPurchaseTable() {
  const tbody = document.querySelector("#purchaseTable tbody");
  tbody.innerHTML =
    `<tr><td colspan="8" style="text-align:center">
      Select date range to view purchase history
    </td></tr>`;
}
/*********************************
 BILLING MANAGEMENT
**********************************/
function addBillingRow() {
  const tbody = document.querySelector("#billingTable tbody");
  const row = document.createElement("tr");

  row.innerHTML = `
    <td><input list="productList" class="bill-product" oninput="onProductSelect(this)"></td>
    <td><input type="number" class="bill-qty" min="1" oninput="calculateRow(this)"></td>
    <td class="bill-price">0</td>
    <td class="bill-total">0</td>
    <td><button onclick="removeBillingRow(this)">Delete</button></td>
  `;
  tbody.appendChild(row);
}

function removeBillingRow(btn) {
  btn.closest("tr").remove();
  calculateFinalTotal();
}

function onProductSelect(input) {
  const row = input.closest("tr");
  const product = products.find(p => p.name === input.value);
  if (!product) return;

  row.querySelector(".bill-price").innerText = product.sellingPrice;
  calculateRow(input);
}

function calculateRow(input) {
  const row = input.closest("tr");
  const qty = Number(row.querySelector(".bill-qty").value);
  const price = Number(row.querySelector(".bill-price").innerText);
  const name = row.querySelector(".bill-product").value;

  const product = products.find(p => p.name === name);
  if (!product) return;

  if (qty > product.currentStock) {
    alert(`Only ${product.currentStock} available`);
    row.querySelector(".bill-qty").value = product.currentStock;
    return;
  }

  row.querySelector(".bill-total").innerText = (qty * price).toFixed(2);
  calculateFinalTotal();
}

function calculateFinalTotal() {
  let total = 0;
  document.querySelectorAll(".bill-total").forEach(td => {
    total += Number(td.innerText);
  });
  document.getElementById("finalTotal").innerText = total.toFixed(2);
}

function generateBill() {
  const paymentMode=document.querySelector('input[name="paymentMode"]:checked')?.value|| "Cash";

  let billItems = [];

  document.querySelectorAll("#billingTable tbody tr").forEach(row => {
    const name = row.querySelector(".bill-product").value;
    const qty = Number(row.querySelector(".bill-qty").value);
    const price = Number(row.querySelector(".bill-price").innerText);
    const product = products.find(p => p.name === name);

    if (!product || qty <= 0) return;

    product.totalSold += qty;
    product.currentStock -= qty;
    const billItems=[];
    billItems.push({
      name,
      qty,
      price,
      costAtSale: product.avgCost,
      total: qty * price
    });
  });

  if (billItems.length === 0) {
    alert("No items added");
    return;
  }

  sales.push({
    date: new Date().toLocaleDateString(),
    items: billItems,
    paymentMode:paymentMode
  });

  saveData();
  alert("Bill generated successfully");
  document.querySelector("#billingTable tbody").innerHTML = "";
  document.getElementById("finalTotal").innerText = "0";
  updateStockTable();
  
  
}
function showBillDetails(billData) {
  showSection("billDetailsSection");

  document.getElementById("billNo").innerText = billData.billNo;
  document.getElementById("billDate").innerText = billData.date;
  document.getElementById("billCustomer").innerText =
    billData.customer || "Walk-in Customer";

  const tbody = document.getElementById("billItems");
  tbody.innerHTML = "";

  let total = 0;

  billData.items.forEach((item, index) => {
    total += item.total;
    const row = `
      <tr>
        <td>${index + 1}</td>
        <td>${item.name}</td>
        <td>${item.qty}</td>
        <td>${item.price}</td>
        <td>${item.total}</td>
      </tr>
    `;
    tbody.innerHTML += row;
  });

  document.getElementById("billFinalTotal").innerText = total;
}

function printBill() {
  window.print();
}

function closeBill() {
  showSection("billingSection");
}
showBillDetails({
  billNo: "BILL-" + Date.now(),
  date: new Date().toLocaleString(),
  customer: document.getElementById("custName").value,
  items: billItems
});

/*********************************
 RECENT TRANSACTIONS (DASHBOARD)
**********************************/
function renderRecentTransactions() {
  const tbody = document.querySelector("#recentTransactionsTable tbody");
  if (!tbody) return;

  tbody.innerHTML = "";

  sales.slice(-5).reverse().forEach((sale, index) => {
    const amount = sale.items.reduce((sum, i) => sum + i.total, 0);
    const row = document.createElement("tr");

    row.innerHTML = `
      <td>BILL-${sales.length - index}</td>
      <td>${sale.date}</td>
      <td>₹${amount.toFixed(2)}</td>
    `;
    tbody.appendChild(row);
  });
}

/* ========================== 
STOCK MANAGEMENT 
========================== */ 
function updateStockTable() { 
const tbody = document.querySelector("#stockTable tbody"); 
tbody.innerHTML = ""; 
let totalPurchased = 0; 
let totalSold = 0; 
// average sold for fast / slow logic 
const totalSoldQty = products.reduce((sum, p) => sum + p.totalSold, 0); 
const avgSold = products.length ? totalSoldQty / products.length : 0; 
products.forEach(p => { 
const currentStock = p.totalPurchased - p.totalSold; 
    totalPurchased += p.totalPurchased; 
    totalSold += p.totalSold; 
 
    const row = document.createElement("tr"); 
 
    // stock status 
    let statusText = "Normal"; 
    let statusClass = ""; 
 
    if (currentStock === 0) { 
      statusText = "Out of Stock"; 
      statusClass = "out-stock"; 
    } else if (currentStock <= 10) { 
      statusText = "Low Stock"; 
      statusClass = "low-stock"; 
    } else if (p.totalSold >= avgSold) { 
      statusText = "Fast Moving"; 
      statusClass = "fast"; 
    } else { 
      statusText = "Slow Moving"; 
      statusClass = "slow"; 
    } 
 
    row.className = statusClass; 
    row.setAttribute("data-status", statusClass); 
 
    row.innerHTML = ` 
      <td>${p.name}</td> 
      <td>${p.totalPurchased}</td> 
      <td>${p.totalSold}</td> 
      <td>${currentStock}</td> 
      <td>${statusText}</td> 
    `; 
 
    tbody.appendChild(row); 
  }); 
 
  document.getElementById("totalProducts").innerText = products.length; 
  document.getElementById("totalPurchased").innerText = totalPurchased; 
  document.getElementById("totalSold").innerText = totalSold; 
  document.getElementById("currentStock").innerText = totalPurchased - totalSold; 
  function getSalesInRange(from, to) {
  return sales.filter(s =>
    s.date >= from && s.date <= to
  );
}
function calculateFastSlow(from, to) {
  const rangeSales = getSalesInRange(from, to);

  const soldMap = {};
  rangeSales.forEach(s => {
    soldMap[s.product] = (soldMap[s.product] || 0) + s.qty;
  });

  const avg =
    Object.values(soldMap).reduce((a,b)=>a+b,0) /
    Object.keys(soldMap).length || 0;

  products.forEach(p => {
    p.rangeSold = soldMap[p.name] || 0;
    p.rangeStatus = p.rangeSold >= avg ? "fast" : "slow";
  });
}
row.setAttribute("data-status", p.rangeStatus);
} 
 
/* Search */ 
function filterStockTable() { 
  const filter = document.getElementById("stockSearch").value.toLowerCase(); 
  const rows = document.querySelectorAll("#stockTable tbody tr"); 
 
  rows.forEach(row => { 
    const productName = row.cells[0].innerText.toLowerCase(); 
    row.style.display = productName.includes(filter) ? "" : "none"; 
  }); 
} 
 
/* Dropdown Filter */ 
function applyStockFilter() { 
  const filter = document.getElementById("stockFilter").value; 
  const rows = document.querySelectorAll("#stockTable tbody tr"); 
 
  rows.forEach(row => { 
    const status = row.getAttribute("data-status"); 
 
    if (filter === "all") { 
      row.style.display = ""; 
    } else if (filter === "low" && status === "low-stock") { 
      row.style.display = ""; 
    } else if (filter === "out" && status === "out-stock") { 
      row.style.display = ""; 
    } else if (filter === "fast" && status === "fast") { 
      row.style.display = ""; 
    } else if (filter === "slow" && status === "slow") { 
      row.style.display = ""; 
    } else { 
      row.style.display = "none"; 
    } 
  }); 
}

function filterStockByDate() {
  const from = document.getElementById("stockFromDate").value;
  const to = document.getElementById("stockToDate").value;

  if (!from || !to) {
    alert("Please select both dates");
    return;
  }

  const rows = document.querySelectorAll("#stockTable tbody tr");

  rows.forEach(row => {
    const productName = row.cells[0].innerText;
    const product = products.find(p => p.name === productName);

    // If product has no activity date → hide
    if (!product || !product.lastUpdated) {
      row.style.display = "none";
      return;
    }

    row.style.display =
      product.lastUpdated >= from && product.lastUpdated <= to
        ? ""
        : "none";
  });
}

function clearStockDateFilter() {
  document.getElementById("stockFromDate").value = "";
  document.getElementById("stockToDate").value = "";
  updateStockTable();
}
/*********************************
 PROFIT & LOSS REPORT
**********************************/
function generateReport() {
  let revenue = 0;
  let cost = 0;

  sales.forEach(sale => {
    sale.items.forEach(item => {
      revenue += item.total;
      cost += item.qty * item.costAtSale;
    });
  });

  document.getElementById("reportSales").innerText = revenue.toFixed(2);
  document.getElementById("reportPurchase").innerText = cost.toFixed(2);

  const profit = revenue - cost;
  const profitElem = document.getElementById("reportProfit");
  profitElem.innerText = profit.toFixed(2);
  profitElem.style.color = profit >= 0 ? "green" : "red";
}

/*********************************
 PRODUCT SEARCH LIST
**********************************/
function populateProductDatalist() {
  const datalist = document.getElementById("productList");
  datalist.innerHTML = products.map(p => `<option value="${p.name}">`).join("");
}
// ===============================
// PURCHASE FORM AJAX SUBMIT
// ===============================
document.getElementById("purchaseForm").addEventListener("submit", function (e) {
  e.preventDefault(); // stop page reload

  const formData = new FormData(this);

  fetch("purchase_add.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(response => {
    if (response.trim() === "success") {
      alert("Purchase added successfully");

      this.reset();               // clear form
      fetchPurchaseHistory();     // refresh purchase table
      fetchStockTable();          // refresh stock table
    } else {
      alert("Error adding purchase");
      console.error(response);
    }
  })
  .catch(error => {
    console.error("AJAX Error:", error);
  });
});
