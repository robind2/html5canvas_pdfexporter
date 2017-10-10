@extends('dashboard.base')

@set('page_title', 'Copy Insight Report')

@section('nav_actions')
    {!! NavActions::render(
        NavActions::action("/insight-reports/", NavActions::LABEL_BACK)
    ) !!}
@stop

@section('content')
    {!! Form::open(['action' => ['InsightReportController@postCopyInsightReport'], 'class' => 'panel form-horizontal']) !!}
        <div class="panel-body">
            <div class="col-sm-12">
                <legend>Change Insight Report Name</legend>
            </div>
            <div class="form-group">
                <div class="col-sm-12">
                    {!! Form::bootstrapLabel('Name: ' . $insight_report->name, null, ['class' => 'col-md-2 control-label ']) !!}
                    <div class="col-md-10">
                        <div class="input-group-sm">
                            {!! Form::bootstrapText('report_name', $insight_report->name, ['placeholder' => 'Insight Report Name']) !!}
                            {!! Form::hidden('report_id', $insight_report->id) !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div class="form-group">
                    <div class="col-md-2"></div>
                    <div class="col-md-10">
                        {!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
                        <a href="/insight-reports/" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </div>
        </div> <!--  end panel-body div -->
    {!! Form::close() !!}
@stop