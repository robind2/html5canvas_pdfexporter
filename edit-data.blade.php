<div class="data-editor">

    <div>
        <ul class="nav nav-pills nav-stacked control dimension-tab-list">
            @foreach($campaignPlacement->insightDimensions as $dimension)
                <li @if($dimension == $campaignPlacement->insightDimensions->first()) class="active" @endif>
                    <a  href="#{{ $dimension->id }}" data-toggle="tab">{{ $dimension->name }}</a>
                </li>
            @endforeach
        </ul>
        {!! Form::bootstrapButton('New Dimension', ['id' => 'add_new_dimension-{{ $dimension->id }}', 'class' => 'btn btn-block data-editor-btn']) !!}
        {!! Form::bootstrapButton('Delete Dimension', ['id' => 'delete_active_dimension-{{ $dimension->id }}', 'class' => 'btn btn-block data-editor-btn']) !!}
        {!! Form::bootstrapButton('Rename Dimension', ['id' => 'rename_active_dimension-{{ $dimension->id }}', 'class' => 'btn btn-block data-editor-btn']) !!}
    </div>

    <div class="tab-content" style="padding: 0;">
        @foreach ($campaignPlacement->insightDimensions as $dimension)

            <div class="tab-pane @if($dimension == $campaignPlacement->insightDimensions->first()) active @endif" id="{{ $dimension->id }}">
                <div style="margin-bottom: 1rem;">
                    {!! Form::bootstrapButton('New Value', ['id' => 'add_dimension_value']) !!}
                </div>
                <table name="{{ $dimension->name }}-{{ $dimension->id }}" class="datatable row-reorder table " data-ajax-url="/insight-reports/json-dimension-values/{{ $dimension->id }}" data-dimension-id="{{ $dimension->id }}">
                    <thead>
                        <tr>
                            <th class="datatable-nosort"></th>
                            <th class="datatable-nosort">Dimension Value</th>
                            <th class="datatable-nosort">Imps</th>
                            <th class="datatable-nosort">CTR</th>
                            <th class="datatable-nosort">VCR</th>
                            <th class="datatable-nosort">Expand Rate</th>
                            <th class="datatable-nosort">Engagement Rate</th>
                            <th class="datatable-nosort">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

</div>
