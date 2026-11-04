<?php
// app/Core/OutOfStockException.php
// Raised when a paid order can no longer be fulfilled because inventory ran out.

declare(strict_types=1);

namespace App\Core;

class OutOfStockException extends \RuntimeException
{
    /** @var string[] */
    private array $productNames;

    public function __construct(array $productNames)
    {
        $this->productNames = $productNames;
        parent::__construct('Insufficient stock for: ' . implode(', ', $productNames));
    }

    public function getProductNames(): array
    {
        return $this->productNames;
    }
}
