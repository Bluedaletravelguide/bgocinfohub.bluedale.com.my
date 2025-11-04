<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
        }
        h2 {
            color: #1976d2;
            border-bottom: 3px solid #1976d2;
            padding-bottom: 10px;
        }
        h3 {
            margin-top: 30px;
            padding: 10px;
            border-radius: 4px;
        }
        h3.expired {
            background-color: #ffebee;
            color: #c62828;
        }
        h3.today {
            background-color: #fff8e1;
            color: #f57c00;
        }
        h3.tomorrow {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        h3.pending {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th {
            background-color: #f5f5f5;
            padding: 12px 8px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            font-size: 13px;
        }
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .summary-box {
            display: inline-block;
            margin-right: 20px;
            padding: 10px 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .summary-expired { background-color: #ffebee; color: #c62828; }
        .summary-today { background-color: #fff8e1; color: #f57c00; }
        .summary-tomorrow { background-color: #e3f2fd; color: #1976d2; }
        .summary-pending { background-color: #f3e5f5; color: #7b1fa2; }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Outstanding Task Summary - {{ \Carbon\Carbon::now()->format('d M Y') }}</h2>

        <p>Hello <strong>{{ $data['user']->name }}</strong>,</p>
        <p>Here are your assigned tasks:</p>

        <div style="margin: 20px 0;">
            @if($data['expired']->count() > 0)
            <span class="summary-box summary-expired">🔴 Overdue: {{ $data['expired']->count() }}</span>
            @endif
            @if($data['due_today']->count() > 0)
            <span class="summary-box summary-today">🟡 Due Today: {{ $data['due_today']->count() }}</span>
            @endif
            @if($data['due_tomorrow']->count() > 0)
            <span class="summary-box summary-tomorrow">🔵 Due Tomorrow: {{ $data['due_tomorrow']->count() }}</span>
            @endif
            @if($data['pending']->count() > 0)
            <span class="summary-box summary-pending">⚪ Upcoming: {{ $data['pending']->count() }}</span>
            @endif
        </div>

        {{-- OVERDUE TASKS --}}
        @if($data['expired']->count() > 0)
        <h3 class="expired">🔴 OVERDUE TASKS ({{ $data['expired']->count() }})</h3>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>PIC</th>
                    <th>Product</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['expired'] as $item)
                <tr>
                    <td><strong>{{ $item->task }}</strong></td>
                    <td>{{ $item->company_id ?? '-' }}</td>
                    <td>{{ $item->type_label ?? '-' }}</td>
                    <td>{{ $item->pic_name ?? '-' }}</td>
                    <td>{{ $item->product_id ?? '-' }}</td>
                    <td style="color: #c62828; font-weight: bold;">
                        {{ $item->deadline ? \Carbon\Carbon::parse($item->deadline)->format('d M Y') : '-' }}
                    </td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->remarks ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- DUE TODAY --}}
        @if($data['due_today']->count() > 0)
        <h3 class="today">🟡 DUE TODAY ({{ $data['due_today']->count() }})</h3>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>PIC</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['due_today'] as $item)
                <tr>
                    <td><strong>{{ $item->task }}</strong></td>
                    <td>{{ $item->company_id ?? '-' }}</td>
                    <td>{{ $item->type_label ?? '-' }}</td>
                    <td>{{ $item->pic_name ?? '-' }}</td>
                    <td>{{ $item->product_id ?? '-' }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->remarks ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- DUE TOMORROW --}}
        @if($data['due_tomorrow']->count() > 0)
        <h3 class="tomorrow">🔵 DUE TOMORROW ({{ $data['due_tomorrow']->count() }})</h3>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>PIC</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['due_tomorrow'] as $item)
                <tr>
                    <td><strong>{{ $item->task }}</strong></td>
                    <td>{{ $item->company_id ?? '-' }}</td>
                    <td>{{ $item->type_label ?? '-' }}</td>
                    <td>{{ $item->pic_name ?? '-' }}</td>
                    <td>{{ $item->product_id ?? '-' }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->remarks ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- UPCOMING TASKS --}}
        @if($data['pending']->count() > 0)
        <h3 class="pending">⚪ UPCOMING TASKS ({{ $data['pending']->count() }})</h3>
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Company</th>
                    <th>Type</th>
                    <th>PIC</th>
                    <th>Product</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['pending'] as $item)
                <tr>
                    <td><strong>{{ $item->task }}</strong></td>
                    <td>{{ $item->company_id ?? '-' }}</td>
                    <td>{{ $item->type_label ?? '-' }}</td>
                    <td>{{ $item->pic_name ?? '-' }}</td>
                    <td>{{ $item->product_id ?? '-' }}</td>
                    <td>
                        {{ $item->deadline ? \Carbon\Carbon::parse($item->deadline)->format('d M Y') : '-' }}
                    </td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->remarks ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="footer">
            <p>This is your personal task summary from BGOC Information Booth system.</p>
            <p>Sent on {{ \Carbon\Carbon::now()->format('l, d F Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
