import test from 'node:test';
import assert from 'node:assert/strict';
import registerIdeaRelationEditor from '../../resources/js/idea-relation-editor.js';

function createEditor(initialRelations = []) {
    let factory;
    registerIdeaRelationEditor({
        data(name, callback) {
            if (name === 'ideaRelationEditor') factory = callback;
        },
    });

    const dispatched = [];
    globalThis.CustomEvent = class {
        constructor(type, options = {}) {
            this.type = type;
            this.detail = options.detail;
        }
    };
    globalThis.window = {
        dispatchEvent(event) {
            dispatched.push(event);
        },
    };

    const editor = factory({
        candidates: [
            { id: '2', title: 'Inventario', author: 'Ana' },
            { id: '3', title: 'Panel', author: 'Luis' },
        ],
        initialRelations,
        types: { related_to: 'Relacionada con', enables: 'Habilita' },
    });
    editor.$nextTick = (callback) => callback();
    editor.init();

    return { editor, dispatched };
}

test('adds a manual relation and prevents duplicate relation keys', () => {
    const { editor } = createEditor();
    editor.draftTargetId = '2';
    editor.draftType = 'related_to';
    editor.draftRationale = 'Comparten el mismo inventario institucional.';
    editor.addManualRelation();

    assert.equal(editor.relations.length, 1);
    assert.equal(editor.relations[0].target_title, 'Inventario');

    editor.draftTargetId = '2';
    editor.draftType = 'related_to';
    editor.addManualRelation();
    assert.equal(editor.relations.length, 1);
    assert.match(editor.error, /ya está incorporado/);
});

test('AI relation toggles are reflected in the form relation collection', () => {
    const { editor, dispatched } = createEditor();
    const relation = {
        target_idea_id: '3',
        target_title: 'Panel',
        type: 'enables',
        rationale: 'La idea genera la información del panel.',
    };

    editor.handleAiToggle({ relation, include: true });
    assert.equal(editor.relations.length, 1);
    assert.equal(editor.relations[0].type, 'enables');

    editor.handleAiToggle({ relation, include: false });
    assert.equal(editor.relations.length, 0);
    assert.equal(dispatched.at(-1).type, 'semantic-relations-changed');
    assert.deepEqual(dispatched.at(-1).detail.relations, []);
});
