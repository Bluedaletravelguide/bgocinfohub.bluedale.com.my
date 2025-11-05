<?php

namespace App\Exports;

use App\Models\Item;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ItemsExport implements FromCollection, WithMapping, WithEvents, ShouldAutoSize
{
    protected $filters;
protected $user;
protected $isAdmin;

public function __construct(array $filters = [], ?User $user = null, bool $isAdmin = false)
{
    $this->filters = $filters;
    $this->user = $user ?? Auth::user();
    $this->isAdmin = $isAdmin;
}

public function collection()
{
    $q = Item::query();

    // 🔒 ENFORCE USER OWNERSHIP (kecuali admin)
    if (!$this->isAdmin) {
        // Ambil data user yang login
        $userEmail = strtolower($this->user->email);
        $userName = strtolower($this->user->name);

        // Filter: cuma tampil data yang ada hubungannya dengan user ini
        $q->where(function($subQuery) use ($userEmail, $userName) {
            // Match by email (exact dan partial)
            $subQuery->whereRaw('LOWER(assign_to_id) LIKE ?', ["%{$userEmail}%"])
                     ->orWhereRaw('LOWER(assign_by_id) LIKE ?', ["%{$userEmail}%"])
                     // Match by name (exact dan partial)
                     ->orWhereRaw('LOWER(assign_to_id) LIKE ?', ["%{$userName}%"])
                     ->orWhereRaw('LOWER(assign_by_id) LIKE ?', ["%{$userName}%"]);
        });
    }
    // Kalau admin, tidak ada filter (ambil semua data)

    // Apply date filters
    if (!empty($this->filters['date_in_from'])) {
        $q->whereDate('date_in', '>=', $this->filters['date_in_from']);
    }

    // Apply other filters
    if (!empty($this->filters['type_label'])) {
        $q->where('type_label', $this->filters['type_label']);
    }
    if (!empty($this->filters['company_id'])) {
        $q->where('company_id', $this->filters['company_id']);
    }
    if (!empty($this->filters['task'])) {
        $q->where('task', 'like', '%' . $this->filters['task'] . '%');
    }
    if (!empty($this->filters['pic_name'])) {
        $q->where('pic_name', 'like', '%' . $this->filters['pic_name'] . '%');
    }
    if (!empty($this->filters['product_id'])) {
        $q->where('product_id', $this->filters['product_id']);
    }
    if (!empty($this->filters['status'])) {
        $q->where('status', $this->filters['status']);
    }

    // Get data
    $items = $q->get();

    // Define status order
    $statusOrder = ['Expired', 'Pending', 'In Progress', 'Completed'];

    // Group and sort
    $grouped = collect();

    foreach ($statusOrder as $status) {
        $statusItems = $items->filter(function($item) use ($status) {
            return strcasecmp($item->status, $status) === 0;
        })->sortBy(function($item) {
            return $item->deadline ? Carbon::parse($item->deadline)->timestamp : PHP_INT_MAX;
        })->values();

        if ($statusItems->isNotEmpty()) {
            $grouped->push((object)[
                'is_section_header' => true,
                'section_title' => strtoupper($status),
            ]);

            $grouped->push((object)[
                'is_column_header' => true,
            ]);

            foreach ($statusItems as $item) {
                $grouped->push($item);
            }
        }
    }

    return $grouped;
}


    public function map($item): array
    {
        // Check if this is a section header
        if (isset($item->is_section_header) && $item->is_section_header) {
            return [
                $item->section_title,
                '', '', '', '', '', '', '', '', '', ''
            ];
        }

        // Check if this is a column header row
        if (isset($item->is_column_header) && $item->is_column_header) {
            return [
                'DATE IN',
                'DEADLINE',
                'ASSIGN BY',
                'ASSIGN TO',
                'COMPANY',
                'PIC',
                'TASK',
                'DEPARTMENT',
                'REMARKS',
                'INTERNAL/CLIENT',
                'STATUS',
            ];
        }

        // 🔴 FIX: Handle both ID and legacy text columns
        $assignBy = $item->assign_by_id ?? $item->assign_by ?? '';
        $assignTo = $item->assign_to_id ?? $item->assign_to ?? '';

        return [
            $item->date_in ? Carbon::parse($item->date_in)->format('d/m/Y') : '',
            $item->deadline ? Carbon::parse($item->deadline)->format('d/m/Y') : '',
            $assignBy,
            $assignTo,
            $item->company_id ?? '',
            $item->pic_name ?? '',
            $item->task ?? '',
            $item->product_id ?? '',
            $item->remarks ?? '',
            $item->type_label ?? '',
            $item->status ?? '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'K';

                // Insert 2 rows for title and timestamp
                $sheet->insertNewRowBefore(1, 2);

                // Title row (Row 1) - Left aligned
                $sheet->setCellValue('A1', 'BGOC INFORMATION HUB');
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF200'],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                // Timestamp row (Row 2) - Red color
                $sheet->setCellValue('A2', 'Generated at: ' . now()->timezone('Asia/Kuala_Lumpur')->format('d/m/Y H:i'));
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => [
                        'size' => 11,
                        'color' => ['rgb' => 'FF0000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // Style section headers and column headers (starting from row 3)
                $highestRow = $sheet->getHighestRow();
                for ($row = 3; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell("A{$row}")->getValue();

                    // Check if this is a section header (all caps status labels)
                    if (in_array($cellValue, ['PENDING', 'IN PROGRESS', 'COMPLETED', 'EXPIRED'])) {
                        $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'size' => 16,
                                'color' => ['rgb' => 'FF0000'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_LEFT,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'B4C7E7'],
                            ],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                    }
                    // Check if this is a column header row (has 'DATE IN' in first cell)
                    elseif ($cellValue === 'DATE IN') {
                        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                                'size' => 11,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                            ],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '22255B'],
                            ],
                        ]);
                        $sheet->getRowDimension($row)->setRowHeight(18);
                    }
                }
            },
        ];
    }
}
