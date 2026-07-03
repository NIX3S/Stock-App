/**
 * Initialise les graphiques Chart.js du tableau de bord à partir des
 * données exposées par l'API JSON (chargées en différé pour ne pas
 * alourdir le rendu initial de la page).
 */
async function loadDashboardData() {
    try {
        const [productsRes] = await Promise.all([fetch('/api/products?per_page=200')]);
        const products = await productsRes.json();
        return products.data || [];
    } catch (e) {
        return [];
    }
}

function renderStockByCategory(products) {
    const canvas = document.getElementById('stockChart');
    if (!canvas || !window.Chart) return;

    const byCategory = {};
    products.forEach(p => {
        const cat = p.category_name || 'Sans catégorie';
        byCategory[cat] = (byCategory[cat] || 0) + Number(p.total_stock || 0);
    });

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: Object.keys(byCategory),
            datasets: [{ data: Object.values(byCategory) }],
        },
        options: { responsive: true },
    });
}

function renderMovementsPlaceholder() {
    const canvas = document.getElementById('movementsChart');
    if (!canvas || !window.Chart) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: ['Entrées', 'Sorties'],
            datasets: [{ label: '30 derniers jours', data: [0, 0], backgroundColor: ['#198754', '#dc3545'] }],
        },
        options: { responsive: true },
    });
}

(async () => {
    const products = await loadDashboardData();
    renderStockByCategory(products);
    renderMovementsPlaceholder();
})();
