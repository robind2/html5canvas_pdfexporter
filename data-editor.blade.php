@extends('dashboard.base')

@set('page_title', 'Insight Report Data Editor')

@push('css')
    <link href="{{ Bust::url('/lib/bootstrap3-editable-1.5.1/bootstrap3-editable/css/bootstrap-editable.css') }}" rel="stylesheet">
    <link href="{{ Bust::url('/lib/datatables/rowReorder.dataTables.min.css') }}" rel="stylesheet"></link>
    <link href="{{ Bust::url('/css/sections/insight-reports.css') }}" rel="stylesheet"></link>
@endpush

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/configure-placements/{$insight_report->id}", NavActions::LABEL_BACK)
    ) !!}
@endsection

@section('content')
    <div id="data-editor">
        <ul class="nav nav-tabs">
            <li class="active insight-tab">
                <a  href="#campaign_overview" data-toggle="tab">Overview</a>
            </li>
            @foreach ($insight_report->insightCampaignPlacements as $campaignPlacement)
                <li class="insight-tab">
                    <a href="#{{ $campaignPlacement->id }}" data-toggle="tab">{{ str_limit($campaignPlacement->name, 30, '&hellip;') }}</a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            <div class="tab-pane active" id="campaign_overview">
                <div class="form-group">
                    {!! Form::bootstrapLabel('flight_dates') !!}
                    <div class="col-md-10">
                        {{ $insight_report->order->start_date }} - {{ $insight_report->order->end_date }}
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::bootstrapLabel('targeting_notes') !!}
                    <div class="col-md-10">
                        {!! Form::bootstrapTextarea('targeting_notes', $insight_report->targeting_notes) !!}
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::bootstrapLabel('campaign_goals') !!}
                    <div class="col-md-10">
                        {!! Form::bootstrapTextarea('campaign_goals', $insight_report->campaign_goals) !!}
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::bootstrapLabel('key_findings') !!}
                    <div class="col-md-10">
                        {!! Form::bootstrapTextarea('key_findings', $insight_report->key_findings) !!}
                    </div>
                </div>

                <div class="form-group">
                    {!! Form::bootstrapLabel('advertiser_logo_url') !!}
                    <div class="col-md-10">
                        {!! Form::bootstrapText('advertiser_logo_url', $insight_report->advertiser_logo_url) !!}
                    </div>
                </div>
            </div> <!-- close #campaign_overview -->

            @foreach ($insight_report->insightCampaignPlacements as $campaignPlacement)
                <div class="tab-pane" id="{{ $campaignPlacement->id }}">
                    @include('dashboard.insight-reports.edit-data')
                </div>
            @endforeach
        </div> <!-- close .tab-content  -->
    </div><!-- close #data-editor -->
@endsection

@push('scripts')
    <script src="{{ Bust::url('/js/sections/insight-reports.js') }}"></script>
    <script src="{{ Bust::url('/lib/datatables/dataTables.rowReorder.min.js') }}"></script>
    <script src="{{ Bust::url('/lib/bootstrap3-editable-1.5.1/bootstrap3-editable/js/bootstrap-editable.js') }}"></script>
@endpush
