<?php
// app/Core/Paginator.php
// Minimal offset paginator used by the storefront (?page=1,2,3...).

declare(strict_types=1);

namespace App\Core;

class Paginator
{
    public readonly int $totalPages;
    public readonly int $page;

    public function __construct(public readonly int $total, public readonly int $perPage, int $requestedPage)
    {
        $this->perPage > 0 || throw new \InvalidArgumentException('perPage must be positive');
        $this->totalPages = max(1, (int)ceil($total / $perPage));
        $this->page = min(max(1, $requestedPage), $this->totalPages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function firstItem(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    public function lastItem(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    /** Page numbers to show, with null marking an ellipsis gap, e.g. [1, null, 4, 5, 6, null, 20]. */
    public function window(int $radius = 2): array
    {
        $pages = [];
        for ($i = 1; $i <= $this->totalPages; $i++) {
            if ($i === 1 || $i === $this->totalPages || abs($i - $this->page) <= $radius) {
                $pages[] = $i;
            } elseif (end($pages) !== null) {
                $pages[] = null;
            }
        }
        return $pages;
    }
}
