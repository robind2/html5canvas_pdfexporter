@extends('dashboard.base')

{!! csrf_field() !!}

@set('page_title', 'Insight Report, Build Widgets: ' . $insight_report->name)

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/", NavActions::LABEL_BACK),
        NavActions::action("/audit-trails/filter/insight_reports/{$insight_report->id}", NavActions::LABEL_AUDIT_TRAIL)
    ) !!}
@stop

@push('css')
    <style type="text/css">
        #chart_type_option:hover {
            background-color: #eee;
        }

        .popover {
            width: 400px;
            height: 600px;
            color: #636363;
        }

        .export_pdf {
            position: relative;
            min-width: 250px;
            height: 32px;
            margin-bottom: 10px;
        }

        .picklist_grid {
            position: relative;
            width: 450px;
            height: 722px;
            min-width: 450px;
            max-width: 450px;
            min-height: 722px;
            max-height: 722px;
        }

        .tab-content {
            position: relative;
            padding: 0px;
        }

        .panel-heading {
            position: relative;
            margin-top: 10px;
            width: 546px;
            min-width: 546px;
            background: none;
            border-bottom: none;
        }

        .panel-body {
            background-color: #eeeeee;
            margin-top: 10px;
            padding: 0px;
            width: 546px;
            height: 722px;
            min-width: 546px;
            max-width: 546px;
            min-height: 722px;
            max-height: 722px;
        }

        .grid_size {
            position: relative;
            background-color: #ffffff;
            width: 546px;
            height: 722px;
            min-width: 546px;
            max-width: 546px;
            min-height: 722px;
            max-height: 722px;
        }

        .grid {
            /*margin-top: 9px;*/
            border: 1px dashed #8e9be2;
            position: relative;
            background-color: #eeeeee;
            width: 546px;
            height: 722px;
            min-width: 546px;
            max-width: 546px;
            min-height: 722px;
            max-height: 722px;
        }

        .grid > div {
            background: #ffffff;
            height: 54px;
            position: absolute;
            width: 54px;
        }

        .grid .ss-placeholder-child {
            background: transparent;
            border: 1px dashed red;
        }

        .inactive_menu_btn {
            position: relative;
            min-width: 248px;
            height: 32px;
        }
    </style>
@endpush


@section('content')
    <div style="display: none;" id="report_id" data-report-id-value="{{ $report_id }}"></div>
    <div class="col-md-3">
        <div>
            <form id="invisible_form" action="/insight-reports/ajax-html-to-pdf" method="post" target="_blank">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div id="svg_image_input"></div>
                <input id="grid_html" name="grid_html" type="hidden" value="default">
                <input id="report_id" name="report_id" type="hidden" value="{{ $insight_report->id }}">
                <button class="btn btn-danger export_pdf" id="export_pdf" type="button">Export PDF</button>
            </form>
        </div>
        <div class="picklist_grid">
        @foreach($insight_dimensions as $dimension)
            <a href="#" data-toggle="popover" title="Choose a Graph Type" data-content="" data-graph-dimension-id="{{ $dimension->id }}">
                <button class="btn btn-default inactive_menu_btn dimension_btn" disabled="true" data-dimension-id="{{ $dimension->id }}" type="button">{{ $dimension->name }}</button>
            </a>
        @endforeach
        </div>
    </div>

    <div class="col-md-9">

        <div class="panel-heading">
            <ul class="nav nav-tabs" id="insight-tabs">
                <li class="active"><a href="#tab1default" data-toggle="tab" data-tab-number="1" id="tab_1">Cover</a></li>
                <li><a href="#tab2default" data-toggle="tab" data-tab-number="2" id="tab_2">Overview</a></li>
                @for ($i = 1; $i <= count($insight_report->insightCampaignPlacements); $i++)
                    <li><a href="#tab{{ $i+2 }}default" data-toggle="tab" data-tab-number="{{ $i+2 }}" id="tab_{{ $i+2 }}">CP {{ $i }}</a></li>
                @endfor
            </ul>
        </div>

        @include('dashboard.insight-reports.widget-canvas', ['insight_report' => $insight_report])
    </div>
@stop

@push('scripts')
    <script src="{{ Bust::url('/lib/bootstrap3-editable-1.5.1/bootstrap3-editable/js/bootstrap-editable.js') }}"></script>
    <script src="{{ Bust::url('/lib/shapeshift/jquery.shapeshift.min.js') }}"></script>
    <script src="{{ Bust::url('/lib/google-charts/loader.js') }}"></script>
    <script src="{{ Bust::url('/js/sections/insight-reports.js') }}"></script>
@endpush
