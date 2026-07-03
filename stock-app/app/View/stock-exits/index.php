<?php

$title = 'Sortie de stock';
?>
<h1 class="h4 mb-3">Sortie de stock</h1>
<meta name="csrf-token" content="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

<div class="row g-3">
    <!-- Panneau gauche : scan / recherche -->
    <div class="col-md-5 col-lg-4">
        <div class="card card-body">
            <h2 class="h6">Identifier le produit</h2>

            <button type="button" id="start-scan" class="btn btn-primary mb-2">
                📷 Scanner le code-barres
            </button>
            <div class="scanner-wrapper d-none mb-2">
                <video id="scanner-video" class="w-100 rounded bg-dark"
                       autoplay muted playsinline style="max-height:220px;"></video>
            </div>

            <div class="input-group mt-1">
                <input type="text" id="manual-barcode" class="form-control"
                       placeholder="Saisie manuelle">
                <button type="button" id="manual-lookup" class="btn btn-outline-secondary">Chercher</button>
            </div>
        </div>
    </div>

    <!-- Panneau droit : fiche produit + sélection de l'entrée -->
    <div class="col-md-7 col-lg-8">
        <div id="no-product" class="text-muted pt-3">
            Scannez ou saisissez un code-barres pour afficher les entrées disponibles.
        </div>

        <div id="product-panel" class="d-none">
            <!-- En-tête produit -->
            <div class="card mb-3">
                <div class="card-body d-flex gap-3 align-items-center">
                    <div id="product-photo-wrap" class="d-none">
                        <img id="product-photo" src="" alt="" class="rounded" style="width:60px;height:60px;object-fit:cover;">
                    </div>
                    <div>
                        <h2 class="h5 mb-1" id="product-name"></h2>
                        <p class="mb-0 text-muted small" id="product-meta"></p>
                    </div>
                    <div class="ms-auto text-end">
                        <span class="badge bg-info fs-6" id="product-total-stock"></span>
                        <div class="small text-muted">en stock</div>
                    </div>
                </div>
            </div>

            <!-- Alerte FEFO (non forcée, informative) -->
            <div id="fefo-warning" class="alert alert-warning d-none">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span id="fefo-warning-text"></span>
            </div>

            <!-- Tableau des entrées disponibles -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Entrées disponibles — choisissez celle physiquement prélevée</span>
                    <span class="badge bg-secondary" id="entries-count"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Date d'entrée</th>
                                <th>DDM / DLC</th>
                                <th>Type</th>
                                <th>Restant</th>
                                <th>Origine</th>
                            </tr>
                        </thead>
                        <tbody id="entries-tbody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Saisie quantité + confirmation -->
            <div class="card card-body" id="exit-form-panel">
                <div class="row align-items-end g-2">
                    <div class="col-auto">
                        <label class="form-label">Quantité retirée</label>
                        <input type="number" id="exit-quantity" class="form-control"
                               style="width:100px;" min="1" value="1">
                    </div>
                    <div class="col">
                        <label class="form-label">Commentaire (optionnel)</label>
                        <input type="text" id="exit-comment" class="form-control" placeholder="…">
                    </div>
                    <div class="col-auto">
                        <button id="confirm-exit" class="btn btn-danger" disabled>
                            ✓ Confirmer la sortie
                        </button>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0" id="selected-entry-info"></p>
            </div>
        </div>
    </div>
</div>

<!-- Toast de confirmation -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="exit-toast" class="toast align-items-center text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toast-body">Sortie enregistrée.</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script type="module">
import { BarcodeScanner } from '/assets/js/modules/scanner.js';

const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;
const productPanel  = document.getElementById('product-panel');
const noProduct     = document.getElementById('no-product');
const entriesTbody  = document.getElementById('entries-tbody');
const confirmBtn    = document.getElementById('confirm-exit');
const selectedInfo  = document.getElementById('selected-entry-info');
const fefoWarning   = document.getElementById('fefo-warning');
const fewoText      = document.getElementById('fefo-warning-text');
const toastEl       = document.getElementById('exit-toast');
const toast         = new bootstrap.Toast(toastEl, { delay: 3500 });

let selectedEntryId = null;
let currentProduct  = null;

// ---- Lookup produit ----
async function lookup(barcode) {
    try {
        const res  = await fetch(`/stock-exits/lookup?barcode=${encodeURIComponent(barcode)}`);
        const data = await res.json();

        if (!data.found) {
            noProduct.innerHTML = '<div class="alert alert-danger">Produit introuvable pour ce code-barres.</div>';
            productPanel.classList.add('d-none');
            return;
        }

        currentProduct  = data.product;
        selectedEntryId = null;
        confirmBtn.disabled = true;
        fefoWarning.classList.add('d-none');

        // En-tête produit
        document.getElementById('product-name').textContent = data.product.name;
        document.getElementById('product-meta').textContent =
            `Réf : ${data.product.reference ?? '—'}  |  Code-barres : ${data.product.barcode}  |  Unité : ${data.product.unit}`;
        document.getElementById('product-total-stock').textContent =
            data.product.total_stock + ' ' + (data.product.unit ?? '');

        const photoWrap = document.getElementById('product-photo-wrap');
        if (data.product.photo_path) {
            document.getElementById('product-photo').src = '/' + data.product.photo_path;
            photoWrap.classList.remove('d-none');
        } else {
            photoWrap.classList.add('d-none');
        }

        // Tableau des entrées
        renderEntries(data.entries);

        noProduct.innerHTML = '';
        noProduct.classList.remove('alert-danger');
        productPanel.classList.remove('d-none');

    } catch (e) {
        noProduct.textContent = 'Erreur de communication. Vérifiez votre connexion.';
    }
}

function renderEntries(entries) {
    document.getElementById('entries-count').textContent = entries.length + ' entrée(s)';
    entriesTbody.innerHTML = '';

    if (!entries.length) {
        entriesTbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center py-3">Aucun stock disponible pour ce produit.</td></tr>';
        return;
    }

    // Trouve la date d'expiration la plus proche pour afficher l'alerte FEFO
    const withExpiry = entries.filter(e => e.expiry_date).sort((a, b) => a.expiry_date.localeCompare(b.expiry_date));
    const earliest   = withExpiry[0] ?? null;

    entries.forEach((entry, idx) => {
        const tr   = document.createElement('tr');
        tr.style.cursor = 'pointer';

        // Coloration : rouge si périmé, orange si dans 7 jours
        let expiryBadge = '—';
        if (entry.expiry_date) {
            const days = Math.ceil((new Date(entry.expiry_date) - new Date()) / 86400000);
            const cls  = days < 0 ? 'danger' : days <= 7 ? 'warning text-dark' : 'success';
            const label = days < 0 ? `Périmé (${entry.expiry_date})` : `${entry.expiry_date} (${days}j)`;
            expiryBadge = `<span class="badge bg-${cls}">${label}</span>`;
            if (earliest && entry.id === earliest.id && entries.length > 1) {
                expiryBadge += ' <span class="badge bg-info text-dark">+ proche</span>';
            }
        }

        tr.innerHTML = `
            <td><input type="radio" name="entry-select" class="form-check-input"
                       value="${entry.id}" id="entry-${entry.id}"></td>
            <td><label for="entry-${entry.id}" style="cursor:pointer;">${entry.entry_date}</label></td>
            <td>${expiryBadge}</td>
            <td>${entry.expiry_type ?? '—'}</td>
            <td><strong>${entry.remaining_quantity}</strong></td>
            <td class="text-muted small">${entry.origin ?? '—'}</td>`;

        tr.addEventListener('click', () => {
            tr.querySelector('input[type=radio]').checked = true;
            selectEntry(entry);
        });

        entriesTbody.appendChild(tr);
    });
}

function selectEntry(entry) {
    selectedEntryId = entry.id;
    confirmBtn.disabled = false;

    const maxQty = entry.remaining_quantity;
    const qtyInput = document.getElementById('exit-quantity');
    qtyInput.max   = maxQty;
    if (parseInt(qtyInput.value) > maxQty) qtyInput.value = maxQty;

    selectedInfo.textContent =
        `Entrée sélectionnée : entrée du ${entry.entry_date}` +
        (entry.expiry_date ? ` — DDM/DLC : ${entry.expiry_date}` : '') +
        ` — restant : ${entry.remaining_quantity}`;

    // Avertissement FEFO informatif : affiche si une autre entrée expire plus tôt
    fefoWarning.classList.add('d-none');
    const allRows = [...entriesTbody.querySelectorAll('input[type=radio]')];
    const otherEntries = allRows
        .filter(r => r.value != entry.id)
        .map(r => entriesTbody.querySelector(`#entry-${r.value}`)?.closest('tr'));
}

// ---- Confirmation de sortie ----
confirmBtn.addEventListener('click', async () => {
    if (!selectedEntryId) return;

    const quantity = parseInt(document.getElementById('exit-quantity').value);
    const comment  = document.getElementById('exit-comment').value;

    confirmBtn.disabled = true;
    confirmBtn.textContent = '…';

    try {
        const res  = await fetch('/stock-exits', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ stock_entry_id: selectedEntryId, quantity, comment }),
        });
        const data = await res.json();

        if (!data.success) {
            alert(data.message || 'Erreur lors de l\'enregistrement.');
            confirmBtn.disabled = false;
            confirmBtn.textContent = '✓ Confirmer la sortie';
            return;
        }

        // Avertissement FEFO retourné par le serveur
        if (data.warning) {
            fewoText.textContent = data.warning;
            fefoWarning.classList.remove('d-none');
        }

        document.getElementById('toast-body').textContent =
            `Sortie de ${quantity} enregistrée.` + (data.warning ? ' ⚠️ Voir avertissement ci-dessus.' : '');
        toast.show();

        // Recharge les entrées du même produit pour mettre à jour les quantités
        setTimeout(() => lookup(currentProduct.barcode), 600);

    } catch (e) {
        alert('Erreur réseau.');
    } finally {
        confirmBtn.textContent = '✓ Confirmer la sortie';
    }
});

// ---- Scanner ----
let scanner = null;
const scanBtn  = document.getElementById('start-scan');
const videoEl  = document.getElementById('scanner-video');
const wrapperEl = videoEl.closest('.scanner-wrapper');

scanBtn.addEventListener('click', async () => {
    if (scanner) {
        scanner.stop(); scanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
        return;
    }
    wrapperEl.classList.remove('d-none');
    scanBtn.textContent = '⏹ Arrêter';

    scanner = new BarcodeScanner(videoEl, async (code) => {
        scanner.stop(); scanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
        await lookup(code);
    });
    try { await scanner.start(); }
    catch (e) {
        alert(e.message); scanner = null;
        wrapperEl.classList.add('d-none');
        scanBtn.textContent = '📷 Scanner le code-barres';
    }
});

// ---- Saisie manuelle ----
document.getElementById('manual-lookup').addEventListener('click', () => {
    const code = document.getElementById('manual-barcode').value.trim();
    if (code) lookup(code);
});
document.getElementById('manual-barcode').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('manual-lookup').click(); }
});
</script>
