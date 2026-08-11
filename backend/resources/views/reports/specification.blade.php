<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2933; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 24px; margin-bottom: 8px; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        .generated-at { color: #6b7280; font-size: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .field-table td:first-child { font-weight: bold; width: 25%; background-color: #f9fafb; }
        .summary-badges span { display: inline-block; margin-right: 12px; padding: 2px 8px; border-radius: 4px; background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="generated-at">Generated {{ now()->toDayDateTimeString() }}</p>

    <h2>Program</h2>
    <table class="field-table">
        @foreach ($program as $field => $value)
            <tr>
                <td>{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
    </table>

    <h2>Program Objectives ({{ count($objectives) }})</h2>
    <table>
        <thead><tr><th>Code</th><th>Statement</th></tr></thead>
        <tbody>
            @forelse ($objectives as $objective)
                <tr><td>{{ $objective['code'] }}</td><td>{{ $objective['statement'] }}</td></tr>
            @empty
                <tr><td colspan="2">No objectives defined.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Program Learning Outcomes ({{ count($learning_outcomes) }})</h2>
    <table>
        <thead><tr><th>Code</th><th>Statement</th><th>Category</th></tr></thead>
        <tbody>
            @forelse ($learning_outcomes as $outcome)
                <tr>
                    <td>{{ $outcome['code'] }}</td>
                    <td>{{ $outcome['statement'] }}</td>
                    <td>{{ $outcome['category'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No learning outcomes defined.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>PO-PLO Matrix Summary</h2>
    <p class="summary-badges">
        <span>{{ $matrix_summary['total_pairs'] }} total pairs</span>
        <span>{{ $matrix_summary['auto'] }} auto-generated</span>
        <span>{{ $matrix_summary['manual'] }} manually confirmed</span>
        <span>{{ $matrix_summary['unmapped'] }} unmapped</span>
    </p>
</body>
</html>
