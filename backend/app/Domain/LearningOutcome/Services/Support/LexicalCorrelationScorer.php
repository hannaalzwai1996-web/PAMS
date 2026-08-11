<?php

namespace App\Domain\LearningOutcome\Services\Support;

use Illuminate\Support\Collection;

/**
 * The PO-PLO auto-generation algorithm — see
 * docs/architecture/0002-po-plo-matrix-engine.md §2.
 *
 * A deliberately simple, deterministic lexical-overlap heuristic: how much
 * significant vocabulary two statements share. It is NOT semantic
 * matching (no synonyms, no paraphrase detection) — PAMS has no
 * embeddings/LLM infrastructure, and this class exists specifically so
 * that limitation is a single, honestly-documented, unit-testable piece
 * of code rather than an implicit assumption buried in a bigger service.
 *
 * Pure function: no I/O, no framework dependency, no database access —
 * that's what makes it testable in complete isolation from everything
 * else in this module.
 */
final class LexicalCorrelationScorer
{
    /**
     * @var array<int, string>
     */
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'of', 'to', 'in', 'on', 'will', 'be',
        'is', 'are', 'that', 'this', 'with', 'by', 'for', 'as', 'at', 'from',
        'their', 'which', 'it', 'its', 'shall', 'can', 'may', 'into', 'such',
    ];

    /**
     * Overlap coefficient — not plain Jaccard — because PO and PLO
     * statements are often very different lengths, and Jaccard penalizes
     * that unfairly. Range [0.0, 1.0]; 0.0 whenever either statement has
     * no significant words at all.
     */
    public function score(string $a, string $b): float
    {
        $wordsA = $this->significantWords($a);
        $wordsB = $this->significantWords($b);

        if ($wordsA->isEmpty() || $wordsB->isEmpty()) {
            return 0.0;
        }

        $overlap = $wordsA->intersect($wordsB)->count();
        $smaller = min($wordsA->count(), $wordsB->count());

        return $overlap / $smaller;
    }

    /**
     * Buckets a score into a suggested `correlation_level` (1-3), or null
     * when there's no lexical overlap at all — BR-MTX-7: the algorithm
     * refuses to guess on a zero-overlap pair rather than defaulting to
     * "Low", leaving it for a human to decide during review.
     */
    public function suggestLevel(string $a, string $b): ?int
    {
        $score = $this->score($a, $b);

        return match (true) {
            $score >= 0.5 => 3,
            $score >= 0.25 => 2,
            $score > 0.0 => 1,
            default => null,
        };
    }

    /**
     * @return Collection<int, string>
     */
    private function significantWords(string $text): Collection
    {
        $tokens = preg_split('/[^a-zA-Z0-9]+/', strtolower($text)) ?: [];

        return collect($tokens)
            ->filter(fn (string $word) => strlen($word) > 2 && ! in_array($word, self::STOPWORDS, true))
            ->unique()
            ->values();
    }
}
