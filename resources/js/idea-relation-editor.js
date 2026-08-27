export default function registerIdeaRelationEditor(Alpine) {
    Alpine.data('ideaRelationEditor', (options) => ({
        candidates: options.candidates || [],
        relations: [],
        draftTargetId: '',
        draftType: 'related_to',
        draftRationale: '',
        error: '',
        nextClientId: 1,

        init() {
            this.relations = (options.initialRelations || []).map((relation) => this.normalizeRelation(relation));
            this.$nextTick(() => this.notifyAssistant());
        },

        normalizeRelation(relation) {
            const candidate = this.candidateFor(relation.target_idea_id);

            return {
                id: relation.id || null,
                client_key: relation.id ? `existing-${relation.id}` : `new-${this.nextClientId++}`,
                target_idea_id: String(relation.target_idea_id),
                target_title: relation.target_title || candidate?.title || 'Idea relacionada',
                target_author: relation.target_author || candidate?.author || '',
                type: relation.type || 'related_to',
                rationale: relation.rationale || '',
                status: relation.status || null,
                status_label: relation.status_label || null,
            };
        },

        candidateFor(id) {
            return this.candidates.find((candidate) => String(candidate.id) === String(id));
        },

        relationKey(relation) {
            return `${relation.target_idea_id}:${relation.type}`;
        },

        addManualRelation() {
            this.error = '';
            const candidate = this.candidateFor(this.draftTargetId);

            if (!candidate) {
                this.error = 'Selecciona una idea para crear la conexión.';
                return;
            }

            const relation = this.normalizeRelation({
                target_idea_id: candidate.id,
                target_title: candidate.title,
                target_author: candidate.author,
                type: this.draftType,
                rationale: this.draftRationale.trim(),
            });

            if (this.relations.some((item) => this.relationKey(item) === this.relationKey(relation))) {
                this.error = 'Ese tipo de relación ya está incorporado para la idea seleccionada.';
                return;
            }

            if (this.relations.length >= 20) {
                this.error = 'Puedes gestionar un máximo de 20 relaciones por idea.';
                return;
            }

            this.relations.push(relation);
            this.draftTargetId = '';
            this.draftType = 'related_to';
            this.draftRationale = '';
            this.notifyAssistant();
        },

        removeRelation(index) {
            this.relations.splice(index, 1);
            this.error = '';
            this.notifyAssistant();
        },

        handleAiToggle(detail) {
            const relation = detail.relation;
            const key = this.relationKey(relation);
            const existingIndex = this.relations.findIndex((item) => this.relationKey(item) === key);

            if (detail.include && existingIndex < 0) {
                if (this.relations.length >= 20) {
                    this.error = 'Puedes gestionar un máximo de 20 relaciones por idea.';
                    return;
                }

                this.relations.push(this.normalizeRelation({
                    target_idea_id: relation.target_idea_id,
                    target_title: relation.target_title,
                    target_author: relation.target_author || '',
                    type: relation.type,
                    rationale: relation.rationale,
                }));
            } else if (!detail.include && existingIndex >= 0) {
                this.relations.splice(existingIndex, 1);
            }

            this.error = '';
            this.notifyAssistant();
        },

        notifyAssistant() {
            window.dispatchEvent(new CustomEvent('semantic-relations-changed', {
                detail: {
                    relations: this.relations.map((relation) => ({
                        target_idea_id: relation.target_idea_id,
                        type: relation.type,
                    })),
                },
            }));
        },

        relationLabel(type) {
            return options.types?.[type] || type;
        },
    }));
}
