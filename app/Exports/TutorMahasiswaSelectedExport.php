<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TutorMahasiswaSelectedExport implements WithMultipleSheets
{
    /** @var \Illuminate\Support\Collection<int,\App\Models\User> */
    protected Collection $users;
    protected ?string $groupNo;
    protected ?string $prodyName;

    public function __construct(Collection $users, ?string $groupNo = null, ?string $prodyName = null)
    {
        $this->users = $users;
        $this->groupNo = $groupNo;
        $this->prodyName = $prodyName;
    }

    public function sheets(): array
    {
        return [
            new TutorMahasiswaTemplateExport($this->users, $this->groupNo, $this->prodyName),
            new TutorMahasiswaDailyExport($this->users, $this->groupNo, $this->prodyName),
        ];
    }
}
