@extends('dashboard.base')

@set('page_title', 'Edit Insight Report: ' . $insight_report->name)

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/", NavActions::LABEL_BACK),
        NavActions::action("/audit-trails/filter/insight_reports/{$insight_report->id}", NavActions::LABEL_AUDIT_TRAIL)
    ) !!}
@endsection

@section('content')
    {!! Form::model($insight_report, ['action' => ['InsightReportController@postEditReport', $insight_report->id], 'class' => 'panel form-horizontal']) !!}
        @include('dashboard.insight-reports.basic-info-form')
    {!! Form::close() !!}
@endsection

@push('scripts')
    <script src="{{ Bust::url('/lib/bootstrap3-editable-1.5.1/bootstrap3-editable/js/bootstrap-editable.js') }}"></script>
    <script src="{{ Bust::url('/js/sections/insight-reports.js') }}"></script>
@endpush
