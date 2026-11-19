<?php

namespace App\Observers;

use App\Models\ReactionElement;

class ReactionElementObserver
{
    /**
     * Handle the ReactionElement "created" event.
     */
    public function created(ReactionElement $reactionElement): void
    {
        //
    }

    /**
     * Handle the ReactionElement "updated" event.
     */
    public function updated(ReactionElement $reactionElement): void
    {
        //
    }

public function saved($element)
{
    // \App\Http\Controllers\ReactionsController::clearReactionsCache($element->reaction_pack_id);
}

public function deleted($element)
{
    // \App\Http\Controllers\ReactionsController::clearReactionsCache($element->reaction_pack_id);
}

    /**
     * Handle the ReactionElement "restored" event.
     */
    public function restored(ReactionElement $reactionElement): void
    {
        //
    }

    /**
     * Handle the ReactionElement "force deleted" event.
     */
    public function forceDeleted(ReactionElement $reactionElement): void
    {
        //
    }
}
