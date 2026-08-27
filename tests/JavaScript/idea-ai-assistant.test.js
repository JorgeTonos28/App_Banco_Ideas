import test from 'node:test';
import assert from 'node:assert/strict';
import registerIdeaAiAssistant from '../../resources/js/idea-ai-assistant.js';

function createAssistant() {
    let factory;
    registerIdeaAiAssistant({
        data(name, callback) {
            if (name === 'ideaAiAssistant') factory = callback;
        },
    });

    return factory({
        transcribeUrl: '/transcribe',
        organizeUrl: '/organize',
        relationsUrl: '/relations',
        currentIdeaId: 10,
    });
}

function installDom({ tags = [], classifications = [] } = {}) {
    const fields = new Map([
        ['#title', { value: '', dispatchEvent() {} }],
        ['#description', { value: '', dispatchEvent() {} }],
        ['#problem_opportunity', { value: '', dispatchEvent() {} }],
        ['#category_id', { value: '', dispatchEvent() {} }],
        ['#parent_idea_id', { value: '', dataset: { selectedTitle: 'Sin idea madre' }, dispatchEvent() {} }],
    ]);
    const tagInputs = tags.map((value) => ({ value }));
    const classificationInputs = classifications.map((input) => ({
        checked: false,
        dispatchEvent() {},
        ...input,
        value: String(input.value),
    }));
    const events = [];
    const scrolls = [];
    const relationResults = {
        scrollIntoView(options) {
            scrolls.push(options);
        },
    };

    globalThis.Event = class {
        constructor(type, options = {}) {
            this.type = type;
            this.bubbles = options.bubbles;
        }
    };
    globalThis.CustomEvent = class extends Event {
        constructor(type, options = {}) {
            super(type);
            this.detail = options.detail;
        }
    };
    globalThis.document = {
        querySelector(selector) {
            if (selector === '#ai-relation-results') return relationResults;
            return fields.get(selector) || null;
        },
        querySelectorAll(selector) {
            if (selector === 'input[name="tags[]"]') return tagInputs;
            if (selector === 'input[name^="classifications["]') return classificationInputs;

            const classificationName = selector.match(/^input\[name="(.+)"\]$/)?.[1];
            if (classificationName) {
                return classificationInputs.filter((input) => input.name === classificationName);
            }

            return [];
        },
    };
    globalThis.window = {
        dispatchEvent(event) {
            events.push(event);
        },
        clearTimeout() {},
        setTimeout(callback) {
            callback();
            return 1;
        },
    };

    return { fields, events, classificationInputs, scrolls };
}

test('marks individual and aggregate AI suggestions as applied', () => {
    const { fields } = installDom();
    const assistant = createAssistant();
    assistant.suggestion = {
        title: 'Título organizado',
        description: 'Descripción organizada',
        problem_opportunity: 'Problema organizado',
        primary_category_id: 7,
        classifications: [],
        tags: [{ name: 'Automatización' }],
        parent_suggestion: { idea_id: null, idea_title: 'Sin idea madre' },
    };

    assistant.applyText('title');
    assert.equal(fields.get('#title').value, 'Título organizado');
    assert.equal(assistant.isApplied('title'), true);
    assert.equal(assistant.allContentApplied, false);

    assistant.applyAll();
    assert.equal(assistant.allContentApplied, true);
});

test('applying AI tags emits a replacement payload and resets on a new analysis', async () => {
    const { events } = installDom();
    const assistant = createAssistant();
    assistant.suggestion = {
        tags: [{ name: 'IA' }, { name: 'Procesos' }],
    };

    assistant.applyTags();
    const tagEvent = events.find((event) => event.type === 'ai-tags-suggested');
    assert.deepEqual(tagEvent.detail.names, ['IA', 'Procesos']);
    assert.equal(assistant.isApplied('tags'), true);

    assistant.request = async () => ({ title: 'Análisis nuevo' });
    await assistant.analyze();
    assert.equal(assistant.isApplied('tags'), false);
});

test('keeping original text restores the value captured before analysis', async () => {
    const { fields } = installDom();
    const assistant = createAssistant();
    fields.get('#title').value = 'Título escrito por la persona';
    fields.get('#description').value = 'Descripción íntegra original';
    assistant.request = async () => ({
        title: 'Título sugerido por IA',
        description: 'Descripción reescrita por IA',
    });

    await assistant.analyze();
    assistant.applyText('description');
    assert.equal(fields.get('#description').value, 'Descripción reescrita por IA');

    assistant.keepOriginal('description');
    assert.equal(fields.get('#description').value, 'Descripción íntegra original');
    assert.equal(assistant.isOriginal('description'), true);
    assert.equal(assistant.isApplied('description'), false);
});

test('keeping original organization restores classifications, tags, and parent idea', () => {
    const { fields, events, classificationInputs } = installDom({
        tags: ['Procesos', 'Calidad'],
        classifications: [
            { name: 'classifications[2][]', value: 20, checked: true },
            { name: 'classifications[2][]', value: 21, checked: false },
        ],
    });
    const assistant = createAssistant();
    fields.get('#category_id').value = '4';
    fields.get('#parent_idea_id').value = '8';
    fields.get('#parent_idea_id').dataset.selectedTitle = 'Idea madre original';
    assistant.originalValues = assistant.captureCurrentValues();
    assistant.suggestion = {
        primary_category_id: 7,
        classifications: [{ dimension_id: 2, category_ids: [21] }],
        tags: [{ name: 'IA' }],
        parent_suggestion: { idea_id: 9, idea_title: 'Idea madre sugerida' },
    };

    assistant.applyClassification();
    assistant.keepOriginal('classification');
    assert.equal(fields.get('#category_id').value, '4');
    assert.equal(classificationInputs[0].checked, true);
    assert.equal(classificationInputs[1].checked, false);
    assert.equal(assistant.isOriginal('classification'), true);

    assistant.applyTags();
    assistant.keepOriginal('tags');
    const tagEvents = events.filter((event) => event.type === 'ai-tags-suggested');
    assert.deepEqual(tagEvents.at(-1).detail.names, ['Procesos', 'Calidad']);

    assistant.applyParent();
    assistant.keepOriginal('parent');
    const parentEvents = events.filter((event) => event.type === 'ai-parent-suggested');
    assert.deepEqual(parentEvents.at(-1).detail, { id: '8', title: 'Idea madre original' });
});

test('semantic relation analysis confirms completion and reveals its results', async () => {
    const { scrolls } = installDom();
    const assistant = createAssistant();
    assistant.request = async () => ({
        relations: [{ target_idea_id: 14, type: 'related_to', target_title: 'Idea relacionada' }],
    });

    await assistant.suggestRelations();

    assert.equal(assistant.relationAnalysisComplete, true);
    assert.equal(assistant.relationSuggestions.length, 1);
    assert.equal(assistant.state, 'review');
    assert.deepEqual(scrolls, [{ behavior: 'smooth', block: 'start' }]);
});
