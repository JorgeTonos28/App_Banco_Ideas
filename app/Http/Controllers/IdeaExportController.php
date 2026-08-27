<?php

namespace App\Http\Controllers;

use App\Http\Requests\Idea\ExportIdeaTreeRequest;
use App\Models\Idea;
use App\Services\IdeaExportService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class IdeaExportController extends Controller
{
    public function __invoke(
        ExportIdeaTreeRequest $request,
        Idea $idea,
        IdeaExportService $service
    ): Response {
        $fields = $request->boolean('all')
            ? IdeaExportService::OPTIONAL_FIELDS
            : $request->input('fields', []);
        $export = $service->build($idea, $request->user(), $fields);
        $filename = Str::slug($idea->title).'-arbol-'.now()->format('Ymd-His');

        if ($request->input('format') === 'json') {
            return response(
                json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                200,
                [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'.json"',
                    'X-Content-Type-Options' => 'nosniff',
                ],
            );
        }

        return response()
            ->view('ideas.export', compact('export', 'idea'))
            ->header('Content-Type', 'application/msword; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'.doc"')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
