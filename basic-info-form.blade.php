<div class="panel-heading">
    <strong>Basic Info</strong>
</div>
<div class="panel-body">
    <div class="form-group">
        {!! Form::bootstrapLabel('name') !!}
        <div class="col-md-10">
            <div class="input-group-sm">
                {!! Form::bootstrapText('name', ['title' => trans('tooltips.insight-reports.name')]) !!}
            </div>
        </div>
    </div>

    <div class="form-group">
        {!! Form::bootstrapLabel('order_id', 'Order') !!}
        <div class="col-sm-10">
            <div class="input-group-sm">
                @if ($errors->isEmpty() && $insight_report->order_id)
                    {!! Form::bootstrapText('order_name', Order::find($insight_report->order_id)->machine_name, ['disabled' => 'disabled']) !!}
                    {!! Form::hidden('order_id', $insight_report->order_id) !!}
                @else
                    {!! Form::bootstrapSelect('order_id', 'Order', $insight_report->order_id, ['title' => trans('tooltips.insight-reports.order_id'), 'data-ajax' => '/ajax-search/orders', 'data-placeholder' => 'Select Order', 'data-auto-width' => 'true']) !!}
                @endif
            </div>
        </div>
    </div>

    <div class="form-group">
        {!! Form::bootstrapLabel('campaign_placement_grouping') !!}
        <div class="col-sm-10">
            <div class="input-group-sm">
                @if ($insight_report->exists && !is_null($insight_report->campaign_placement_grouping))
                    {!! Form::bootstrapText('campaign_placement_grouping', App\Models\InsightReport::CAMPAIGN_PLACEMENT_GROUPING_LOOKUP[$insight_report->campaign_placement_grouping], ['disabled' => 'disabled']) !!}
                    {!! Form::hidden('campaign_placement_grouping', $insight_report->campaign_placement_grouping) !!}
                @else
                    {!! Form::bootstrapSelect('campaign_placement_grouping', App\Models\InsightReport::CAMPAIGN_PLACEMENT_GROUPING_LOOKUP, null, ['title' => trans('tooltips.insight-reports.campaign_placement_grouping'), 'data-placeholder' => 'Select Placement Grouping', 'data-auto-width' => 'true']) !!}
                @endif
            </div>
        </div>
    </div>
    <div id="unsaved_change_alert" class="alert alert-warning hide">
    </div>

    <div class="form-group">
        <div class="col-md-2"></div>
        <div class="col-md-10">
            {!! Html::multiSubmit('/insight-reports', [
                ['name' => 'save_and_configure_placement', 'label' => 'Submit'],
                ['name' => 'save_and_stay', 'label' => 'Submit & Stay on Page'],
                ['name' => 'save_and_add', 'label' => 'Submit & Add New Insight Report'],
            ]) !!}
        </div>
    </div>
</div> <!--close panel body-->
