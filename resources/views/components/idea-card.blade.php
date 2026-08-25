@props(['idea'])

<div class="bg-surface-container-lowest rounded-2xl p-5 sm:p-6 shadow-sm hover:shadow-md border border-surface-container-high/60 transition-all flex flex-col justify-between group relative overflow-hidden">
    <!-- Top Gradient Accent on Hover -->
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary via-primary-container to-secondary-container opacity-0 group-hover:opacity-100 transition-opacity"></div>

    <div>
        <!-- Card Header: Category & Status Badges -->
        <div class="flex flex-wrap items-center justify-between gap-y-2 gap-x-2 mb-3">
            @if($idea->category)
            <a href="{{ route('ideas.index', ['categoria' => $idea->category->slug]) }}" 
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline bg-primary-fixed/40 px-2.5 py-0.5 rounded-full border border-primary/10 shrink-0">
                <span class="material-symbols-outlined text-sm">{{ $idea->category->icon }}</span>
                <span class="truncate max-w-[130px]">{{ $idea->category->name }}</span>
            </a>
            @else
            <div></div>
            @endif

            <div class="flex flex-wrap items-center gap-1.5 ml-auto">
                @if($idea->is_featured)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-bold border border-amber-200 shrink-0" title="Idea Destacada">
                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">star</span>
                    <span>Destacada</span>
                </span>
                @elseif($idea->innovation_score >= 80)
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 text-[10px] font-bold border border-orange-200 shrink-0" title="En Tendencia">
                    <span class="material-symbols-outlined text-xs" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                    <span>Tendencia</span>
                </span>
                @endif

                @if($idea->isPublished())
                    <x-status-badge :status="$idea->status" />
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary/10 text-tertiary text-[10px] font-bold border border-tertiary/15">
                        <span class="material-symbols-outlined text-xs">person</span>
                        Visible en perfil
                    </span>
                @endif
            </div>
        </div>

        <!-- Idea Title -->
        <a href="{{ route('ideas.show', $idea->slug) }}" class="block group-hover:text-primary transition-colors">
            <h3 class="font-headline font-bold text-base sm:text-lg text-on-surface line-clamp-2 leading-snug mb-2">
                {{ $idea->title }}
            </h3>
        </a>

        <!-- Summary -->
        <p class="text-xs sm:text-sm text-on-surface-variant line-clamp-3 leading-relaxed mb-4">
            {{ $idea->summary ?: Str::limit(strip_tags($idea->description), 140) }}
        </p>

        @if(($idea->published_children_count ?? 0) > 0)
        <div class="mb-4 inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-tertiary/10 border border-tertiary/15 text-tertiary text-xs font-bold">
            <span class="material-symbols-outlined text-base">account_tree</span>
            <span>{{ $idea->published_children_count }} {{ $idea->published_children_count === 1 ? 'microidea trazable' : 'microideas trazables' }}</span>
        </div>
        @endif

        <!-- Tags List -->
        @if($idea->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1.5 mb-4">
            @foreach($idea->tags->take(3) as $tag)
            <a href="{{ route('ideas.index', ['etiqueta' => $tag->slug]) }}" 
               class="px-2 py-0.5 rounded-md bg-surface-container text-on-surface-variant text-[11px] font-mono-tech hover:bg-primary-fixed hover:text-primary transition-colors">
                #{{ $tag->name }}
            </a>
            @endforeach
            @if($idea->tags->count() > 3)
            <span class="text-[11px] font-mono-tech text-outline self-center">+{{ $idea->tags->count() - 3 }}</span>
            @endif
        </div>
        @endif
    </div>

    <!-- Card Footer -->
    <div class="pt-4 border-t border-surface-container-high/60 flex items-center justify-between gap-2 mt-auto">
        <!-- Author Info -->
        <a href="{{ route('profile.show', $idea->user_id) }}" class="flex items-center gap-2 min-w-0 group/author">
            <img src="{{ $idea->user->avatar_url }}" 
                 alt="{{ $idea->user->name }}" 
                 class="w-7 h-7 rounded-full object-cover ring-1 ring-white shadow-2xs">
            <div class="min-w-0">
                <span class="text-xs font-semibold text-on-surface block truncate group-hover/author:text-primary transition-colors">
                    {{ $idea->user->name }}
                </span>
                <span class="text-[10px] text-on-surface-variant block truncate">
                    {{ $idea->user->department ?: 'INFOTEP' }}
                </span>
            </div>
        </a>

        <!-- Stats Counters -->
        <div class="flex items-center gap-3 text-xs text-on-surface-variant shrink-0 font-mono-tech">
            <!-- Votes & Rating -->
            <div class="flex items-center gap-1 text-primary font-semibold" title="{{ $idea->votes_count }} valoraciones (Promedio {{ number_format($idea->average_rating, 1) }} / 5)">
                <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' 1;">thumb_up</span>
                <span>{{ $idea->votes_count }}</span>
            </div>

            @if($idea->hasPreliminaryRatings())
            <span class="hidden lg:inline text-[9px] font-bold uppercase text-tertiary" title="Valoración previa a la publicación comunitaria">Preliminar</span>
            @endif

            <!-- Comments -->
            <div class="flex items-center gap-1 hover:text-on-surface transition-colors" title="{{ $idea->comments_count ?? $idea->comments->count() }} comentarios">
                <span class="material-symbols-outlined text-base">chat_bubble</span>
                <span>{{ $idea->comments_count ?? $idea->comments->count() }}</span>
            </div>

            <!-- Views -->
            <div class="hidden sm:flex items-center gap-1 text-outline" title="{{ $idea->views_count }} visualizaciones">
                <span class="material-symbols-outlined text-base">visibility</span>
                <span>{{ $idea->views_count }}</span>
            </div>
        </div>
    </div>
</div>
