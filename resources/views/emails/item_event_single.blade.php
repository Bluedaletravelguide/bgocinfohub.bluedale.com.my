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
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }

        .event-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .badge-created {
            background-color: #4caf50;
            color: white;
        }

        .badge-status {
            background-color: #ff9800;
            color: white;
        }

        .badge-assignee {
            background-color: #2196f3;
            color: white;
        }

        .details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #666;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }

        .status-completed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-expired {
            background-color: #ffebee;
            color: #c62828;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            @if ($event === 'item.created')
                <span class="event-badge badge-created">🆕 NEW TASK</span>
            @elseif($event === 'item.status_changed')
                <span class="event-badge badge-status">🔄 STATUS UPDATE</span>
            @elseif($event === 'item.assignee_changed')
                <span class="event-badge badge-assignee">👤 REASSIGNED</span>
            @endif
            <h2 style="margin: 10px 0 0 0;">{{ $item->task }}</h2>
        </div>

        @if ($event === 'item.created')
            <p>A new task has been created and assigned to you.</p>
        @elseif($event === 'item.status_changed')
            <p>The status of this task has been updated to <strong>{{ $item->status }}</strong>.</p>
        @elseif($event === 'item.assignee_changed')
            <p>This task has been assigned to you.</p>
        @endif

        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Company:</div>
                <div class="detail-value">{{ $item->company_id ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Type:</div>
                <div class="detail-value">{{ $item->type_label ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">PIC:</div>
                <div class="detail-value">{{ $item->pic_name ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Product:</div>
                <div class="detail-value">{{ $item->product_id ?? '-' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Deadline:</div>
                <div class="detail-value" style="{{ $item->is_overdue ? 'color: #c62828; font-weight: bold;' : '' }}">
                    {{ $item->deadline ? \Carbon\Carbon::parse($item->deadline)->format('d M Y') : '-' }}
                    @if ($item->is_overdue)
                        <span style="color: #c62828;">⚠️ OVERDUE</span>
                    @endif
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge status-{{ strtolower($item->status) }}">
                        {{ $item->status }}
                    </span>
                </div>
            </div>
            @if ($item->remarks)
                <div class="detail-row">
                    <div class="detail-label">Remarks:</div>
                    <div class="detail-value">{{ $item->remarks }}</div>
                </div>
            @endif
        </div>

        {{-- Optional: Add a link to view in system --}}
        {{-- <a href="{{ url('/items/' . $item->id) }}" class="button">View Task Details</a> --}}

        <div class="footer">
            <p>This is an automated notification from BGOC Information Booth system.</p>
            <p>Sent on {{ \Carbon\Carbon::now()->format('l, d F Y H:i') }}</p>
        </div>
    </div>
</body>

</html>
