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

class StatistikPerProdiExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths,
    WithStyles
{
    /** @var Collection<int, array{prodi:string,total:int,persen:float}> */
    protected Collection $rows;
    protected string $titleLine;
    protected string $periodLabel;
    protected int $rowIndex = 0;

    public function __construct(Collection $rows, string $titleLine, string $periodLabel)
    {
        $this->rows        = $rows;
        $this->titleLine   = $titleLine;
        $this->periodLabel = $periodLabel;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            ['UPT Bahasa Universitas Muhammadiyah Metro'],
            [$this->titleLine],
            [$this->periodLabel],
            ['No', 'Program Studi', 'Jumlah', 'Persentase (%)'],
        ];
    }

    public function map($row): array
    {
        return [
            ++$this->rowIndex,
            $row['prodi'],
            $row['total'],
            $row['persen'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 40,
            'C' => 12,
            'D' => 16,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D3')->getFont()->setBold(true);
        $sheet->getStyle('A1:D3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:D3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A4:D4')->getFont()->setBold(true);
        $sheet->getStyle('A1:D4')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (range(1, 3) as $row) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                }

                $lastRow = 4 + $this->rows->count();
                if ($lastRow >= 4) {
                    $sheet->getStyle("A4:D{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                        ],
                    ]);
                }

                $sheet->getRowDimension(1)->setRowHeight(18);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(18);
                $sheet->getRowDimension(4)->setRowHeight(20);
            },
        ];
    }
}
