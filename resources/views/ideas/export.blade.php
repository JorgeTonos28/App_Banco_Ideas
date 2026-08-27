<!doctype html>
<html lang="es"><head><meta charset="utf-8"><title>{{ $idea->title }}</title><style>body{font-family:Arial,sans-serif;color:#172033;line-height:1.45}h1{color:#003e6f;border-bottom:3px solid #feb700;padding-bottom:10px}.node{margin:16px 0 16px 24px;border-left:3px solid #dbe4f5;padding-left:16px}.node.level-0{margin-left:0;border-left-color:#003e6f}.meta{color:#657083;font-size:11px}.label{font-weight:bold;color:#003e6f}.tags span{display:inline-block;background:#eef3ff;padding:3px 7px;margin:2px;border-radius:8px;font-size:11px}.relation{background:#f6f7fb;padding:8px;margin:5px 0}</style></head>
<body>
<h1>{{ $export['idea']['title'] }}</h1>
<p class="meta">Exportado desde INNOVATEP · Centro de Innovación · {{ now()->translatedFormat('d M Y, h:i A') }}</p>
@php
    $renderNode = function (array $node, int $level = 0) use (&$renderNode) {
        echo '<section class="node level-'.$level.'">';
        echo '<h'.min(6, $level + 2).'>'.e($node['title']).'</h'.min(6, $level + 2).'>';
        if (array_key_exists('description', $node)) echo '<p><span class="label">Descripción:</span><br>'.nl2br(e($node['description'] ?? '—')).'</p>';
        if (array_key_exists('problem_opportunity', $node)) echo '<p><span class="label">Problema u oportunidad:</span><br>'.nl2br(e($node['problem_opportunity'] ?? '—')).'</p>';
        if (!empty($node['categories'])) { echo '<p><span class="label">Categorías:</span> '.e(collect($node['categories'])->map(fn($item) => ($item['dimension'] ? $item['dimension'].': ' : '').$item['name'])->join(' · ')).'</p>'; }
        if (!empty($node['tags'])) { echo '<div class="tags"><span class="label">Etiquetas</span> '; foreach ($node['tags'] as $tag) echo '<span>#'.e($tag).'</span>'; echo '</div>'; }
        if (!empty($node['relations'])) { echo '<p class="label">Relaciones semánticas</p>'; foreach ($node['relations'] as $relation) echo '<div class="relation"><strong>'.e($relation['type']).': '.e($relation['target_title']).'</strong><br><span class="meta">'.e($relation['target_author'] ?? '').'</span><br>'.e($relation['rationale'] ?? '').'</div>'; }
        foreach ($node['children'] ?? [] as $child) $renderNode($child, $level + 1);
        echo '</section>';
    };
    $renderNode($export['idea']);
@endphp
</body></html>
