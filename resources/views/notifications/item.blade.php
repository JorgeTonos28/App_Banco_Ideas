<div class="flex items-start justify-between p-3.5 rounded-2xl {{ $notification->read_at ? 'bg-surface-container-low/50' : 'bg-primary-fixed/20 border border-primary/20' }} transition-colors">
    <div class="flex items-start gap-3 min-w-0">
        <div class="w-8 h-8 rounded-xl bg-primary-fixed flex items-center justify-center text-primary shrink-0 mt-0.5">
            <span class="material-symbols-outlined text-lg">
                {{ $notification->data['icon'] ?? 'notifications' }}
            </span>
        </div>
        <div class="min-w-0">
            <p class="text-xs font-semibold text-on-surface">
                {{ $notification->data['message'] ?? 'Actualización en una de tus ideas' }}
            </p>
            <span class="text-[10px] font-mono-tech text-outline">{{ $notification->created_at->diffForHumans() }}</span>
        </div>
    </div>

    @if(!$notification->read_at)
    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
        @csrf
        <button type="submit" class="text-outline hover:text-primary p-1" title="Marcar como leída">
            <span class="material-symbols-outlined text-base">done</span>
        </button>
    </form>
    @endif
</div>
