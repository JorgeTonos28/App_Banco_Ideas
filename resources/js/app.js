import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global Search Component
Alpine.data('globalSearch', () => ({
    open: false,
    query: '',
    results: { ideas: [], people: [], categories: [], tags: [] },
    loading: false,

    async search() {
        if (this.query.trim().length < 2) {
            this.results = { ideas: [], people: [], categories: [], tags: [] };
            return;
        }

        this.loading = true;
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(this.query)}`);
            this.results = await response.json();
        } catch (e) {
            console.error('Search error:', e);
        } finally {
            this.loading = false;
        }
    }
}));

// Star Rating Component
Alpine.data('starRating', (ideaId, currentRating = 0) => ({
    ideaId: ideaId,
    rating: currentRating,
    hoverRating: 0,
    submitting: false,
    userRating: currentRating,

    async setRating(value) {
        if (this.submitting) return;
        this.submitting = true;
        this.rating = value;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch(`/ideas/${this.ideaId}/votar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ rating: value })
            });

            const data = await response.json();
            if (response.ok) {
                this.userRating = value;
                // Dispatch event to update average rating and score in UI if present
                window.dispatchEvent(new CustomEvent('rating-updated', { detail: data }));
            } else {
                alert(data.error || 'Error al registrar valoración');
            }
        } catch (e) {
            console.error(e);
        } finally {
            this.submitting = false;
        }
    }
}));

Alpine.start();
