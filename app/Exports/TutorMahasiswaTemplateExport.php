<?php

namespace App\Exports;

use App\Models\User;
use App\Support\BlCompute;
use App\Support\BlGrading;
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

class TutorMahasiswaTemplateExport implements
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
        // JANGAN SORTING LAGI DI SINI.
        // Terima apa adanya dari Controller.
        $this->users = $users; 

        $this->totalUsers = $users->count();
        $this->exportedBy = Auth::user()?->name ?? '-';
        $this->exportedAt = Carbon::now()->format('d M Y H:i');
        $this->groupNo   = $groupNo;
        $this->prodyName = $prodyName ?: optional($users->first()->prody)->name;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        $groupLine = 'Group';
        if ($this->groupNo || $this->prodyName) {
            $groupLine = trim('Group ' . ($this->groupNo ?? '') . ($this->prodyName ? " — {$this->prodyName}" : ''));
        }

        $metaLine = "Prodi: " . ($this->prodyName ?: '-') .
            " | Jumlah: {$this->totalUsers}" .
            " | Export by: {$this->exportedBy}" .
            " | Waktu: {$this->exportedAt}";

        return [
            [$groupLine],
            [$metaLine],
            ['', '', '', '', '', '', '', ''],
            ['No', 'Name', 'SRN', 'Attendance', 'Daily', 'Final Test', 'SCORE', 'ALPHABETICAL SCORE'],
        ];
    }

    public function map($user): array
    {
        /** @var User $user */
        $daily = BlCompute::dailyAvgForUser($user->id, $user->year);
        $att   = optional($user->basicListeningGrade)->attendance;
        // Final Test: pakai nilai di grade; fallback ke attempt session 6 jika ada.
        $final = optional($user->basicListeningGrade)->final_test;
        if (!is_numeric($final)) {
            $finalAttempt = $user->basicListeningAttempts
                ->where('session_id', 6)
                ->sortByDesc('submitted_at')
                ->first();
            $final = $finalAttempt?->score;
        }

        $attVal   = is_numeric($att)   ? (float) $att   : null;
        $dailyVal = is_numeric($daily) ? (float) $daily : null;
        $finalVal = is_numeric($final) ? (float) $final : null;

        $cachedNumeric = optional($user->basicListeningGrade)->final_numeric_cached;
        $cachedLetter  = optional($user->basicListeningGrade)->final_letter_cached;

        // Attendance wajib ada; jika tidak ada salah satu komponen, jangan tampilkan numeric/letter.
        if ($attVal === null || $dailyVal === null || $finalVal === null) {
            $finalNumeric = null;
            $finalLetter  = '';
        } else {
            $finalNumeric = is_numeric($cachedNumeric)
                ? (float) $cachedNumeric
                : round(($attVal + $dailyVal + $finalVal) / 3, 2);

            $finalLetter = $cachedLetter ?: BlGrading::letter($finalNumeric);
        }

        return [
            ++$this->rowIndex,            
            $user->name,                  
            (string) $user->srn,          
            $this->fmt($att),             
            $this->fmt($daily),           
            $this->fmt($final),           
            $this->fmt($finalNumeric),    
            $finalLetter,                 
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   'B' => 34,  'C' => 14,  'D' => 13,
            'E' => 13,  'F' => 13,  'G' => 12,  'H' => 22,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A2:H3')->getFont()->setBold(true);
        $sheet->getStyle('A1:H3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:H1')->getAlignment()->setWrapText(true);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:H1'); 
                $sheet->mergeCells('A2:H2'); 
                $sheet->mergeCells('D3:F3'); 

                $lastRow = 4 + $this->users->count(); 
                $sheet->getStyle("A4:H{$lastRow}")->applyFromArray([
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
        return 'Rekap';
    }
}
