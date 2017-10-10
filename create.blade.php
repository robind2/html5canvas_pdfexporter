@extends('dashboard.base')

@set('page_title', 'Create Insight Report')

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/", NavActions::LABEL_BACK)
    ) !!}
@endsection

@section('content')
    {!! Form::model($insight_report, ['action' => ['InsightReportController@postCreateReport', $insight_report->id], 'class' => 'panel form-horizontal']) !!}
        @include('dashboard.insight-reports.basic-info-form')
    {!! Form::close() !!}
@endsection
