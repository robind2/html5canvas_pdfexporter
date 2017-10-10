@extends('dashboard.base')

@set('page_title', 'Configure Campaign Placements: ' . $insight_report->name)

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/edit/{$insight_report->id}", NavActions::LABEL_BACK),
        NavActions::action("/audit-trails/filter/insight_reports/{$insight_report->id}", NavActions::LABEL_AUDIT_TRAIL)
    ) !!}
@stop

<!--
    pull the list first if there isn't anything in the db for it
-->

@section('content')
    {!! Form::open(['method' => 'POST', 'url' => ["/insight-reports/configure-placements/{$insight_report->id}"], 'class' => 'panel form-horizontal']) !!}
        <div class="panel-body">
            <div class="space-bottom">
                {!! Form::bootstrapButton('Add Campaign Placement', ['id' => 'add_insight_campaign_placement']) !!}
            </div>
            <table name="dimensions" class="datatable table" data-ajax-url="/insight-reports/json-placements/{{ $insight_report->id }}">
                <thead>
                    <tr>
                        <th class="datatable-nosort"></th>
                        <th class="datatable-nosort">Campaign Placement</th>
                        <th class="datatable-nosort">Type</th>
                        <th class="datatable-nosort">Imps</th>
                        <th class="datatable-nosort">{!! Form::checkbox('ctr_checkbox', 1, true, ['class' => 'metric-checkbox', 'data-metric' => 'ctr']) !!} CTR enabled</th>
                        <th class="datatable-nosort">CTR</th>
                        <th class="datatable-nosort">{!! Form::checkbox('vcr_checkbox', 1, true, ['class' => 'metric-checkbox', 'data-metric' => 'vcr']) !!} VCR Enabled</th>
                        <th class="datatable-nosort">VCR</th>
                        <th class="datatable-nosort">{!! Form::checkbox('expand_rate_checkbox', 1, true, ['class' => 'metric-checkbox', 'data-metric' => 'expand_rate']) !!} ExR Enabled</th>
                        <th class="datatable-nosort">Expand Rate</th>
                        <th class="datatable-nosort">{!! Form::checkbox('engagement_rate_checkbox', 1, true, ['class' => 'metric-checkbox', 'data-metric' => 'engagement_rate']) !!} EnR Enabled</th>
                        <th class="datatable-nosort">Engagement Rate</th>
                        <th class="datatable-nosort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>

            <div id="unsaved_change_alert" class="alert alert-warning hide">
            </div>

            <div class="form-group">
                <div class="col-md-10">
                    {!! Html::multiSubmit('/insight-data-editor', [
                        ['name' => 'save_and_overview_campaign', 'label' => 'Submit'],
                        ['name' => 'save_and_stay', 'label' => 'Submit & Stay on Page'],
                    ]) !!}
                </div>
            </div>
        </div>
    {!! Form::close() !!}
@stop

@push('scripts')
    <script src="{{ Bust::url('/js/sections/insight-reports.js') }}"></script>
    <script src="{{ Bust::url('/lib/datatables/dataTables.rowReorder.min.js') }}"></script>
    <script src="{{ Bust::url('/lib/bootstrap3-editable-1.5.1/bootstrap3-editable/js/bootstrap-editable.js') }}"></script>
@endpush
