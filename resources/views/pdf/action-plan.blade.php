<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Action Plan - {{ $case->client_alias }}</title>
    <style>
        @page {
            margin: 80px 40px 60px 40px; /* Top, Right, Bottom, Left */
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
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
            font-size: 12px;
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
            font-size: 12px;
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
            margin-bottom: 30px;
        }
        h2 {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-top: 30px;
            font-size: 1.4em;
        }
        h3 {
            font-size: 1.1em;
            margin-top: 20px;
        }
        .section {
            margin-bottom: 20px;
        }
        p {
            margin: 10px 0;
            text-align: justify;
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 5px;
        }
        .metadata {
            text-align: center;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 40px;
        }
        .bold {
            font-weight: bold;
        }
        .case-image-grid {
            text-align: center;
            display: block;
        }
        .case-image-card {
            display: inline-block;
            vertical-align: top;
            margin: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 260px; /* Compact width to fit side-by-side and avoid giant page breaks */
            background: #fafafa;
            page-break-inside: avoid;
            text-align: left;
        }
        .case-image-card img {
            display: block;
            width: 100%;
            max-height: 350px; /* Limit height to prevent taking up too much vertical space */
            height: auto;
        }
        .case-image-caption {
            margin: 0 0 8px 0;
            color: #444;
            font-size: 0.6em;
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
        <div style="clear: both;"></div>
    </header>

    <footer>
        <div class="footer-left">
            Date: {{ date('F j, Y') }}
        </div>
        <div class="footer-right page-number"></div>
        <div style="clear: both;"></div>
    </footer>

    <h1>Negotiation Action Plan</h1>
    
    <div class="metadata">
        <p><span class="bold">Client Alias:</span> {{ $case->client_alias }}</p>
        @if($case->case_reference)
            <p><span class="bold">Case Reference:</span> {{ $case->case_reference }}</p>
        @endif
        <p><span class="bold">Created on:</span> {{ $case->created_at ? $case->created_at->format('F j, Y') : 'N/A' }}</p>
    </div>

    @if($case->context_overview || !empty($case->case_details))
    <div class="section">
        <h2>Case Study Details</h2>
        @if($case->context_overview)
            <p><span class="bold">Context Overview:</span> {{ $case->context_overview }}</p>
        @endif
        @if(!empty($case->case_details))
            @foreach($case->case_details as $key => $value)
                @if(!empty($value) && !is_array($value))
                    <p>
                        <span class="bold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                        {{ $value }}
                    </p>
                @elseif(!empty($value) && is_array($value))
                    <p><span class="bold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span></p>
                    <ul>
                        @foreach($value as $item)
                            @if(!is_array($item) && !empty($item))
                                <li>{{ $item }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            @endforeach
        @endif
    </div>
    @endif

    @if(!empty($caseImages))
    <div class="section">
        <h2>Case Images</h2>
        <div class="case-image-grid">
            @foreach($caseImages as $image)
                <div class="case-image-card">
                    <p class="case-image-caption"><span class="bold">{{ $image['label'] }}</span></p>
                    <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}">
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(isset($plan['executive_summary']))
    <div class="section">
        <h2>Executive Summary</h2>
        <p>{{ $plan['executive_summary'] }}</p>
    </div>
    @endif

    @if(isset($plan['meeting_objectives']))
    <div class="section">
        <h2>Meeting Objectives</h2>
        <ul>
            @if(is_array($plan['meeting_objectives']))
                @foreach($plan['meeting_objectives'] as $objective)
                    <li>{{ $objective }}</li>
                @endforeach
            @else
                <li>{{ $plan['meeting_objectives'] }}</li>
            @endif
        </ul>
    </div>
    @endif

    @if(isset($plan['action_plan']))
    <div class="section">
        <h2>Action Plan</h2>
        @if(is_array($plan['action_plan']))
            @foreach($plan['action_plan'] as $key => $action)
                @if(is_array($action))
                    <h3>{{ is_numeric($key) ? 'Step ' . ($key + 1) : ucfirst(str_replace('_', ' ', $key)) }}</h3>
                    <ul>
                        @foreach($action as $subKey => $subAction)
                            <li>
                                @if(!is_numeric($subKey))
                                    <span class="bold">{{ ucfirst(str_replace('_', ' ', $subKey)) }}:</span>
                                @endif
                                {{ is_array($subAction) ? implode(', ', $subAction) : $subAction }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p>{{ $action }}</p>
                @endif
            @endforeach
        @else
            <p>{{ $plan['action_plan'] }}</p>
        @endif
    </div>
    @endif

    @if(isset($plan['strategic_recommendations']))
    <div class="section">
        <h2>Strategic Recommendations</h2>
        <ul>
            @if(is_array($plan['strategic_recommendations']))
                @foreach($plan['strategic_recommendations'] as $recommendation)
                    <li>
                        @if(is_array($recommendation))
                            @foreach($recommendation as $key => $value)
                                <span class="bold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span> {{ is_array($value) ? implode(', ', $value) : $value }}<br>
                            @endforeach
                        @else
                            {{ $recommendation }}
                        @endif
                    </li>
                @endforeach
            @else
                <li>{{ $plan['strategic_recommendations'] }}</li>
            @endif
        </ul>
    </div>
    @endif

    @if(isset($plan['critical_success_factors']))
    <div class="section">
        <h2>Critical Success Factors</h2>
        <ul>
            @if(is_array($plan['critical_success_factors']))
                @foreach($plan['critical_success_factors'] as $factor)
                    <li>{{ $factor }}</li>
                @endforeach
            @else
                <li>{{ $plan['critical_success_factors'] }}</li>
            @endif
        </ul>
    </div>
    @endif

    @if(isset($plan['plan_b']))
    <div class="section">
        <h2>Plan B (Contingency)</h2>
        <p>{{ is_array($plan['plan_b']) ? implode(' ', $plan['plan_b']) : $plan['plan_b'] }}</p>
    </div>
    @endif

    @if(isset($plan['user_question_answer']) && !empty($plan['user_question_answer']))
        @php
            $qAnswer = $plan['user_question_answer'];
            $questionText = is_array($qAnswer) ? ($qAnswer['question'] ?? '') : '';
            $answerText   = is_array($qAnswer) ? ($qAnswer['answer'] ?? '') : (is_string($qAnswer) ? $qAnswer : '');
        @endphp
        @if(!empty($answerText))
        <div class="section">
            <h2>User Question & Analysis</h2>
            @if(!empty($questionText))
                <p><span class="bold">Question:</span> {{ $questionText }}</p>
            @endif
            <p><span class="bold">Answer:</span> {{ $answerText }}</p>
        </div>
        @endif
    @endif

</body>
</html>