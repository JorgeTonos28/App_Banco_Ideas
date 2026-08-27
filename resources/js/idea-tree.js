const TERMINAL_STATUSES = ['completada', 'archivada', 'descartada'];

const normalizeSearchValue = (value) => (value || '')
    .toString()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '');

export const createIdeaTreeState = (branchTerms = [], storageKey = 'idea-tree') => ({
    query: '',
    branchTerms,
    rootFilterOpen: false,
    rootVisibility: { completada: false, archivada: false, descartada: false },

    init() {
        try {
            const saved = JSON.parse(window.localStorage.getItem(`${storageKey}:root`) || '{}');
            this.rootVisibility = { ...this.rootVisibility, ...saved };
        } catch {
            // Invalid local preferences fall back to the safe hidden state.
        }
    },

    normalizedQuery() {
        return this.normalize(this.query);
    },

    normalize(value) {
        return normalizeSearchValue(value);
    },

    branchMatches(searchTerms) {
        const query = this.normalizedQuery();
        return query === '' || (searchTerms || '').includes(query);
    },

    hasMatches() {
        const query = this.normalizedQuery();
        return query === '' || this.branchTerms.some((terms) => (terms || '').includes(query));
    },

    rootNodeVisible(status) {
        return !TERMINAL_STATUSES.includes(status) || Boolean(this.rootVisibility[status]);
    },

    saveRootVisibility() {
        window.localStorage.setItem(`${storageKey}:root`, JSON.stringify(this.rootVisibility));
    }
});

export const createIdeaTreeNodeState = (nodeId) => ({
    expanded: false,
    filterOpen: false,
    visibility: { completada: false, archivada: false, descartada: false },

    init() {
        try {
            const saved = JSON.parse(window.localStorage.getItem(`idea-tree-node:${nodeId}`) || '{}');
            this.visibility = { ...this.visibility, ...saved };
        } catch {
            // Keep terminal children hidden when the preference cannot be read.
        }
    },

    childVisible(status) {
        return !TERMINAL_STATUSES.includes(status) || Boolean(this.visibility[status]);
    },

    saveVisibility() {
        window.localStorage.setItem(`idea-tree-node:${nodeId}`, JSON.stringify(this.visibility));
    }
});

export default function registerIdeaTree(Alpine) {
    Alpine.data('ideaTree', createIdeaTreeState);
    Alpine.data('ideaTreeNode', createIdeaTreeNodeState);
}
