/**
 * Aide à la configuration du formulaire de génération d'étiquettes
 * (sélection produits, copies, colonnes/lignes A4) avant soumission vers
 * /print/labels qui rend la grille imprimable côté serveur.
 */
export function initLabelForm() {
    const form = document.querySelector('#label-form');
    if (!form) return;

    const preview = document.querySelector('#label-preview-count');
    const updatePreview = () => {
        const checked = form.querySelectorAll('input[name="product_ids[]"]:checked').length;
        const copies = parseInt(form.querySelector('[name="copies"]').value || '1', 10);
        if (preview) preview.textContent = `${checked * copies} étiquette(s) seront générées.`;
    };

    form.addEventListener('change', updatePreview);
    updatePreview();
}
