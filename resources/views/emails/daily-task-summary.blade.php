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
        h3.pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        h3.completed {
            background-color: #e8f5e9;
            color: #2e7d32;
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
        .no-items {
            padding: 20px;
            text-align: center;
            color: #999;
            font-style: italic;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .summary-box {
            display: inline-block;
            margin-right: 20px;
            padding: 10px 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .summary-expired { background-color: #ffebee; color: #c62828; }
        .summary-pending { background-color: #fff3e0; color: #ef6c00; }
        .summary-completed { background-color: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Daily Task Summary - {{ \Carbon\Carbon::now()->format('d M Y') }}</h2>

        <p>Hello <strong>{{ $data['user']->name }}</strong>,</p>
        <p>Here is your daily task summary:</p>

        <div style="margin: 20px 0;">
            @if(count($data['expired']) > 0)
            <span class="summary-box summary-expired">🔴 Expired: {{ count($data['expired']) }}</span>
            @endif
            @if(count($data['pending']) > 0)
            <span class="summary-box summary-pending">🟡 Pending: {{ count($data['pending']) }}</span>
            @endif
            @if(count($data['completed']) > 0)
            <span class="summary-box summary-completed">🟢 Completed: {{ count($data['completed']) }}</span>
            @endif
        </div>

        {{-- EXPIRED TASKS --}}
        @if(count($data['expired']) > 0)
        <h3 class="expired">🔴 EXPIRED TASKS ({{ count($data['expired']) }})</h3>
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

        {{-- PENDING TASKS --}}
        @if(count($data['pending']) > 0)
        <h3 class="pending">🟡 PENDING TASKS ({{ count($data['pending']) }})</h3>
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

        {{-- COMPLETED TASKS --}}
        @if(count($data['completed']) > 0)
        <h3 class="completed">🟢 COMPLETED TASKS ({{ count($data['completed']) }})</h3>
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
                @foreach($data['completed'] as $item)
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
            <p>This is an automated daily summary from BGOC Information Booth system.</p>
            <p>Sent on {{ \Carbon\Carbon::now()->format('l, d F Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
