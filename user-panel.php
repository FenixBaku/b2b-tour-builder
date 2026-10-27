<?php
require_once __DIR__ . '/auth/_auth_helper.php';
auth_require_login('/bb/auth/login.php?redirect=' . urlencode('/bb/user-panel.php'));

$user = auth_get_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders | B2B Tour Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Montserrat', sans-serif; }
        .order-card { transition: all 0.2s; }
        .order-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,0.08); }
    </style>
    <!-- Axtarışlı select-lər (assets/searchable-select.js) -->
    <link rel="stylesheet" href="/bb/assets/searchable-select.css?v=<?php echo @filemtime(__DIR__ . '/assets/searchable-select.css'); ?>" />
    <script src="/bb/assets/searchable-select.js?v=<?php echo @filemtime(__DIR__ . '/assets/searchable-select.js'); ?>" defer></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
    <header class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="tour-builder.php" class="flex items-center gap-2 text-slate-700 hover:text-blue-600">
                    <span class="material-symbols-outlined">travel_explore</span>
                    <span class="font-bold">B2B Tour Builder</span>
                </a>
                <span class="text-slate-400">|</span>
                <h1 class="text-lg font-semibold text-slate-800">My Orders</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-600"><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
                <a href="auth/logout.php" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 bg-white text-sm font-semibold text-red-600 hover:bg-red-50 hover:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2" aria-label="Log out">
                    <span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">logout</span>
                    Log out
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 flex gap-6">
        <aside class="w-64 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 h-fit">
            <nav class="space-y-1">
                <a href="#" data-section-link="dashboard" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-50 text-blue-700 font-semibold">
                    <span class="material-symbols-outlined" style="font-size:18px;">dashboard</span>
                    <span>Dashboard</span>
                </a>
                <a href="#" data-section-link="invoice" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    <span class="material-symbols-outlined" style="font-size:18px;">receipt_long</span>
                    <span>Invoice</span>
                </a>
                <a href="#" data-section-link="debts" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    <span class="material-symbols-outlined" style="font-size:18px;">account_balance_wallet</span>
                    <span>Debts</span>
                </a>
            </nav>
        </aside>

        <section class="flex-1">
            <!-- Dashboard section -->
            <div id="panel-dashboard" data-panel-section="dashboard">
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-slate-600">Here you can see the orders you have saved from the Tour Builder.</p>
                    <a href="tour-builder.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                        <span class="material-symbols-outlined" style="font-size:18px;">add</span>
                        Create new tour
                    </a>
                </div>

                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-3">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" style="font-size:18px;">search</span>
                            <input
                                id="filter-search"
                                type="text"
                                class="pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[220px]"
                                placeholder="Search by order name or hotel"
                            />
                        </div>

                        <select
                            id="filter-status"
                            class="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-xs text-slate-500">Price:</span>
                            <input
                                id="filter-price-min"
                                type="number"
                                step="0.01"
                                class="w-24 px-2 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="min"
                            />
                            <span class="text-xs text-slate-400">–</span>
                            <input
                                id="filter-price-max"
                                type="number"
                                step="0.01"
                                class="w-24 px-2 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="max"
                            />
                        </div>

                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-xs text-slate-500">Date:</span>
                            <input
                                id="filter-date-from"
                                type="date"
                                class="px-2 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <span class="text-xs text-slate-400">–</span>
                            <input
                                id="filter-date-to"
                                type="date"
                                class="px-2 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <div id="orders-container" class="space-y-4">
                    <div id="loading" class="text-center py-12 text-slate-500">Loading...</div>
                    <div id="orders-list" class="hidden"></div>
                    <div id="empty-state" class="hidden text-center py-16 bg-white rounded-2xl border border-slate-200">
                        <span class="material-symbols-outlined text-6xl text-slate-300">inbox</span>
                        <p class="mt-4 text-slate-600">No orders yet.</p>
                        <a href="tour-builder.php" class="inline-block mt-4 text-blue-600 font-medium hover:underline">Go to Tour Builder →</a>
                    </div>
                </div>
            </div>

            <!-- Invoice section -->
            <div id="panel-invoice" data-panel-section="invoice" class="hidden">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-800 mb-1">Invoice</h2>
                    <p class="text-sm text-slate-600">Here you can upload payment receipts (PDF, JPG, PNG) for confirmed orders and track their status.</p>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <span class="text-sm font-medium text-slate-700">Upload new receipt</span>
                        </div>
                        <form id="invoice-upload-form" class="p-4 space-y-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Order</label>
                                <select id="invoice-order-id" name="order_id" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select order</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Amount (optional)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    id="invoice-amount"
                                    name="amount"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="If left empty, the order amount will be used"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Receipt file (PDF, JPG, PNG)</label>
                                <input
                                    type="file"
                                    id="invoice-file"
                                    name="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                />
                            </div>
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700"
                            >
                                <span class="material-symbols-outlined" style="font-size:18px;">upload</span>
                                Upload receipt
                            </button>
                            <p id="invoice-upload-message" class="text-xs text-slate-500"></p>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-700">Receipt list</span>
                            <span class="text-xs text-slate-400">Status: Pending / Approved / Rejected</span>
                        </div>
                        <div id="invoice-list-container" class="p-4 text-sm">
                            <div id="invoice-loading" class="text-slate-500">Loading...</div>
                            <div id="invoice-list" class="hidden space-y-3"></div>
                            <div id="invoice-empty" class="hidden text-slate-500 text-sm">You have not uploaded any receipts yet.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debts section -->
            <div id="panel-debts" data-panel-section="debts" class="hidden">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-800 mb-1">Debts</h2>
                    <p class="text-sm text-slate-600">In this section you can track your debt for confirmed orders and the status of payments.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-3 mb-6">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500 mb-1">Total debt (confirmed orders)</p>
                        <p id="debts-total" class="text-2xl font-bold text-red-500">$0.00</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500 mb-1">Pending payments (awaiting receipt approval)</p>
                        <p id="debts-pending" class="text-2xl font-bold text-amber-500">$0.00</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                        <p class="text-xs text-slate-500 mb-1">Paid amount (approved by admin)</p>
                        <p id="debts-paid" class="text-2xl font-bold text-emerald-500">$0.00</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-700">Debt activity</span>
                        <span class="text-xs text-slate-400">Total amount and payments for each order</span>
                    </div>
                    <div id="debts-list-container" class="p-4 text-sm">
                        <div id="debts-loading" class="text-slate-500">Loading...</div>
                        <div id="debts-list" class="hidden divide-y divide-slate-100"></div>
                        <div id="debts-empty" class="hidden text-slate-500 text-sm">There are no confirmed orders.</div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        (function() {
            // Tab naviqasiyası (Dashboard / Invoice / Debts)
            const sectionWrappers = document.querySelectorAll('[data-panel-section]');
            const navLinks = document.querySelectorAll('[data-section-link]');

            function setActiveSection(name) {
                sectionWrappers.forEach(function (el) {
                    const key = el.getAttribute('data-panel-section');
                    el.classList.toggle('hidden', key !== name);
                });

                navLinks.forEach(function (link) {
                    const key = link.getAttribute('data-section-link');
                    const isActive = key === name;
                    link.classList.toggle('bg-blue-50', isActive);
                    link.classList.toggle('text-blue-700', isActive);
                    link.classList.toggle('font-semibold', isActive);
                    link.classList.toggle('text-slate-600', !isActive);
                });
            }

            navLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = this.getAttribute('data-section-link');
                    if (target) setActiveSection(target);
                });
            });

            // İlkin aktiv bölmə
            setActiveSection('dashboard');

            const container = document.getElementById('orders-list');
            const loading = document.getElementById('loading');
            const emptyState = document.getElementById('empty-state');
            const searchInput = document.getElementById('filter-search');
            const statusSelect = document.getElementById('filter-status');
            const priceMinInput = document.getElementById('filter-price-min');
            const priceMaxInput = document.getElementById('filter-price-max');
            const dateFromInput = document.getElementById('filter-date-from');
            const dateToInput = document.getElementById('filter-date-to');

            // Invoice elementləri
            const invoiceOrderSelect = document.getElementById('invoice-order-id');
            const invoiceAmountInput = document.getElementById('invoice-amount');
            const invoiceFileInput = document.getElementById('invoice-file');
            const invoiceUploadForm = document.getElementById('invoice-upload-form');
            const invoiceUploadMessage = document.getElementById('invoice-upload-message');
            const invoiceLoading = document.getElementById('invoice-loading');
            const invoiceListEl = document.getElementById('invoice-list');
            const invoiceEmptyEl = document.getElementById('invoice-empty');

            // Debts elementləri
            const debtsTotalEl = document.getElementById('debts-total');
            const debtsPendingEl = document.getElementById('debts-pending');
            const debtsPaidEl = document.getElementById('debts-paid');
            const debtsLoadingEl = document.getElementById('debts-loading');
            const debtsListEl = document.getElementById('debts-list');
            const debtsEmptyEl = document.getElementById('debts-empty');

            let allOrders = [];

            function formatDate(s) {
                if (!s) return '–';
                const d = new Date(s);
                return isNaN(d.getTime()) ? s : d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
            }

            function formatOrderSummary(data) {
                if (!data || !data.days) return '';
                const b = data.basics || {};
                const parts = [];
                if (b.checkin) parts.push('Check-in: ' + b.checkin);
                if (b.checkout) parts.push('Check-out: ' + b.checkout);
                if (b.adults) parts.push(b.adults + ' adults');
                if (b.children) parts.push(b.children + ' children');
                const hotels = [];
                (data.days || []).forEach(function(d) {
                    if (d.hotel && hotels.indexOf(d.hotel) < 0) hotels.push(d.hotel);
                });
                if (hotels.length) parts.push('Hotels: ' + hotels.slice(0, 3).join(', ') + (hotels.length > 3 ? '...' : ''));
                return parts.join(' • ');
            }

            function formatOrderStatus(status) {
                status = Number(status || 0);
                if (status === 1) return { label: 'Confirmed', class: 'bg-emerald-100 text-emerald-700' };
                if (status === 2) return { label: 'Rejected', class: 'bg-red-100 text-red-700' };
                return { label: 'Pending', class: 'bg-amber-100 text-amber-700' };
            }

            function getStatusKey(status) {
                status = Number(status || 0);
                if (status === 1) return 'confirmed';
                if (status === 2) return 'rejected';
                return 'pending';
            }

            function formatMoney(v) {
                if (v == null || isNaN(v)) return '$0.00';
                return '$' + Number(v).toFixed(2);
            }

            function renderOrders(list) {
                if (!list || list.length === 0) {
                    container.innerHTML = '<div class="p-6 bg-slate-50 text-slate-500 rounded-xl">No orders match the current filters.</div>';
                    return;
                }

                container.innerHTML = list.map(function(o) {
                    const summary = formatOrderSummary(o.order_data);
                    const total = o.total_amount != null ? '$' + Number(o.total_amount).toFixed(2) : '–';
                    const st = formatOrderStatus(o.status);
                    return '<div class="order-card bg-white rounded-xl border border-slate-200 p-6 shadow-sm">' +
                        '<div class="flex flex-wrap items-start justify-between gap-4">' +
                            '<div class="flex-1 min-w-0">' +
                                '<h3 class="font-bold text-slate-800 text-lg">' + (o.name || 'Order #' + o.id) + '</h3>' +
                                '<p class="text-sm text-slate-500 mt-1">' + formatDate(o.created_at) + '</p>' +
                                (summary ? '<p class="text-sm text-slate-600 mt-2">' + summary + '</p>' : '') +
                            '</div>' +
                            '<div class="text-right space-y-2">' +
                                '<div><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' + st.class + '">' + st.label + '</span></div>' +
                                '<div><span class="text-xl font-bold text-blue-600">' + total + '</span></div>' +
                            '</div>' +
                        '</div>' +
                        '<div class="mt-4 pt-4 border-t border-slate-100 flex gap-3">' +
                        '<a href="tour-builder.php?load=' + o.id + '" class="text-sm text-blue-600 hover:underline font-medium">Open in Tour Builder</a>' +
                        '</div>' +
                    '</div>';
                }).join('');
            }

            function populateInvoiceOrders() {
                if (!invoiceOrderSelect) return;
                // Yalnız təsdiqlənmiş sifarişləri (status = 1) göstərək
                const confirmed = allOrders.filter(function (o) {
                    return Number(o.status || 0) === 1;
                });
                const current = invoiceOrderSelect.value;
                invoiceOrderSelect.innerHTML = '<option value="">Select order</option>' +
                    confirmed.map(function (o) {
                        const name = o.name || ('Order #' + o.id);
                        return '<option value="' + o.id + '">' + name + ' – ' + formatMoney(o.total_amount) + '</option>';
                    }).join('');
                if (current && invoiceOrderSelect.querySelector('option[value="' + current + '"]')) {
                    invoiceOrderSelect.value = current;
                }
            }

            function applyFilters() {
                if (!allOrders || allOrders.length === 0) return;

                const q = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
                const status = (statusSelect && statusSelect.value) || '';
                const minPrice = (priceMinInput && priceMinInput.value !== '') ? parseFloat(priceMinInput.value) : null;
                const maxPrice = (priceMaxInput && priceMaxInput.value !== '') ? parseFloat(priceMaxInput.value) : null;
                        const dateFrom = (dateFromInput && dateFromInput.value) ? new Date(dateFromInput.value) : null;
                const dateTo = (dateToInput && dateToInput.value) ? new Date(dateToInput.value) : null;

                const filtered = allOrders.filter(function(o) {
                    if (status) {
                        const key = getStatusKey(o.status);
                        if (key !== status) return false;
                    }

                    if (minPrice !== null || maxPrice !== null) {
                        const amt = o.total_amount != null ? Number(o.total_amount) : NaN;
                        if (isNaN(amt)) return false;
                        if (minPrice !== null && amt < minPrice) return false;
                        if (maxPrice !== null && amt > maxPrice) return false;
                    }

                    if (dateFrom || dateTo) {
                        if (!o.created_at) return false;
                        const created = new Date(o.created_at);
                        if (isNaN(created.getTime())) return false;
                        if (dateFrom && created < dateFrom) return false;
                        if (dateTo) {
                            const end = new Date(dateTo);
                            end.setHours(23, 59, 59, 999);
                            if (created > end) return false;
                        }
                    }

                    if (q) {
                        const name = (o.name || ('Order #' + o.id)).toLowerCase();
                        const summary = (formatOrderSummary(o.order_data) || '').toLowerCase();
                        if (name.indexOf(q) === -1 && summary.indexOf(q) === -1) {
                            return false;
                        }
                    }

                    return true;
                });

                renderOrders(filtered);
            }

            if (searchInput) {
                searchInput.addEventListener('input', applyFilters);
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', applyFilters);
            }

            if (priceMinInput) {
                priceMinInput.addEventListener('input', applyFilters);
            }

            if (priceMaxInput) {
                priceMaxInput.addEventListener('input', applyFilters);
            }

            if (dateFromInput) {
                dateFromInput.addEventListener('change', applyFilters);
            }

            if (dateToInput) {
                dateToInput.addEventListener('change', applyFilters);
            }

            function renderInvoices(list) {
                if (!invoiceListEl || !invoiceLoading || !invoiceEmptyEl) return;

                invoiceLoading.classList.add('hidden');
                if (!list || !list.length) {
                    invoiceListEl.classList.add('hidden');
                    invoiceEmptyEl.classList.remove('hidden');
                    return;
                }

                invoiceEmptyEl.classList.add('hidden');
                invoiceListEl.classList.remove('hidden');

                invoiceListEl.innerHTML = list.map(function (r) {
                    var statusLabel = 'Pending';
                    var statusClass = 'bg-amber-100 text-amber-700';
                    if (Number(r.status) === 1) {
                        statusLabel = 'Approved';
                        statusClass = 'bg-emerald-100 text-emerald-700';
                    } else if (Number(r.status) === 2) {
                        statusLabel = 'Rejected';
                        statusClass = 'bg-red-100 text-red-700';
                    }
                    var orderName = r.order_name || ('Order #' + r.order_id);
                    var fileLabel;
                    if (r.file_type === 'pdf') {
                        fileLabel = 'PDF document';
                    } else if (r.file_type === 'image') {
                        fileLabel = 'Image file';
                    } else {
                        // Admin tərəfindən manual daxil edilmiş ödəniş (file_path yoxdur)
                        fileLabel = 'Confirmed payment recorded by admin';
                    }
                    var created = formatDate(r.created_at);
                    return (
                        '<div class="flex items-start justify-between gap-3">' +
                            '<div class="min-w-0">' +
                                '<p class="text-sm font-medium text-slate-800">' + orderName + '</p>' +
                                '<p class="text-xs text-slate-500 mt-0.5">' + created + '</p>' +
                                '<p class="text-xs text-slate-500 mt-1">' + fileLabel + ' • ' + formatMoney(r.amount) + '</p>' +
                                (r.order_total != null ? '<p class="text-[11px] text-slate-400 mt-0.5">Order amount: ' + formatMoney(r.order_total) + '</p>' : '') +
                                (r.file_path ? '<a href="' + r.file_path + '" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline mt-1">Open file</a>' : '') +
                            '</div>' +
                            '<div class="flex flex-col items-end gap-1">' +
                                '<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold ' + statusClass + '">' + statusLabel + '</span>' +
                            '</div>' +
                        '</div>'
                    );
                }).join('');
            }

            function loadInvoices() {
                if (!invoiceLoading) return;
                invoiceLoading.classList.remove('hidden');
                if (invoiceEmptyEl) invoiceEmptyEl.classList.add('hidden');
                if (invoiceListEl) invoiceListEl.classList.add('hidden');

                fetch('api/user-invoices.php')
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.ok) {
                            invoiceLoading.textContent = res.error || 'An error occurred.';
                            return;
                        }
                        renderInvoices(res.receipts || []);
                    })
                    .catch(function () {
                        invoiceLoading.textContent = 'Loading error';
                    });
            }

            function renderDebts(data) {
                if (!debtsLoadingEl || !debtsListEl || !debtsEmptyEl) return;

                debtsLoadingEl.classList.add('hidden');

                var summary = data.summary || {};
                if (debtsTotalEl) debtsTotalEl.textContent = formatMoney(summary.total_debt || 0);
                if (debtsPendingEl) debtsPendingEl.textContent = formatMoney(summary.pending_payments || 0);
                if (debtsPaidEl) debtsPaidEl.textContent = formatMoney(summary.paid_total || 0);

                var list = data.debts || [];
                if (!list.length) {
                    debtsListEl.classList.add('hidden');
                    debtsEmptyEl.classList.remove('hidden');
                    return;
                }

                debtsEmptyEl.classList.add('hidden');
                debtsListEl.classList.remove('hidden');

                debtsListEl.innerHTML = list.map(function (d) {
                    var name = d.name || ('Order #' + d.order_id);
                    var created = formatDate(d.created_at);
                    return (
                        '<div class="py-3 flex items-start justify-between gap-3">' +
                            '<div class="min-w-0">' +
                                '<p class="text-sm font-medium text-slate-800">' + name + '</p>' +
                                '<p class="text-xs text-slate-500 mt-0.5">' + created + '</p>' +
                            '</div>' +
                            '<div class="flex flex-col items-end gap-1 text-xs">' +
                                '<div class="flex items-center gap-1"><span class="text-slate-500">Debt:</span><span class="font-semibold text-slate-800">' + formatMoney(d.total_amount) + '</span></div>' +
                                '<div class="flex items-center gap-1"><span class="text-slate-500">Pending:</span><span class="font-semibold text-amber-600">' + formatMoney(d.pending_amount) + '</span></div>' +
                                '<div class="flex items-center gap-1"><span class="text-slate-500">Paid:</span><span class="font-semibold text-emerald-600">' + formatMoney(d.paid_amount) + '</span></div>' +
                            '</div>' +
                        '</div>'
                    );
                }).join('');
            }

            function loadDebts() {
                if (!debtsLoadingEl) return;
                debtsLoadingEl.classList.remove('hidden');
                if (debtsEmptyEl) debtsEmptyEl.classList.add('hidden');
                if (debtsListEl) debtsListEl.classList.add('hidden');

                fetch('api/user-debts.php')
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (!res.ok) {
                            debtsLoadingEl.textContent = res.error || 'An error occurred.';
                            return;
                        }
                        renderDebts(res);
                    })
                    .catch(function () {
                        debtsLoadingEl.textContent = 'Loading error';
                    });
            }

            if (invoiceUploadForm) {
                invoiceUploadForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!invoiceOrderSelect || !invoiceFileInput) return;

                    var orderId = invoiceOrderSelect.value;
                    if (!orderId) {
                        if (invoiceUploadMessage) {
                        invoiceUploadMessage.textContent = 'Please select an order.';
                            invoiceUploadMessage.classList.remove('text-emerald-600');
                            invoiceUploadMessage.classList.add('text-red-600');
                        }
                        return;
                    }

                    if (!invoiceFileInput.files || !invoiceFileInput.files.length) {
                        if (invoiceUploadMessage) {
                        invoiceUploadMessage.textContent = 'Please choose a receipt file.';
                            invoiceUploadMessage.classList.remove('text-emerald-600');
                            invoiceUploadMessage.classList.add('text-red-600');
                        }
                        return;
                    }

                    var formData = new FormData();
                    formData.append('order_id', orderId);
                    if (invoiceAmountInput && invoiceAmountInput.value) {
                        formData.append('amount', invoiceAmountInput.value);
                    }
                    formData.append('file', invoiceFileInput.files[0]);

                        if (invoiceUploadMessage) {
                            invoiceUploadMessage.textContent = 'Uploading...';
                            invoiceUploadMessage.classList.remove('text-red-600');
                            invoiceUploadMessage.classList.remove('text-emerald-600');
                            invoiceUploadMessage.classList.add('text-slate-500');
                        }

                    fetch('api/user-invoices.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (!invoiceUploadMessage) return;
                            if (!res.ok) {
                                invoiceUploadMessage.textContent = res.error || 'An error occurred during upload.';
                                invoiceUploadMessage.classList.remove('text-emerald-600');
                                invoiceUploadMessage.classList.add('text-red-600');
                                return;
                            }
                            invoiceUploadMessage.textContent = 'Receipt uploaded successfully and is awaiting admin approval.';
                            invoiceUploadMessage.classList.remove('text-red-600');
                            invoiceUploadMessage.classList.remove('text-slate-500');
                            invoiceUploadMessage.classList.add('text-emerald-600');

                            if (invoiceAmountInput) invoiceAmountInput.value = '';
                            if (invoiceFileInput) invoiceFileInput.value = '';

                            loadInvoices();
                            loadDebts();
                        })
                        .catch(function () {
                            if (!invoiceUploadMessage) return;
                            invoiceUploadMessage.textContent = 'An error occurred during upload.';
                            invoiceUploadMessage.classList.remove('text-emerald-600');
                            invoiceUploadMessage.classList.add('text-red-600');
                        });
                });
            }

            // İlk yükləmədə həm sifarişləri, həm də borc/invoice məlumatlarını çəkirik
            fetch('api/user-orders.php')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    loading.classList.add('hidden');
                    if (!res.ok) {
                        container.classList.remove('hidden');
                        container.innerHTML = '<div class="p-6 bg-red-50 text-red-700 rounded-xl">' + (res.error || 'Error') + '</div>';
                        return;
                    }
                    allOrders = res.orders || [];
                    if (!allOrders.length) {
                        emptyState.classList.remove('hidden');
                        return;
                    }
                    container.classList.remove('hidden');
                    renderOrders(allOrders);
                    populateInvoiceOrders();
                })
                .catch(function() {
                    loading.classList.add('hidden');
                    container.classList.remove('hidden');
                    container.innerHTML = '<div class="p-6 bg-red-50 text-red-700 rounded-xl">Loading error</div>';
                });

            loadInvoices();
            loadDebts();
        })();
    </script>
</body>
</html>
