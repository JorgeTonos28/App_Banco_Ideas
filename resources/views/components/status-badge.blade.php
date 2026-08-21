@props(['status'])

@php
$config = match($status) {
    'nueva' => [
        'label' => 'NUEVA',
        'icon' => 'lightbulb',
        'classes' => 'bg-surface-container-high text-on-surface-variant border border-outline-variant/50',
    ],
    'en_revision' => [
        'label' => 'EN REVISIÓN',
        'icon' => 'visibility',
        'classes' => 'bg-secondary-container/20 text-secondary border border-secondary-container/40',
    ],
    'priorizada' => [
        'label' => 'PRIORIZADA',
        'icon' => 'star',
        'classes' => 'bg-primary-fixed text-on-primary-fixed-variant border border-primary/20',
    ],
    'en_desarrollo' => [
        'label' => 'EN DESARROLLO',
        'icon' => 'science',
        'classes' => 'bg-tertiary-fixed text-on-tertiary-fixed-variant border border-tertiary/20',
    ],
    'implementada' => [
        'label' => 'IMPLEMENTADA',
        'icon' => 'rocket_launch',
        'classes' => 'bg-emerald-50 text-emerald-800 border border-emerald-300',
    ],
    'descartada' => [
        'label' => 'DESCARTADA',
        'icon' => 'block',
        'classes' => 'bg-error-container/60 text-on-error-container border border-error/20',
    ],
    'archivada' => [
        'label' => 'ARCHIVADA',
        'icon' => 'inventory_2',
        'classes' => 'bg-slate-100 text-slate-600 border border-slate-300',
    ],
    default => [
        'label' => strtoupper(str_replace('_', ' ', $status)),
        'icon' => 'circle',
        'classes' => 'bg-surface-container text-on-surface border border-outline',
    ],
};
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono-tech font-bold uppercase tracking-wider {{ $config['classes'] }}">
    <span class="material-symbols-outlined text-[13px]" style="font-variation-settings: 'FILL' 1;">{{ $config['icon'] }}</span>
    <span>{{ $config['label'] }}</span>
</span>
