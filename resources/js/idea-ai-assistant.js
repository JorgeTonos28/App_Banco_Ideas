export default function registerIdeaAiAssistant(Alpine) {
    Alpine.data('ideaAiAssistant', (options) => ({
        state: 'idle',
        error: '',
        transcript: '',
        suggestion: null,
        relationSuggestions: [],
        confirmedRelations: [],
        appliedSections: {
            title: false,
            description: false,
            problem_opportunity: false,
            classification: false,
            tags: false,
            parent: false,
        },
        mediaRecorder: null,
        mediaStream: null,
        audioChunks: [],
        seconds: 0,
        timer: null,

        get isBusy() {
            return ['transcribing', 'analyzing', 'relations'].includes(this.state);
        },

        get canRecord() {
            return Boolean(navigator.mediaDevices?.getUserMedia && window.MediaRecorder);
        },

        get allContentApplied() {
            return Object.values(this.appliedSections).every(Boolean);
        },

        async startRecording() {
            this.error = '';
            if (!this.canRecord) {
                this.error = 'Este navegador no permite grabar audio. Puedes escribir el borrador y analizarlo.';
                return;
            }

            try {
                this.mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mimeType = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4']
                    .find((type) => MediaRecorder.isTypeSupported(type));
                if (!mimeType) {
                    this.error = 'Este navegador no genera un formato de audio compatible. Puedes escribir la idea y analizarla.';
                    this.cleanupRecording();
                    return;
                }
                this.mediaRecorder = new MediaRecorder(this.mediaStream, { mimeType });
                this.audioChunks = [];
                this.mediaRecorder.ondataavailable = (event) => {
                    if (event.data.size > 0) this.audioChunks.push(event.data);
                };
                this.mediaRecorder.onstop = () => this.uploadRecording();
                this.mediaRecorder.start();
                this.state = 'recording';
                this.seconds = 0;
                this.timer = window.setInterval(() => {
                    this.seconds += 1;
                    if (this.seconds >= 300) this.stopRecording();
                }, 1000);
            } catch (error) {
                this.error = 'No se pudo acceder al micrófono. Revisa el permiso del navegador.';
                this.cleanupRecording();
            }
        },

        stopRecording() {
            if (this.mediaRecorder?.state === 'recording') this.mediaRecorder.stop();
            window.clearInterval(this.timer);
        },

        cleanupRecording() {
            window.clearInterval(this.timer);
            this.mediaStream?.getTracks().forEach((track) => track.stop());
            this.mediaStream = null;
            this.mediaRecorder = null;
        },

        async uploadRecording() {
            const mimeType = this.mediaRecorder?.mimeType || 'audio/webm';
            const extension = mimeType.includes('mp4') ? 'm4a' : 'webm';
            const blob = new Blob(this.audioChunks, { type: mimeType });
            this.cleanupRecording();
            this.state = 'transcribing';

            try {
                const body = new FormData();
                body.append('audio', blob, `idea-${Date.now()}.${extension}`);
                const data = await this.request(options.transcribeUrl, body, false);
                this.transcript = data.transcript;
                await this.analyze();
            } catch (error) {
                this.fail(error);
            }
        },

        async analyze() {
            this.error = '';
            this.state = 'analyzing';

            try {
                this.suggestion = await this.request(options.organizeUrl, {
                    transcript: this.transcript,
                    title: document.querySelector('#title')?.value || '',
                    description: document.querySelector('#description')?.value || '',
                    problem_opportunity: document.querySelector('#problem_opportunity')?.value || '',
                    current_idea_id: options.currentIdeaId,
                });
                this.resetAppliedSections();
                this.relationSuggestions = [];
                this.confirmedRelations = [];
                this.state = 'review';
            } catch (error) {
                this.fail(error);
            }
        },

        async suggestRelations() {
            this.error = '';
            this.state = 'relations';

            try {
                const data = await this.request(options.relationsUrl, {
                    title: document.querySelector('#title')?.value || this.suggestion?.title || '',
                    description: document.querySelector('#description')?.value || this.suggestion?.description || '',
                    problem_opportunity: document.querySelector('#problem_opportunity')?.value || this.suggestion?.problem_opportunity || '',
                    parent_idea_id: document.querySelector('#parent_idea_id')?.value || null,
                    current_idea_id: options.currentIdeaId,
                });
                this.relationSuggestions = data.relations || [];
                this.state = 'review';
            } catch (error) {
                this.fail(error);
            }
        },

        applyText(field) {
            const value = this.suggestion?.[field];
            if (value === undefined || value === null) return;
            const input = document.querySelector(`#${field}`);
            if (!input) return;
            input.value = value;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            this.markApplied(field);
        },

        applyClassification() {
            const categoryId = this.suggestion?.primary_category_id;
            if (categoryId) {
                const category = document.querySelector('#category_id');
                if (category) {
                    category.value = String(categoryId);
                    category.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            (this.suggestion?.classifications || []).forEach((selection) => {
                const selector = `input[name="classifications[${selection.dimension_id}][]"]`;
                document.querySelectorAll(selector).forEach((input) => {
                    input.checked = selection.category_ids.map(String).includes(input.value);
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });

            this.markApplied('classification');
        },

        applyTags() {
            const names = (this.suggestion?.tags || []).map((tag) => tag.name);
            window.dispatchEvent(new CustomEvent('ai-tags-suggested', { detail: { names } }));
            this.markApplied('tags');
        },

        applyParent() {
            const parent = this.suggestion?.parent_suggestion;
            if (!parent) return;
            window.dispatchEvent(new CustomEvent('ai-parent-suggested', {
                detail: { id: parent.idea_id || '', title: parent.idea_title || 'Sin idea madre' }
            }));
            this.markApplied('parent');
        },

        applyAll() {
            ['title', 'description', 'problem_opportunity'].forEach((field) => this.applyText(field));
            this.applyClassification();
            this.applyTags();
            this.applyParent();
        },

        resetAppliedSections() {
            Object.keys(this.appliedSections).forEach((section) => {
                this.appliedSections[section] = false;
            });
        },

        markApplied(section) {
            if (Object.prototype.hasOwnProperty.call(this.appliedSections, section)) {
                this.appliedSections[section] = true;
            }
        },

        isApplied(section) {
            return Boolean(this.appliedSections[section]);
        },

        toggleRelation(relation) {
            const key = `${relation.target_idea_id}:${relation.type}`;
            const index = this.confirmedRelations.findIndex((item) => `${item.target_idea_id}:${item.type}` === key);
            if (index >= 0) this.confirmedRelations.splice(index, 1);
            else this.confirmedRelations.push(relation);
        },

        relationIsConfirmed(relation) {
            return this.confirmedRelations.some((item) => item.target_idea_id === relation.target_idea_id && item.type === relation.type);
        },

        async request(url, payload, json = true) {
            const headers = {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            };
            if (json) headers['Content-Type'] = 'application/json';

            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: json ? JSON.stringify(payload) : payload,
            });
            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validation = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(validation || result.message || 'No fue posible completar la solicitud.');
            }

            return result.data;
        },

        fail(error) {
            this.error = error.message || 'El asistente no está disponible en este momento.';
            this.state = 'idle';
            this.cleanupRecording();
        },

        formatTime() {
            return `${String(Math.floor(this.seconds / 60)).padStart(2, '0')}:${String(this.seconds % 60).padStart(2, '0')}`;
        },

        relationLabel(type) {
            return ({
                depends_on: 'Depende de', enables: 'Habilita', complements: 'Complementa',
                derived_from: 'Deriva de', evolves_into: 'Evoluciona hacia', duplicate_of: 'Posible duplicado de',
                superseded_by: 'Sustituida por', related_to: 'Relacionada con'
            })[type] || type;
        }
    }));
}
