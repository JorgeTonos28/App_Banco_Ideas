<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\IdeaStatusHistory;
use App\Models\User;

class IdeaStatusCascadeService
{
    public function __construct(private readonly IdeaHierarchyService $hierarchy) {}

    public function cascadeTerminalStatus(Idea $idea, string $status, User $actor): int
    {
        if (! in_array($status, Idea::TERMINAL_WORKSPACE_STATUSES, true)) {
            return 0;
        }

        $descendants = Idea::query()
            ->whereIn('id', $this->hierarchy->descendantIds($idea))
            ->where('workspace_status', '!=', $status)
            ->get();

        foreach ($descendants as $descendant) {
            $oldStatus = $descendant->workspace_status;
            $descendant->update(['workspace_status' => $status]);
            IdeaStatusHistory::create([
                'idea_id' => $descendant->id,
                'user_id' => $actor->id,
                'workflow' => 'workspace',
                'old_status' => $oldStatus,
                'new_status' => $status,
                'comment' => 'Estado heredado desde la idea madre «'.$idea->title.'».',
            ]);
        }

        return $descendants->count();
    }
}
