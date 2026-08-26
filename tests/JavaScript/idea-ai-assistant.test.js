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

function installDom() {
    const fields = new Map([
        ['#title', { value: '', dispatchEvent() {} }],
        ['#description', { value: '', dispatchEvent() {} }],
        ['#problem_opportunity', { value: '', dispatchEvent() {} }],
        ['#category_id', { value: '', dispatchEvent() {} }],
        ['#parent_idea_id', { value: '', dispatchEvent() {} }],
    ]);
    const events = [];

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
            return fields.get(selector) || null;
        },
        querySelectorAll() {
            return [];
        },
    };
    globalThis.window = {
        dispatchEvent(event) {
            events.push(event);
        },
    };

    return { fields, events };
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
