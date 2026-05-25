<?php

namespace App\Analysis\Detectors;

use App\Analysis\StoryCandidate;

interface Detector
{
    /** Identificador del tipo de historia (coincide con story_candidates.tipo). */
    public function tipo(): string;

    /** @return iterable<StoryCandidate> */
    public function detect(): iterable;
}
