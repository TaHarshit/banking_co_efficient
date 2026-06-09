<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Action Plan - {{ $case->client_alias }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 20px;
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
    </style>
</head>
<body>

    <h1>Negotiation Action Plan</h1>
    
    <div class="metadata">
        <p><span class="bold">Client Alias:</span> {{ $case->client_alias }}</p>
        @if($case->case_reference)
            <p><span class="bold">Case Reference:</span> {{ $case->case_reference }}</p>
        @endif
        <p><span class="bold">Created on:</span> {{ $case->created_at ? $case->created_at->format('F j, Y') : 'N/A' }}</p>
        <p><span class="bold">PDF Generated on:</span> {{ date('F j, Y') }}</p>
    </div>

    @if($case->context_overview || !empty($case->case_details))
    <div class="section">
        <h2>Case Study Details</h2>
        @if($case->context_overview)
            <p><span class="bold">Context Overview:</span> {{ $case->context_overview }}</p>
        @endif
        @if(!empty($case->case_details))
            @foreach($case->case_details as $key => $value)
                @if(!empty($value))
                    <p>
                        <span class="bold">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                        @if(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value }}
                        @endif
                    </p>
                @endif
            @endforeach
        @endif
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

</body>
</html>
