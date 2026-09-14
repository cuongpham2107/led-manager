<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CheckinBatchImport implements ToCollection
{
    public array $rows = [];

    public array $headers = [];

    public function collection(Collection $collection): void
    {
        $this->rows = $collection->toArray();
    }

    public function headingRow(): int
    {
        return 1;
    }
}
