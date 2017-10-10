@extends('dashboard.base')

@set('page_title', 'Insight Reports')

@section('nav_actions')
    {!! NavActions::render(
            NavActions::action("/insight-reports/create", NavActions::LABEL_CREATE, 'class="create-new"')
    ) !!}
@endsection

@section('content')
    <div class="panel">
        <div class="panel-heading">
            <span class="panel-title">Filters</span>
        </div>
        <div class="panel-body">
            <div class="form-group select-filters">
                {!! Form::bootstrapSelect('sales_rep_user_id', 'User', $sales_rep_user_id, ['data-placeholder' => 'Sales Rep', 'data-width' => '150px', 'data-ajax' => '/ajax-search/sales-reps']) !!}
                {!! Form::bootstrapSelect('csm_user_id', 'User', $csm_user_id, ['data-placeholder' => 'CSM', 'data-width' => '150px', 'data-ajax' => '/ajax-search/users']) !!}
                {!! Form::bootstrapSelect('ta_user_id', 'User', $ta_user_id, ['data-placeholder' => 'T&A', 'data-width' => '150px', 'data-ajax' => '/ajax-search/ta-users']) !!}
                {!! Form::bootstrapSelect('account_manager_user_id', 'User', $account_manager_user_id, ['data-placeholder' => 'Account Manager', 'data-width' => '150px', 'data-ajax' => '/ajax-search/account-managers']) !!}
            </div>
        </div>
    </div>

    <form method="post" action="/insight-reports/bulk-action">
        <table class="datatable display" data-ajax-url="/insight-reports/json-reports">
            <thead>
                <tr>
                    <th class="datatable-centeralign datatable-nosort">{!! Form::checkbox('select_all', 1, null, ['class' => 'select_all']) !!}</th>
                    <th>Name</th>
                    <th>Order</th>
                    <th>Sales Rep</th>
                    <th>CSM</th>
                    <th>T&amp;A</th>
                    <th>Account Manager</th>
                    <th>Date Updated</th>
                    <th class="datatable-nosort">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        {!! BulkActions::render(
            BulkActions::newGroup('Send to'),
            BulkActions::action('delete', 'insight-reports', 'delete', BulkActions::LABEL_SEND_TO_DELETED)
        ) !!}
    </form>
@endsection
