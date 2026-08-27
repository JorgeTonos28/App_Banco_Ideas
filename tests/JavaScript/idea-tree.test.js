import test from 'node:test';
import assert from 'node:assert/strict';
import registerIdeaTree, { createIdeaTreeNodeState, createIdeaTreeState } from '../../resources/js/idea-tree.js';

function fakeLocalStorage() {
    return {
        values: new Map(),
        getItem(key) { return this.values.get(key) || null; },
        setItem(key, value) { this.values.set(key, value); },
    };
}

test('registers both Alpine tree components before startup', () => {
    const registrations = new Map();

    registerIdeaTree({ data: (name, factory) => registrations.set(name, factory) });

    assert.equal(registrations.get('ideaTree'), createIdeaTreeState);
    assert.equal(registrations.get('ideaTreeNode'), createIdeaTreeNodeState);
});

test('keeps active roots visible and terminal roots hidden by default', () => {
    globalThis.window = { localStorage: fakeLocalStorage() };
    const tree = createIdeaTreeState([], 'my-ideas-internas');

    tree.init();

    assert.equal(tree.rootNodeVisible('en_ejecucion'), true);
    assert.equal(tree.rootNodeVisible('completada'), false);
    assert.equal(tree.rootNodeVisible('archivada'), false);
    assert.equal(tree.rootNodeVisible('descartada'), false);
});

test('persists root and child visibility independently', () => {
    globalThis.window = { localStorage: fakeLocalStorage() };
    const tree = createIdeaTreeState([], 'my-ideas-internas');
    tree.rootVisibility.completada = true;
    tree.saveRootVisibility();

    const restoredTree = createIdeaTreeState([], 'my-ideas-internas');
    restoredTree.init();
    assert.equal(restoredTree.rootNodeVisible('completada'), true);

    const node = createIdeaTreeNodeState('17');
    node.visibility.archivada = true;
    node.saveVisibility();

    const restoredNode = createIdeaTreeNodeState('17');
    restoredNode.init();
    assert.equal(restoredNode.childVisible('archivada'), true);
    assert.equal(restoredNode.childVisible('descartada'), false);
});
