<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class TranscribeIdeaAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_active;
    }

    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                'file',
                'max:'.config('ai.limits.audio_kilobytes', 10240),
                'extensions:mp3,mp4,mpeg,mpga,m4a,wav,webm',
                'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/webm,video/webm',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'Adjunta una grabación para transcribir.',
            'audio.max' => 'La grabación no puede superar 10 MB.',
            'audio.extensions' => 'Usa audio MP3, MP4, M4A, WAV o WebM.',
            'audio.mimetypes' => 'El tipo de audio recibido no está permitido.',
        ];
    }
}
