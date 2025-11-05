<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class AdminUserController extends Controller
{
    /**
     * Store a new user/admin (existing method - unchanged)
     */
    public function store(Request $request)
    {
        // 1) Validasi tegas biar ketahuan kalau gagal
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:8'],
            'role'     => ['required','in:admin,user'],
        ]);

        try {
            return DB::transaction(function () use ($data) {
                // 2) Buat user (hash password!)
                $user = User::create([
                    'name'     => $data['name'],
                    'email'    => $data['email'],
                    'password' => Hash::make($data['password']),
                ]);

                // 3) Pastikan role ada, lalu assign
                $role = Role::where('name', $data['role'])->firstOrFail();
                $user->syncRoles([$role->name]);

                return redirect()
                    ->back()
                    ->with('success', 'User created: '.$user->email);
            });
        } catch (\Throwable $e) {
            Log::error('Admin create user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->except(['password']),
            ]);

            return redirect()
                ->back()
                ->withInput($request->except('password'))
                ->withErrors(['general' => 'Create user failed: '.$e->getMessage()]);
        }
    }

    /**
     * 🆕 Get all users (JSON for View Users modal)
     */
    public function index()
    {
        try {
            $users = User::orderBy('created_at', 'desc')
                ->get(['id', 'name', 'email', 'password', 'created_at']);

            // Add role from Spatie
            $users = $users->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'password'   => $user->password,
                    'role'       => $user->getRoleNames()->first() ?? 'user',
                    'created_at' => $user->created_at,
                ];
            });

            return response()->json($users);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Failed to load users: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 Export all users to Excel
     */
    public function export()
    {
        try {
            $users = User::orderBy('created_at', 'desc')
                ->get(['id', 'name', 'email', 'password', 'created_at']);

            // Add role from Spatie
            $users = $users->map(function ($user) {
                return [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'password'   => $user->password,
                    'role'       => $user->getRoleNames()->first() ?? 'user',
                    'created_at' => $user->created_at,
                ];
            });

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title
            $sheet->setTitle('Users');

            // Headers
            $headers = ['ID', 'Name', 'Email', 'Password Hash', 'Role', 'Created At'];
            $sheet->fromArray($headers, null, 'A1');

            // Style headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '1F2937'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E5E7EB'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ];
            $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

            // Set header row height
            $sheet->getRowDimension(1)->setRowHeight(25);

            // Data rows
            $row = 2;
            foreach ($users as $user) {
                $sheet->setCellValue('A' . $row, $user['id']);
                $sheet->setCellValue('B' . $row, $user['name']);
                $sheet->setCellValue('C' . $row, $user['email']);
                $sheet->setCellValue('D' . $row, $user['password']);
                $sheet->setCellValue('E' . $row, strtoupper($user['role']));
                $sheet->setCellValue('F' . $row, $user['created_at']->format('Y-m-d H:i:s'));
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add borders to all data
            if ($row > 2) {
                $sheet->getStyle('A2:F' . ($row - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);
            }

            // Freeze header row
            $sheet->freezePane('A2');

            // Generate filename
            $filename = 'users_' . date('Ymd_His') . '.xlsx';

            // Create writer
            $writer = new Xlsx($spreadsheet);

            // Set headers for download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');

            // Output to browser
            $writer->save('php://output');
            exit;

        } catch (\Throwable $e) {
            Log::error('Export users failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
