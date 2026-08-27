import test from 'node:test';
import assert from 'node:assert/strict';
import registerTaskTools from '../../resources/js/task-tools.js';

function taskFormFactory() {
    let factory;
    globalThis.window = {
        sessionStorage: {
            values: new Map(),
            getItem(key) { return this.values.get(key) || null; },
            setItem(key, value) { this.values.set(key, value); },
            removeItem(key) { this.values.delete(key); },
        },
    };

    registerTaskTools({
        data(name, callback) {
            if (name === 'taskForm') factory = callback;
        },
    });

    return factory;
}

test('moves an AI task draft into the task form only once', () => {
    const factory = taskFormFactory();
    window.sessionStorage.setItem('innovationTaskDraft', JSON.stringify({
        title: 'Preparar prototipo',
        description: 'Crear una versión navegable para validar.',
        target_idea_id: 12,
        parent_task_id: 31,
    }));

    const form = factory({ title: '', description: '', idea_id: '', parent_task_id: '' });
    form.init();

    assert.equal(form.title, 'Preparar prototipo');
    assert.equal(form.ideaId, '12');
    assert.equal(form.parentTaskId, '31');
    assert.equal(form.loadedFromAi, true);
    assert.equal(window.sessionStorage.getItem('innovationTaskDraft'), null);
});

test('does not overwrite values restored by server-side validation', () => {
    const factory = taskFormFactory();
    window.sessionStorage.setItem('innovationTaskDraft', JSON.stringify({ title: 'Borrador IA' }));
    const form = factory({ title: 'Título corregido por la persona', description: '', idea_id: '', parent_task_id: '' });

    form.init();

    assert.equal(form.title, 'Título corregido por la persona');
    assert.equal(form.loadedFromAi, false);
});
