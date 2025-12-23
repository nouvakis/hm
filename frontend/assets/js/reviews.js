/**
 * frontend/assets/js/reviews.js
 */
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.querySelector('#reviewFormSection form');
    const stars = document.querySelectorAll('.rate input[type="radio"]');
    const movieIDInput = document.querySelector('input[name="movie_id"]');

    if (!movieIDInput) return;
    const movieID = movieIDInput.value;

    // 1. Αποθήκευση Βαθμολογίας (Stars) - Σιωπηλή
    stars.forEach(star => {
        star.addEventListener('change', function() {
            const formData = new FormData();
            formData.append('action', 'submit_rating_ajax');
            formData.append('movie_id', movieID);
            formData.append('rating', this.value);

            fetch('backend/reviews_controller.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log('Rating saved');
                }
            })
            .catch(err => console.error('Error:', err));
        });
    });

    // 2. Υποβολή Κριτικής (Post) - Με Πράσινο Toast
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault(); 

            const formData = new FormData(this);
            formData.append('action', 'submit_review_ajax');

            fetch('backend/reviews_controller.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showGreenToast('Η κριτική σας αποθηκεύτηκε!', false);
                } else {
                    alert('Σφάλμα: ' + data.message);
                }
            })
            .catch(err => console.error('Error:', err));
        });
    }
});

/**
 * Εμφάνιση Toast (Πράσινο για επιτυχία, Γκρι για αφαίρεση)
 */
function showGreenToast(message, isGray) {
    const toastEl = document.getElementById('ajaxToast');
    if (toastEl) {
        // Καθαρισμός κλάσεων
        toastEl.classList.remove('bg-success', 'bg-secondary', 'hide');
        
        // Ορισμός χρώματος
        if (isGray) {
            toastEl.classList.add('bg-secondary');
        } else {
            toastEl.classList.add('bg-success');
        }
        
        const toastBody = toastEl.querySelector('.toast-body');
        toastBody.textContent = message;

        // Αρχικοποίηση και εμφάνιση μέσω Bootstrap
        const bsToast = new bootstrap.Toast(toastEl, { delay: 2000 });
        bsToast.show();
    }
}