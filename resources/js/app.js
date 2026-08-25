import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global Search Component
Alpine.data('globalSearch', () => ({
    open: false,
    query: '',
    results: { ideas: [], people: [], categories: [], tags: [] },
    loading: false,
    searchController: null,

    normalizedSearchQuery() {
        return (this.query || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '');
    },

    async search() {
        if (this.normalizedSearchQuery().length < 2) {
            this.searchController?.abort();
            this.results = { ideas: [], people: [], categories: [], tags: [] };
            this.loading = false;
            return;
        }

        this.searchController?.abort();
        const controller = new AbortController();
        this.searchController = controller;
        this.loading = true;

        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(this.query)}`, {
                signal: controller.signal,
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) throw new Error(`Search request failed with ${response.status}`);

            this.results = await response.json();
        } catch (e) {
            if (e.name !== 'AbortError') console.error('Search error:', e);
        } finally {
            if (this.searchController === controller) this.loading = false;
        }
    }
}));

const createIdeaTreeState = (branchTerms = []) => ({
    query: '',
    branchTerms,

    normalizedQuery() {
        return this.normalize(this.query);
    },

    normalize(value) {
        return (value || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '');
    },

    branchMatches(searchTerms) {
        const query = this.normalizedQuery();
        return query === '' || (searchTerms || '').includes(query);
    },

    hasMatches() {
        const query = this.normalizedQuery();
        return query === '' || this.branchTerms.some((terms) => (terms || '').includes(query));
    }
});

Alpine.data('ideaTree', createIdeaTreeState);

Alpine.data('ideaParentPicker', (branchTerms, selectedId, selectedTitle, independentLabel) => ({
    ...createIdeaTreeState(branchTerms),
    open: false,
    selectedId: selectedId ? selectedId.toString() : '',
    selectedTitle: selectedTitle || independentLabel,

    choose(id, title) {
        this.selectedId = id ? id.toString() : '';
        this.selectedTitle = title;
        this.open = false;
        this.query = '';
        this.$dispatch('parent-idea-changed', { id: this.selectedId });
    }
}));

// Star Rating Component
Alpine.data('starRating', (ideaId, currentRating = 0, currentAverage = 0, currentVotes = 0) => ({
    ideaId: ideaId,
    rating: currentRating,
    hoverRating: 0,
    submitting: false,
    userRating: currentRating,
    averageRating: Number(currentAverage).toFixed(1),
    votesCount: Number(currentVotes),

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
                this.averageRating = data.average_rating;
                this.votesCount = data.votes_count;
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
