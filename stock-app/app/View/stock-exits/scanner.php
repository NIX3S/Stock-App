<?php $title = 'Scanner un produit'; ?>
<h1 class="h4 mb-3">Scanner un produit</h1>

<div class="card card-body text-center" style="max-width:500px;">
    <video id="scanner-video" class="w-100 rounded bg-dark" autoplay muted playsinline style="max-height:300px;"></video>
    <button id="start-scan" class="btn btn-primary mt-3">Démarrer le scan</button>
    <div class="input-group mt-3">
        <input type="text" id="manual-barcode" class="form-control" placeholder="Saisie manuelle">
        <button id="manual-lookup" class="btn btn-outline-secondary">Rechercher</button>
    </div>
</div>

<script type="module">
import { BarcodeScanner } from '/assets/js/modules/scanner.js';

async function goToProduct(barcode) {
    const res = await fetch(`/scanner/lookup?barcode=${encodeURIComponent(barcode)}`);
    if (!res.ok) {
        alert('Produit introuvable.');
        return;
    }
    const data = await res.json();
    window.location.href = `/products/${data.product.id}`;
}

document.getElementById('manual-lookup').addEventListener('click', () => {
    const code = document.getElementById('manual-barcode').value.trim();
    if (code) goToProduct(code);
});

document.getElementById('start-scan').addEventListener('click', async () => {
    try {
        const scanner = new BarcodeScanner(document.getElementById('scanner-video'), (code) => goToProduct(code));
        await scanner.start();
    } catch (e) {
        alert(e.message);
    }
});
</script>
