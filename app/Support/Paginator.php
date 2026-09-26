<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Request;

/**
 * Page numbers for long lists. The page comes from ?faqja=N.
 */
final class Paginator
{
    private function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public static function fromRequest(Request $request, int $total, int $perPage = 50): self
    {
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($pages, max(1, (int) $request->query('faqja', 1)));

        return new self($total, $page, $perPage);
    }

    public function pages(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** Number of the first item on this page (1-based), 0 when empty. */
    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /**
     * Page numbers to show, with null for a gap: [1, null, 4, 5, 6, null, 26].
     *
     * @return list<int|null>
     */
    public function window(int $around = 2): array
    {
        $pages = $this->pages();
        $numbers = [];

        for ($n = 1; $n <= $pages; $n++) {
            if ($n === 1 || $n === $pages || abs($n - $this->page) <= $around) {
                $numbers[] = $n;
            } elseif (end($numbers) !== null) {
                $numbers[] = null;
            }
        }

        return $numbers;
    }
}
