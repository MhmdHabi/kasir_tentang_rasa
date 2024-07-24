<?php

namespace App\Exports;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DataPenjualanExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Purchase::with(['purchaseItems.product'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Pengunjung',
            'Nama Produk',
            'Quantity',
            'Total Harga',
            'Uang Pembayaran',
            'Kembalian',
            'Harga Satuan',
            'Waktu Pemesanan',
        ];
    }

    /**
     * @var Purchase $purchase
     */
    public function map($purchase): array
    {
        $mappedData = [];

        foreach ($purchase->purchaseItems as $item) {
            $mappedData[] = [
                $purchase->id,
                $purchase->nama_pengunjung,
                $item->product->name,
                $item->quantity,
                $purchase->total_amount,
                $purchase->amount_paid,
                $purchase->change,
                $item->product->price,
                $purchase->created_at,
            ];
        }

        return $mappedData;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text and center alignment.
            1    => ['font' => ['bold' => true], 'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]],
        ];
    }

    /**
     * Register events.
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Apply border styles to all cells.
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

                // Set auto width for columns
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Center align the headings
                $sheet->getStyle('A1:' . $highestColumn . '1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // Center align the ID column
                $sheet->getStyle('A2:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
