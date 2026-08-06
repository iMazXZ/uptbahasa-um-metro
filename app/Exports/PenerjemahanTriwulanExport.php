<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PenerjemahanTriwulanExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths,
    WithStyles
{
    /** @var \Illuminate\Support\Collection<int,\App\Models\Penerjemahan> */
    protected Collection $rows;
    protected string $titleLine;
    protected string $periodLabel;
    protected string $keterangan;
    protected int $rowIndex = 0;

    public function __construct(Collection $rows, string $titleLine, string $periodLabel, string $keterangan = 'Abstrak')
    {
        $this->rows        = $rows;
        $this->titleLine   = $titleLine;
        $this->periodLabel = $periodLabel;
        $this->keterangan  = $keterangan;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['Lampiran'],
            [$this->titleLine],
            ['DAFTAR PENERJEMAHAN'],
            [$this->periodLabel],
            ['No', 'Nama', 'NPM', 'Keterangan'],
        ];
    }

    public function map($row): array
    {
        $user = $row->users;

        return [
            ++$this->rowIndex,
            $user?->name ?? '—',
            $user?->srn ?? '—',
            $this->keterangan,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 32,
            'C' => 16,
            'D' => 18,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D4')->getFont()->setBold(true);
        $sheet->getStyle('A1:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:D4')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A5:D5')->getFont()->setBold(true);
        $sheet->getStyle('A1:D5')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge header rows
                foreach (range(1, 4) as $row) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                }

                $lastRow = 5 + $this->rows->count();
                if ($lastRow >= 5) {
                    $sheet->getStyle("A5:D{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                }

                $sheet->getRowDimension(1)->setRowHeight(18);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(20);
                $sheet->getRowDimension(5)->setRowHeight(20);
            },
        ];
    }
}
