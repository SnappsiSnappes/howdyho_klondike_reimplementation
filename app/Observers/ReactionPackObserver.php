<?php

namespace App\Observers;

use App\Models\ReactionPack;

class ReactionPackObserver
{
    /**
     * Handle the ReactionPack "created" event.
     */
    public function created(ReactionPack $reactionPack): void
    {
        //
    }

    /**
     * Handle the ReactionPack "updated" event.
     */
    public function updated(ReactionPack $reactionPack): void
    {
        //
    }

    public function saved($pack)
{
    // \App\Http\Controllers\ReactionsController::clearReactionsCache($pack->id);
}

    /**
     * Handle the ReactionPack "deleted" event.
     */
public function deleted($pack)
{
    // \App\Http\Controllers\ReactionsController::clearReactionsCache($pack->id);
}

    /**
     * Handle the ReactionPack "restored" event.
     */
    public function restored(ReactionPack $reactionPack): void
    {
        //
    }

    /**
     * Handle the ReactionPack "force deleted" event.
     */
    public function forceDeleted(ReactionPack $reactionPack): void
    {
        //
    }
}
