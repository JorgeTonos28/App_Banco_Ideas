@extends('layouts.app')

@section('title', 'Editar Mi Perfil - INNOVATEP')

@section('content')
<div class="max-w-2xl mx-auto space-y-6" x-data="avatarCropper()">

    <!-- Header & Profile Navigation Tabs -->
    <div class="space-y-4">
        <div>
            <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-on-surface">Mi Perfil</h1>
            <p class="text-xs sm:text-sm text-on-surface-variant mt-1">Actualiza tus datos profesionales, biografía y configuración de seguridad</p>
        </div>

        <div class="flex items-center gap-3 border-b border-surface-container-high pb-px text-xs font-semibold">
            <a href="{{ route('profile.edit') }}" class="py-2.5 px-3 text-primary font-bold border-b-2 border-primary">
                Información Personal
            </a>
            <a href="{{ route('profile.security') }}" class="py-2.5 px-3 text-on-surface-variant hover:text-on-surface border-b-2 border-transparent">
                Seguridad & 2FA
            </a>
        </div>
    </div>

    <div class="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 border border-surface-container-high/80 shadow-xs">
        
        @if($errors->any())
        <div class="mb-6 p-4 rounded-2xl bg-error-container/60 border border-error/30 text-error text-xs font-medium space-y-1">
            @foreach($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Hidden Cropped Image Input -->
            <input type="hidden" name="avatar_cropped" :value="croppedDataUrl">

            <!-- Avatar & Size Guidelines Box -->
            <div class="p-5 rounded-2xl bg-surface-container-low border border-surface-container-high/80 space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <!-- Avatar Preview -->
                    <div class="relative group shrink-0">
                        <img :src="previewUrl || '{{ $user->avatar_url }}'" 
                             alt="{{ $user->name }}" 
                             class="w-20 h-20 rounded-2xl object-cover ring-2 ring-primary/20 shadow-xs">
                        
                        <label for="avatar_input" class="absolute inset-0 bg-primary/60 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white cursor-pointer" title="Cambiar Foto">
                            <span class="material-symbols-outlined text-xl">photo_camera</span>
                        </label>
                    </div>

                    <div class="flex-1 space-y-1.5">
                        <div class="flex items-center gap-2">
                            <label for="avatar_input" class="text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech">
                                Foto de Perfil Institucional
                            </label>
                            <span class="px-2 py-0.5 rounded-md bg-secondary-fixed/30 text-on-secondary-fixed text-[10px] font-mono-tech font-bold">
                                400 × 400 px
                            </span>
                        </div>

                        <p class="text-xs text-on-surface-variant leading-relaxed">
                            Formato cuadrado (relación <b>1:1</b>). Formatos compatibles: <b>JPG, PNG o WebP</b> (máx. 5 MB).
                        </p>

                        <div class="flex items-center gap-3 pt-1">
                            <label for="avatar_input" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-primary text-white text-xs font-bold rounded-xl cursor-pointer hover:bg-primary-container shadow-xs transition-colors">
                                <span class="material-symbols-outlined text-sm">upload</span>
                                <span>Subir y Ajustar Foto</span>
                            </label>
                            <input type="file" 
                                   id="avatar_input" 
                                   accept="image/png, image/jpeg, image/webp" 
                                   @change="handleFileSelect($event)" 
                                   class="hidden">

                            <span x-show="croppedDataUrl" class="text-emerald-700 font-bold text-xs flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">check_circle</span>
                                <span>Ajuste listo para guardar</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Name -->
            <div>
                <label for="name" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Nombre Completo <span class="text-error">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name', $user->name) }}" 
                       required 
                       class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Job Title -->
            <div>
                <label for="job_title" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Cargo Institucional
                </label>
                <input type="text" 
                       id="job_title" 
                       name="job_title" 
                       value="{{ old('job_title', $user->job_title) }}" 
                       placeholder="Ej.: Instructor Técnico en Mecánica"
                       class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Department & Organizational Community -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="department" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                        Área / Departamento
                    </label>
                    <input type="text" 
                           id="department" 
                           name="department" 
                           value="{{ old('department', $user->department) }}" 
                           placeholder="Ej.: Formación Profesional"
                           class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <span class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                        Unidad organizacional
                    </span>
                    <div class="rounded-xl border border-surface-container-high bg-surface-container p-3 text-xs text-on-surface">
                        {{ $user->effectiveOrganizationalUnit()?->path_label ?: 'Sin unidad organizacional asignada' }}
                    </div>
                    <p class="mt-1 text-[10px] text-on-surface-variant">Por seguridad, esta asignación la gestiona el equipo administrador.</p>
                </div>
            </div>

            <!-- Bio -->
            <div>
                <label for="bio" class="block text-xs font-bold text-on-surface uppercase tracking-wider font-mono-tech mb-1.5">
                    Biografía / Perfil Profesional
                </label>
                <textarea id="bio" 
                          name="bio" 
                          rows="4" 
                          placeholder="Comparte tu experiencia técnica, áreas de especialidad e intereses en innovación dentro de INFOTEP..."
                          class="w-full bg-surface-container-low text-on-surface text-sm rounded-xl py-2.5 px-3.5 border border-surface-container-high focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary resize-none">{{ old('bio', $user->bio) }}</textarea>
                <p class="text-[11px] text-on-surface-variant mt-1">Máximo 1,000 caracteres.</p>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-container-high">
                <a href="{{ route('profile.show') }}" 
                   class="px-5 py-2.5 text-xs font-semibold text-on-surface-variant hover:text-on-surface transition-colors">
                    Cancelar
                </a>
                <button type="submit" 
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white font-headline font-bold text-xs rounded-xl shadow-xs hover:bg-primary-container active:scale-95 transition-all">
                    <span class="material-symbols-outlined text-base">save</span>
                    <span>Guardar Cambios</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Interactive Canvas Cropper Modal -->
    <div x-show="showCropModal" class="fixed inset-0 z-50 overflow-y-auto p-4 flex items-center justify-center" style="display: none;">
        <div class="fixed inset-0 bg-on-surface/50 backdrop-blur-xs" @click="showCropModal = false"></div>
        <div class="relative bg-surface-container-lowest rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl border border-surface-container-high z-10 space-y-4">
            
            <div class="flex items-center justify-between border-b border-surface-container-high pb-3">
                <div>
                    <h3 class="font-headline font-bold text-base text-on-surface">Ajustar Foto al Canvas</h3>
                    <p class="text-[11px] text-on-surface-variant">Arrastra y haz zoom para encuadrar tu foto de perfil</p>
                </div>
                <button type="button" @click="showCropModal = false" class="text-outline hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Canvas Viewport -->
            <div class="flex justify-center relative bg-surface-container-low rounded-2xl p-2 border border-surface-container-high overflow-hidden select-none cursor-move">
                <canvas id="cropper_canvas" 
                        width="320" 
                        height="320" 
                        class="rounded-xl touch-none shadow-inner"
                        @mousedown="startDrag($event)"
                        @mousemove="drag($event)"
                        @mouseup="endDrag()"
                        @mouseleave="endDrag()"
                        @touchstart="startTouch($event)"
                        @touchmove="touchMove($event)"
                        @touchend="endDrag()"></canvas>
            </div>

            <!-- Controls Toolbar -->
            <div class="space-y-3 pt-1">
                <!-- Zoom Slider -->
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-outline text-lg">zoom_out</span>
                    <input type="range" 
                           min="0.3" 
                           max="3" 
                           step="0.05" 
                           x-model="scale" 
                           @input="drawCanvas()" 
                           class="w-full accent-primary">
                    <span class="material-symbols-outlined text-outline text-lg">zoom_in</span>
                </div>

                <!-- Action Tools (Rotate, Center/Fit) -->
                <div class="flex items-center justify-center gap-2">
                    <button type="button" 
                            @click="rotateImage(-90)" 
                            class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">rotate_left</span>
                        <span>-90°</span>
                    </button>
                    <button type="button" 
                            @click="rotateImage(90)" 
                            class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">rotate_right</span>
                        <span>+90°</span>
                    </button>
                    <button type="button" 
                            @click="fitToCanvas()" 
                            class="px-3 py-1.5 bg-surface-container hover:bg-surface-container-high rounded-xl text-xs font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-base">aspect_ratio</span>
                        <span>Centrar</span>
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-surface-container-high flex justify-end gap-2">
                <button type="button" @click="showCropModal = false" class="px-4 py-2 text-xs font-semibold text-outline">
                    Cancelar
                </button>
                <button type="button" 
                        @click="applyCrop()" 
                        class="px-5 py-2 bg-primary text-white text-xs font-bold rounded-xl hover:bg-primary-container shadow-xs flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">crop</span>
                    <span>Aplicar Ajuste</span>
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function avatarCropper() {
    return {
        showCropModal: false,
        imageObj: null,
        previewUrl: null,
        croppedDataUrl: null,
        scale: 1,
        rotation: 0,
        offsetX: 0,
        offsetY: 0,
        isDragging: false,
        dragStartX: 0,
        dragStartY: 0,

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    this.imageObj = img;
                    this.showCropModal = true;
                    this.$nextTick(() => {
                        this.fitToCanvas();
                    });
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        fitToCanvas() {
            if (!this.imageObj) return;
            const canvas = document.getElementById('cropper_canvas');
            const cw = canvas.width;
            const ch = canvas.height;

            const minDim = Math.min(this.imageObj.width, this.imageObj.height);
            this.scale = cw / minDim;
            this.rotation = 0;
            this.offsetX = cw / 2;
            this.offsetY = ch / 2;
            this.drawCanvas();
        },

        rotateImage(degrees) {
            this.rotation = (this.rotation + degrees) % 360;
            this.drawCanvas();
        },

        drawCanvas() {
            const canvas = document.getElementById('cropper_canvas');
            if (!canvas || !this.imageObj) return;
            const ctx = canvas.getContext('2d');
            const cw = canvas.width;
            const ch = canvas.height;

            // Clear
            ctx.clearRect(0, 0, cw, ch);

            // Draw image with transform
            ctx.save();
            ctx.translate(this.offsetX, this.offsetY);
            ctx.rotate((this.rotation * Math.PI) / 180);
            ctx.scale(this.scale, this.scale);
            ctx.drawImage(this.imageObj, -this.imageObj.width / 2, -this.imageObj.height / 2);
            ctx.restore();

            // Circular Guide Mask Overlay
            ctx.save();
            ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
            ctx.fillRect(0, 0, cw, ch);

            // Cut out circular hole
            ctx.globalCompositeOperation = 'destination-out';
            ctx.beginPath();
            ctx.arc(cw / 2, ch / 2, (cw / 2) - 8, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            // Circular ring border
            ctx.save();
            ctx.strokeStyle = '#005696';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            ctx.arc(cw / 2, ch / 2, (cw / 2) - 8, 0, Math.PI * 2);
            ctx.stroke();
            ctx.restore();
        },

        startDrag(e) {
            this.isDragging = true;
            this.dragStartX = e.clientX - this.offsetX;
            this.dragStartY = e.clientY - this.offsetY;
        },

        drag(e) {
            if (!this.isDragging) return;
            this.offsetX = e.clientX - this.dragStartX;
            this.offsetY = e.clientY - this.dragStartY;
            this.drawCanvas();
        },

        startTouch(e) {
            if (e.touches.length === 1) {
                this.isDragging = true;
                this.dragStartX = e.touches[0].clientX - this.offsetX;
                this.dragStartY = e.touches[0].clientY - this.offsetY;
            }
        },

        touchMove(e) {
            if (!this.isDragging || e.touches.length !== 1) return;
            this.offsetX = e.touches[0].clientX - this.dragStartX;
            this.offsetY = e.touches[0].clientY - this.dragStartY;
            this.drawCanvas();
        },

        endDrag() {
            this.isDragging = false;
        },

        applyCrop() {
            if (!this.imageObj) return;

            // Render high-res 400x400 export canvas
            const exportCanvas = document.createElement('canvas');
            exportCanvas.width = 400;
            exportCanvas.height = 400;
            const expCtx = exportCanvas.getContext('2d');

            const scaleRatio = 400 / 320;

            expCtx.save();
            expCtx.translate(this.offsetX * scaleRatio, this.offsetY * scaleRatio);
            expCtx.rotate((this.rotation * Math.PI) / 180);
            expCtx.scale(this.scale * scaleRatio, this.scale * scaleRatio);
            expCtx.drawImage(this.imageObj, -this.imageObj.width / 2, -this.imageObj.height / 2);
            expCtx.restore();

            const finalDataUrl = exportCanvas.toDataURL('image/jpeg', 0.92);
            this.croppedDataUrl = finalDataUrl;
            this.previewUrl = finalDataUrl;
            this.showCropModal = false;
        }
    };
}
</script>
@endsection
