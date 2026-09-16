<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Cases Strategic Summary - {{ $clientAlias }}</title>
    <style>
        @page {
            margin: 80px 40px 60px 40px; /* Top, Right, Bottom, Left */
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            font-size: 12px;
        }
        header {
            position: fixed;
            top: -50px;
            left: 0px;
            right: 0px;
            height: 50px;
        }
        header img {
            max-height: 40px;
        }
        .header-left {
            float: left;
            margin-top: 10px;
            font-size: 11px;
            color: #666;
        }
        .header-right {
            float: right;
        }
        footer {
            position: fixed;
            bottom: -40px;
            left: 0px;
            right: 0px;
            height: 30px;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        .footer-left {
            float: left;
        }
        .footer-right {
            float: right;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        h1, h2, h3 {
            color: #0056b3;
        }
        h1 {
            text-align: center;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        h2 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 12px;
            font-size: 1.3em;
            color: #0056b3;
        }
        h3 {
            font-size: 1.05em;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #222;
        }
        .section {
            margin-bottom: 20px;
        }
        p {
            margin: 8px 0;
            text-align: justify;
        }
        ul {
            margin: 8px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 5px;
        }
        .metadata {
            text-align: center;
            color: #555;
            font-size: 0.95em;
            margin-bottom: 30px;
            background: #f9fbfd;
            border: 1px solid #e1e8ed;
            border-radius: 5px;
            padding: 10px 15px;
        }
        .metadata p {
            text-align: center;
            margin: 4px 0;
        }
        .bold {
            font-weight: bold;
        }
        .red-flag-card {
            background-color: #fff8f8;
            border: 1px solid #f5c6cb;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .risk-badge {
            display: inline-block;
            padding: 2px 7px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
            background-color: #dc3545;
            border-radius: 3px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .case-card {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 10px 14px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .case-card-header {
            border-bottom: 1px solid #e8e8e8;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .case-ref {
            font-weight: bold;
            color: #0056b3;
            font-size: 1.05em;
            float: left;
        }
        .case-meta {
            float: right;
            font-size: 0.9em;
            color: #666;
        }
        .tag {
            display: inline-block;
            background: #edf2f7;
            color: #4a5568;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            margin-right: 4px;
            margin-bottom: 3px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    @php
        $logoPath = public_path('assets/img/logo.png');
        $logoBase64 = '';
        if(file_exists($logoPath)){
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        }
    @endphp

    <header>
        <div class="header-left page-number"></div>
        <div class="header-right">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
        <div class="clear"></div>
    </header>

    <footer>
        <div class="footer-left">
            Date: {{ isset($generatedAt) ? $generatedAt->format('F j, Y') : date('F j, Y') }}
        </div>
        <div class="footer-right page-number"></div>
        <div class="clear"></div>
    </footer>

    <h1>Client Strategic Overview & Summary</h1>
    
    <div class="metadata">
        <p><span class="bold">Client Alias:</span> {{ $clientAlias }}</p>
        @if(!empty($clientId))
            <p><span class="bold">Client ID:</span> {{ $clientId }}</p>
        @endif
        <p><span class="bold">Total Historical Cases:</span> {{ $totalCases ?? ($summary['total_cases_analyzed'] ?? 'N/A') }}</p>
        <p><span class="bold">Generated on:</span> {{ isset($generatedAt) ? $generatedAt->format('F j, Y, g:i a') : date('F j, Y') }}</p>
    </div>

    {{-- Executive Summary --}}
    @if(!empty($summary['executive_summary']))
    <div class="section">
        <h2>Executive Summary</h2>
        <p>{{ $summary['executive_summary'] }}</p>
    </div>
    @endif

    {{-- Client Profile and Evolution --}}
    @if(!empty($summary['client_profile_and_evolution']))
    <div class="section">
        <h2>Client Profile & Negotiation Evolution</h2>
        <p>{{ $summary['client_profile_and_evolution'] }}</p>
    </div>
    @endif

    {{-- Client Red Flags --}}
    @if(!empty($summary['client_red_flags']) && is_array($summary['client_red_flags']))
    <div class="section">
        <h2 style="color: #c53030; border-bottom: 1px solid #feb2b2;">Client Red Flags & Risk Triggers</h2>
        @foreach($summary['client_red_flags'] as $flag)
            @if(is_array($flag))
                <div class="red-flag-card">
                    @if(!empty($flag['risk_level']))
                        <span class="risk-badge">{{ $flag['risk_level'] }} RISK</span>
                    @endif
                    @if(!empty($flag['area']))
                        <p style="margin: 3px 0;"><span class="bold">Area:</span> {{ $flag['area'] }}</p>
                    @endif
                    @if(!empty($flag['description']))
                        <p style="margin: 3px 0;"><span class="bold">Issue:</span> {{ $flag['description'] }}</p>
                    @endif
                    @if(!empty($flag['recommended_approach']))
                        <p style="margin: 3px 0; color: #2c5282;"><span class="bold">Recommended Approach:</span> {{ $flag['recommended_approach'] }}</p>
                    @endif
                </div>
            @else
                <div class="red-flag-card">
                    <p style="margin: 2px 0;"><strong>&#9888;</strong> {{ $flag }}</p>
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Recurring Patterns & Objections --}}
    @if(!empty($summary['recurring_patterns_and_objections']) && is_array($summary['recurring_patterns_and_objections']))
    <div class="section">
        <h2>Recurring Patterns & Objections</h2>
        <ul>
            @foreach($summary['recurring_patterns_and_objections'] as $pattern)
                <li>{{ is_array($pattern) ? json_encode($pattern) : $pattern }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Proven Strategies & Successes --}}
    @if(!empty($summary['proven_strategies_and_successes']) && is_array($summary['proven_strategies_and_successes']))
    <div class="section">
        <h2>Proven Strategies & High-Impact Successes</h2>
        <ul>
            @foreach($summary['proven_strategies_and_successes'] as $strategy)
                <li>{{ is_array($strategy) ? json_encode($strategy) : $strategy }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Pitfalls & Lessons Learned --}}
    @if(!empty($summary['pitfalls_and_lessons_learned']) && is_array($summary['pitfalls_and_lessons_learned']))
    <div class="section">
        <h2>Pitfalls & Lessons Learned</h2>
        <ul>
            @foreach($summary['pitfalls_and_lessons_learned'] as $lesson)
                <li>{{ is_array($lesson) ? json_encode($lesson) : $lesson }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Strategic Recommendations for Future --}}
    @if(!empty($summary['strategic_recommendations_for_future']) && is_array($summary['strategic_recommendations_for_future']))
    <div class="section">
        <h2>Strategic Recommendations for Upcoming Negotiations</h2>
        <ol style="margin: 8px 0; padding-left: 20px;">
            @foreach($summary['strategic_recommendations_for_future'] as $rec)
                <li style="margin-bottom: 6px;">{{ is_array($rec) ? json_encode($rec) : $rec }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    {{-- Cases Overview / Breakdown --}}
    @if(!empty($summary['cases_overview']) && is_array($summary['cases_overview']))
    <div class="section">
        <h2>Historical Cases Breakdown</h2>
        @foreach($summary['cases_overview'] as $caseItem)
            @php
                $cRef = $caseItem['case_reference'] ?? ('Case #' . ($caseItem['case_id'] ?? ''));
                $cDate = $caseItem['date'] ?? null;
                $cRating = $caseItem['rating_or_outcome'] ?? null;
            @endphp
            <div class="case-card">
                <div class="case-card-header">
                    <span class="case-ref">{{ $cRef }}</span>
                    <span class="case-meta">
                        @if($cDate) Date: {{ $cDate }} @endif
                        @if($cRating) &bull; Outcome/Rating: <strong style="color: #d69e2e;">{{ $cRating }}</strong> @endif
                    </span>
                    <div class="clear"></div>
                </div>

                @if(!empty($caseItem['situation_summary']))
                    <p style="margin: 4px 0;"><span class="bold">Situation:</span> {{ $caseItem['situation_summary'] }}</p>
                @endif

                @if(!empty($caseItem['core_challenges']))
                    <p style="margin: 4px 0;"><span class="bold">Core Challenges:</span></p>
                    <ul style="margin: 3px 0 8px 0;">
                        @if(is_array($caseItem['core_challenges']))
                            @foreach($caseItem['core_challenges'] as $challenge)
                                <li>{{ $challenge }}</li>
                            @endforeach
                        @else
                            <li>{{ $caseItem['core_challenges'] }}</li>
                        @endif
                    </ul>
                @endif

                @if(!empty($caseItem['action_plan_summary']))
                    <p style="margin: 4px 0;"><span class="bold">Action Plan:</span> {{ $caseItem['action_plan_summary'] }}</p>
                @endif

                @if(!empty($caseItem['key_techniques_applied']))
                    <p style="margin: 5px 0 2px 0;"><span class="bold">Techniques Applied:</span>
                        @if(is_array($caseItem['key_techniques_applied']))
                            @foreach($caseItem['key_techniques_applied'] as $technique)
                                <span class="tag">{{ $technique }}</span>
                            @endforeach
                        @else
                            <span>{{ $caseItem['key_techniques_applied'] }}</span>
                        @endif
                    </p>
                @endif
            </div>
        @endforeach
    </div>
    @endif

</body>
</html>
