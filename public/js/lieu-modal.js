document.addEventListener('DOMContentLoaded', () => {

    const lieuForm = document.getElementById('lieu-form');

    if (!lieuForm) {
        return;
    }

    lieuForm.addEventListener('submit', async (e) => {

        e.preventDefault();

        const formData = new FormData(lieuForm);

        const response = await fetch(
            lieuForm.action,
            {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        const contentType = response.headers.get('content-type');

        // CAS ERREUR : Symfony renvoie du HTML
        if (!contentType.includes('application/json')) {

            const html = await response.text();

            console.log(html);

            alert('Le formulaire lieu contient des erreurs.');

            return;
        }

        // CAS OK : JSON
        const result = await response.json();

        console.log(result);

        const select = document.getElementById('sortie_lieu');

        const option = new Option(
            result.nom,
            result.id,
            true,
            true
        );

        select.add(option);

        const modalElement = document.getElementById('lieuModal');

        const modal = bootstrap.Modal.getInstance(modalElement);

        modal.hide();

        lieuForm.reset();

    });

});
