<?php

namespace App\Exports;

use App\Models\User;
use App\Support\BlCompute;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TutorMahasiswaDailyExport implements
    FromCollection, WithHeadings, WithMapping, WithEvents, WithColumnWidths, WithStyles, WithTitle
{
    /** @var \Illuminate\Support\Collection<int,\App\Models\User> */
    protected Collection $users;
    protected ?string $groupNo;
    protected ?string $prodyName;
    protected int $rowIndex = 0;
    protected int $totalUsers = 0;
    protected string $exportedBy;
    protected string $exportedAt;

    public function __construct(Collection $users, ?string $groupNo = null, ?string $prodyName = null)
    {
        $this->users = $users;
        $this->totalUsers = $users->count();
        $this->exportedBy = Auth::user()?->name ?? '-';
        $this->exportedAt = Carbon::now()->format('d M Y H:i');
        $this->groupNo   = $groupNo;
        // fallback: ambil dari data pertama jika tidak dikirim
        $this->prodyName = $prodyName ?: optional($users->first()->prody)->name;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        $groupLine = 'Daily Scores';
        if ($this->groupNo || $this->prodyName) {
            $groupLine = trim('Daily Scores ' . ($this->groupNo ? "Group {$this->groupNo}" : '') . ($this->prodyName ? " — {$this->prodyName}" : ''));
        }

        $metaLine = "Prodi: " . ($this->prodyName ?: '-') .
            " | Jumlah: {$this->totalUsers}" .
            " | Export by: {$this->exportedBy}" .
            " | Waktu: {$this->exportedAt}";

        return [
            [$groupLine],
            [$metaLine],
            ['', '', '', '', '', '', '', ''],
            ['No', 'Name', 'SRN', 'S1', 'S2', 'S3', 'S4', 'S5', 'Average'],
        ];
    }

    public function map($user): array
    {
        /** @var User $user */
        $breakdown = BlCompute::dailyBreakdownForUser($user->id, $user->year);
        $scores = [];
        foreach ([1, 2, 3, 4, 5] as $m) {
            $scores[] = $this->fmt($breakdown[$m]['used'] ?? null);
        }
        // Hitung rata-rata dengan missing dianggap 0
        $avgValues = [];
        foreach ([1, 2, 3, 4, 5] as $m) {
            $val = $breakdown[$m]['used'] ?? null;
            $avgValues[] = is_numeric($val) ? (float) $val : 0.0;
        }
        $avg = array_sum($avgValues) / 5;

        return [
            ++$this->rowIndex,
            $user->name,
            (string) $user->srn,
            ...$scores,
            $this->fmt($avg),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   'B' => 34,  'C' => 14,
            'D' => 12,  'E' => 12,  'F' => 12,  'G' => 12,  'H' => 12, 'I' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        $sheet->getStyle('A2:I4')->getFont()->setBold(true);
        $sheet->getStyle('A1:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A:I')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');
                $sheet->mergeCells('D3:H3');

                $lastRow = 4 + $this->users->count();
                $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(24);
                $sheet->getRowDimension(2)->setRowHeight(18);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(22);
            },
        ];
    }

    private function fmt($val): string
    {
        return is_numeric($val) ? (string) $val : '';
    }

    public function title(): string
    {
        return 'Daily S1-S5';
    }
}
