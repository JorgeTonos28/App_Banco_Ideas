@extends('layouts.app')

@section('title', $idea->title . ' - INNOVATEP Ideas')

@section('content')
<div class="max-w-5xl mx-auto space-y-8" x-data="{ shareModal: false }">

    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between gap-4">
        <nav class="flex items-center gap-2 text-xs font-mono-tech text-outline">
            <a href="{{ route('ideas.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                <span>Ideas</span>
            </a>
            <span>/</span>
            <span class="text-on-surface truncate max-w-xs sm:max-w-md">{{ $idea->category?->name ?: 'Propuesta' }}</span>
        </nav>

        <!-- Quick actions: Favorite, Share, Edit -->
        <div class="flex items-center gap-2">
            @auth
            <!-- Favorite button -->
            <form action="{{ route('ideas.favorite', $idea->id) }}" method="POST">
                @csrf
                <button type="submit" 
                        class="p-2 rounded-xl border border-surface-container-high bg-surface-container-lowest hover:bg-surface-container-low text-on-surface-variant transition-colors" 
                        title="{{ $idea->isFavoritedBy(auth()->user()) ? 'Quitar de guardadas' : 'Guardar idea' }}">
                    <span class="material-symbols-outlined text-lg {{ $idea->isFavoritedBy(auth()->user()) ? 'text-secondary font-bold' : '' }}" 
                          style="{{ $idea->isFavoritedBy(auth()->user()) ? 'font-variation-settings: \'FILL\' 1;' : '' }}">
                        bookmark
                    </span>
                </button>
            </form>

            <!-- Edit button for author/admin if editable -->
            @if($idea->isEditableBy(auth()->user()))
            <a href="{{ route('ideas.edit', $idea->id) }}" 
               class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary-fixed hover:bg-primary hover:text-white text-primary text-xs font-bold transition-colors"
               title="Editar idea y etiquetas">
                <span class="material-symbols-outlined text-base">edit</span>
                <span>Editar Propuesta</span>
            </a>
            @endif
            @endauth

            <button @click="navigator.clipboard.writeText(window.location.href); alert('Enlace copiado al portapapeles');" 
                    class="p-2 rounded-xl border border-surface-container-high bg-surface-container-lowest hover:bg-surface-container-low text-on-surface-variant transition-colors" 
                    title="Compartir idea">
                <span class="material-symbols-outlined text-lg">share</span>
            </button>
        </div>
    </div>

    <!-- Main Idea Header Card -->
    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                @if($idea->category)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-xs font-semibold">
                    <span class="material-symbols-outlined text-sm">{{ $idea->category->icon }}</span>
                    <span>{{ $idea->category->name }}</span>
                </span>
                @endif

                @if($idea->is_featured)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">star</span>
                    <span>Destacada</span>
                </span>
                @endif
            </div>

            <x-status-badge :status="$idea->status" />
        </div>

        <!-- Title -->
        <h1 class="font-headline font-extrabold text-2xl sm:text-4xl text-on-surface leading-tight">
            {{ $idea->title }}
        </h1>

        <!-- Author Block & Metadata -->
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 rounded-2xl bg-surface-container-low border border-surface-container-high/50">
            <a href="{{ route('profile.show', $idea->user_id) }}" class="flex items-center gap-3.5 group">
                <img src="{{ $idea->user->avatar_url }}" alt="{{ $idea->user->name }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-white shadow-2xs">
                <div>
                    <span class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors block">
                        {{ $idea->user->name }}
                    </span>
                    <span class="text-xs text-on-surface-variant block">
                        {{ $idea->user->job_title ?: 'Colaborador' }} • {{ $idea->user->department ?: 'INFOTEP' }}
                    </span>
                </div>
            </a>

            <div class="flex items-center gap-4 text-xs font-mono-tech text-outline">
                <div class="flex items-center gap-1" title="Fecha de publicación">
                    <span class="material-symbols-outlined text-base">calendar_today</span>
                    <span>{{ $idea->created_at->translatedFormat('d M, Y') }}</span>
                </div>
                <div class="flex items-center gap-1" title="Visualizaciones">
                    <span class="material-symbols-outlined text-base">visibility</span>
                    <span>{{ $idea->views_count }} vistas</span>
                </div>
            </div>
        </div>

        <!-- Tags List -->
        @if($idea->tags->isNotEmpty())
        <div class="flex flex-wrap gap-2 pt-2">
            @foreach($idea->tags as $tag)
            <a href="{{ route('ideas.index', ['etiqueta' => $tag->slug]) }}" 
               class="px-3 py-1 rounded-lg bg-surface-container hover:bg-primary-fixed hover:text-primary text-on-surface-variant text-xs font-mono-tech transition-colors">
                #{{ $tag->name }}
            </a>
            @endforeach
        </div>
        @endif
    </div>

    <!-- 2-Column Main Content & Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Details, Evolution, Attachments, Comments (8 cols) -->
        <div class="lg:col-span-8 space-y-8">
            
            <!-- Section: La Idea -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-2xl">lightbulb</span>
                    <span>La Propuesta</span>
                </h2>
                <div class="text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line prose prose-blue max-w-none">
                    {{ $idea->description }}
                </div>
            </div>

            <!-- Section: Problema u Oportunidad -->
            @if($idea->problem_opportunity)
            <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-secondary text-2xl">target</span>
                    <span>Problema u Oportunidad Detectada</span>
                </h2>
                <div class="text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line">
                    {{ $idea->problem_opportunity }}
                </div>
            </div>
            @endif

            <!-- Section: Archivos Adjuntos -->
            @if($idea->attachments->isNotEmpty())
            <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-4">
                <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-2xl">attachment</span>
                    <span>Archivos y Evidencias Adjuntas</span>
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($idea->attachments as $file)
                    <a href="{{ $file->url }}" target="_blank" class="flex items-center gap-3 p-3 rounded-2xl bg-surface-container-low hover:bg-surface-container border border-surface-container-high transition-colors group">
                        <div class="w-10 h-10 rounded-xl bg-surface-container flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-xl">
                                {{ $file->is_pdf ? 'picture_as_pdf' : ($file->is_image ? 'image' : 'description') }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-on-surface truncate">{{ $file->file_name }}</p>
                            <p class="text-[10px] text-on-surface-variant font-mono-tech">{{ $file->formatted_size }}</p>
                        </div>
                        <span class="material-symbols-outlined text-outline text-lg">download</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Section: Evolution Timeline -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-6">
                <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary text-2xl">route</span>
                    <span>Evolución de la Idea</span>
                </h2>

                <!-- Linear Progress Stepper Bar -->
                @php
                $stages = [
                    'nueva' => 'Nueva',
                    'en_revision' => 'En revisión',
                    'priorizada' => 'Priorizada',
                    'en_desarrollo' => 'En desarrollo',
                    'implementada' => 'Implementada'
                ];
                $stageKeys = array_keys($stages);
                $currentIndex = array_search($idea->status, $stageKeys);
                if ($currentIndex === false) $currentIndex = 0;
                @endphp

                <div class="relative flex items-center justify-between pb-6 pt-2">
                    <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1 bg-surface-container-high z-0"></div>
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-primary z-0 transition-all duration-500" 
                         style="width: {{ count($stages) > 1 ? ($currentIndex / (count($stages) - 1)) * 100 : 0 }}%;"></div>

                    @foreach($stages as $key => $label)
                    @php $idx = array_search($key, $stageKeys); @endphp
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold font-mono-tech transition-all shadow-sm
                            {{ $idx < $currentIndex ? 'bg-primary text-white' : ($idx === $currentIndex ? 'bg-secondary-container text-on-secondary-container ring-4 ring-secondary-fixed/30' : 'bg-surface-container-high text-outline') }}">
                            @if($idx < $currentIndex)
                                <span class="material-symbols-outlined text-base">check</span>
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                        <span class="text-[10px] sm:text-xs font-semibold text-center mt-2 {{ $idx <= $currentIndex ? 'text-on-surface font-bold' : 'text-outline' }}">
                            {{ $label }}
                        </span>
                    </div>
                    @endforeach
                </div>

                <!-- History Log Feed -->
                @if($idea->statusHistories->isNotEmpty())
                <div class="relative pl-6 border-l-2 border-surface-container-high space-y-6 mt-6">
                    @foreach($idea->statusHistories as $history)
                    <div class="relative group">
                        <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 rounded-full bg-primary ring-4 ring-surface"></div>
                        <div class="text-xs font-mono-tech text-outline mb-1">
                            {{ $history->created_at->translatedFormat('d M, Y - h:i A') }} • Por {{ $history->user?->name ?: 'Sistema' }}
                        </div>
                        <div class="text-sm font-bold text-on-surface flex items-center gap-2">
                            <span>Estado:</span>
                            <span class="px-2 py-0.5 rounded-full bg-surface-container text-xs font-mono-tech">{{ $history->new_status_label }}</span>
                        </div>
                        @if($history->comment)
                        <p class="text-xs sm:text-sm text-on-surface-variant mt-1.5 leading-relaxed bg-surface-container-low p-3 rounded-xl">
                            {{ $history->comment }}
                        </p>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Section: Comments & Collaborative Conversation -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-headline font-bold text-xl text-on-surface flex items-center gap-2.5">
                        <span class="material-symbols-outlined text-primary text-2xl">forum</span>
                        <span>Conversación y Aportes</span>
                    </h2>
                    <span class="text-xs font-mono-tech text-outline font-bold">{{ $idea->comments->count() }} comentarios</span>
                </div>

                <!-- New Comment Form -->
                @auth
                <form action="{{ route('comments.store', $idea->id) }}" method="POST" class="space-y-3">
                    @csrf
                    <div class="flex items-start gap-3">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full object-cover ring-1 ring-white">
                        <div class="flex-1">
                            <textarea name="content" 
                                      rows="3" 
                                      required 
                                      placeholder="Comparte una observación, sugerencia de mejora o pregunta sobre esta idea..."
                                      class="w-full bg-surface-container-low text-on-surface text-sm rounded-2xl p-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-primary text-white text-xs font-bold rounded-xl shadow-xs hover:bg-primary-container transition-colors">
                            Publicar Comentario
                        </button>
                    </div>
                </form>
                @else
                <div class="p-4 rounded-2xl bg-surface-container-low text-center text-xs text-on-surface-variant">
                    <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Inicia sesión</a> para unirte a la conversación y aportar mejoras.
                </div>
                @endauth

                <!-- Comments List -->
                <div class="space-y-4 divide-y divide-surface-container-high/60 pt-2">
                    @forelse($idea->comments as $comment)
                    <div class="pt-4 first:pt-0 space-y-3" x-data="{ replying: false }">
                        <div class="flex items-start gap-3">
                            <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="w-9 h-9 rounded-full object-cover">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-on-surface">{{ $comment->user->name }}</span>
                                    <span class="text-[10px] font-mono-tech text-outline">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <span class="text-[10px] text-on-surface-variant block mb-1.5">{{ $comment->user->department ?: 'INFOTEP' }}</span>
                                <p class="text-xs sm:text-sm text-on-surface-variant leading-relaxed whitespace-pre-line">{{ $comment->content }}</p>

                                <!-- Comment Actions: Like & Reply -->
                                <div class="flex items-center gap-4 mt-2 text-xs">
                                    <form action="{{ route('comments.like', $comment->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 text-outline hover:text-primary transition-colors font-mono-tech {{ auth()->check() && $comment->isLikedBy(auth()->user()) ? 'text-primary font-bold' : '' }}">
                                            <span class="material-symbols-outlined text-sm" style="{{ auth()->check() && $comment->isLikedBy(auth()->user()) ? 'font-variation-settings: \'FILL\' 1;' : '' }}">thumb_up</span>
                                            <span>{{ $comment->likes_count }}</span>
                                        </button>
                                    </form>

                                    @auth
                                    <button @click="replying = !replying" class="text-outline hover:text-primary transition-colors font-medium">
                                        Responder
                                    </button>
                                    @endauth
                                </div>
                            </div>
                        </div>

                        <!-- Reply Input Box -->
                        @auth
                        <div x-show="replying" x-transition class="pl-12 pt-2">
                            <form action="{{ route('comments.store', $idea->id) }}" method="POST" class="space-y-2">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                <textarea name="content" rows="2" required placeholder="Escribe tu respuesta..." class="w-full bg-surface-container-low text-xs rounded-xl p-3 border border-surface-container-high focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                                <div class="flex justify-end gap-2">
                                    <button type="button" @click="replying = false" class="px-3 py-1.5 text-xs text-outline">Cancelar</button>
                                    <button type="submit" class="px-4 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary-container">Responder</button>
                                </div>
                            </form>
                        </div>
                        @endauth

                        <!-- Nested Replies -->
                        @if($comment->replies->isNotEmpty())
                        <div class="pl-10 space-y-3 pt-2 border-l-2 border-surface-container-high ml-4">
                            @foreach($comment->replies as $reply)
                            <div class="flex items-start gap-2.5">
                                <img src="{{ $reply->user->avatar_url }}" alt="{{ $reply->user->name }}" class="w-7 h-7 rounded-full object-cover">
                                <div class="flex-1 min-w-0 bg-surface-container-low p-3 rounded-2xl">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-on-surface">{{ $reply->user->name }}</span>
                                        <span class="text-[10px] font-mono-tech text-outline">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-1 leading-relaxed">{{ $reply->content }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @empty
                    <p class="text-xs text-on-surface-variant text-center py-4">Aún no hay comentarios. ¡Sé el primero en compartir tu punto de vista!</p>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Right: Interactive Voting, Innovation Score & Related Ideas (4 cols) -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- Voting Widget Card -->
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs relative overflow-hidden text-center"
                 x-data="starRating({{ $idea->id }}, {{ $idea->user_rating ?? 0 }})">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-secondary-container/5 pointer-events-none"></div>

                <div class="relative z-10 space-y-4">
                    <h3 class="font-headline font-bold text-lg text-on-surface">¿Qué te parece esta idea?</h3>
                    <p class="text-xs text-on-surface-variant">Califica el valor e impacto potencial para INFOTEP</p>

                    <!-- Interactive Stars (1 to 5) -->
                    @if(auth()->check() && auth()->id() === $idea->user_id)
                    <div class="p-3 rounded-2xl bg-surface-container-low text-xs text-on-surface-variant">
                        💡 Como autor de esta propuesta, no puedes calificar tu propia idea.
                    </div>
                    @else
                    <div class="flex items-center justify-center gap-2 py-2">
                        @for($star = 1; $star <= 5; $star++)
                        <button type="button" 
                                @click="setRating({{ $star }})" 
                                @mouseenter="hoverRating = {{ $star }}" 
                                @mouseleave="hoverRating = 0"
                                class="text-3xl transition-transform hover:scale-125 focus:outline-none"
                                :class="(hoverRating >= {{ $star }} || (!hoverRating && rating >= {{ $star }})) ? 'text-amber-400' : 'text-outline-variant'">
                            <span class="material-symbols-outlined" 
                                  :style="(hoverRating >= {{ $star }} || (!hoverRating && rating >= {{ $star }})) ? 'font-variation-settings: \'FILL\' 1;' : ''">
                                star
                            </span>
                        </button>
                        @endfor
                    </div>

                    <div x-show="userRating > 0" class="text-xs font-mono-tech font-bold text-primary">
                        Tu valoración: <span x-text="userRating"></span> / 5 ★
                    </div>
                    @endif

                    <!-- Aggregate Score Stats -->
                    <div class="flex items-center justify-center gap-2 pt-3 border-t border-surface-container-high/60">
                        <div class="flex items-center gap-1.5 text-base font-bold text-on-surface">
                            <span class="material-symbols-outlined text-amber-400" style="font-variation-settings: 'FILL' 1;">star</span>
                            <span>{{ number_format($idea->average_rating, 1) }}</span>
                            <span class="text-xs font-normal text-outline">/ 5</span>
                        </div>
                        <span class="text-outline text-xs">•</span>
                        <span class="text-xs font-mono-tech text-on-surface-variant">{{ $idea->votes_count }} valoraciones</span>
                    </div>
                </div>
            </div>

            <!-- Innovation Score Card -->
            <div class="bg-gradient-to-br from-primary to-primary-container text-white rounded-3xl p-6 shadow-md relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
                <div class="flex items-center justify-between mb-4">
                    <span class="font-mono-tech text-xs uppercase font-bold tracking-wider text-on-primary-container">Innovation Score</span>
                    <span class="material-symbols-outlined text-secondary-container text-2xl" style="font-variation-settings: 'FILL' 1;">bolt</span>
                </div>

                <div class="flex items-baseline gap-2">
                    <span class="font-headline font-extrabold text-5xl">{{ $idea->innovation_score }}</span>
                    <span class="text-on-primary-container text-sm font-mono-tech">/ 100</span>
                </div>

                <p class="text-xs text-on-primary-container mt-3 leading-relaxed">
                    El algoritmo pondera valoraciones, volumen de votos, comentarios recientes e impacto pedagógico e institucional.
                </p>
            </div>

            <!-- Related Ideas in Category -->
            @if($relatedIdeas->isNotEmpty())
            <div class="bg-surface-container-lowest rounded-3xl p-6 border border-surface-container-high/80 shadow-xs space-y-4">
                <h3 class="font-headline font-bold text-sm text-on-surface uppercase tracking-wider font-mono-tech">Más en {{ $idea->category?->name }}</h3>
                <div class="space-y-3">
                    @foreach($relatedIdeas as $rel)
                    <a href="{{ route('ideas.show', $rel->slug) }}" class="block p-3 rounded-2xl bg-surface-container-low hover:bg-surface-container transition-colors group">
                        <p class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors line-clamp-2">{{ $rel->title }}</p>
                        <div class="flex items-center justify-between text-[10px] text-on-surface-variant font-mono-tech mt-2">
                            <span>{{ $rel->user->name }}</span>
                            <span class="text-primary font-semibold">Score: {{ $rel->innovation_score }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

    </div>

</div>
@endsection
