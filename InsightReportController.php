<?php
namespace App\Http\Controllers;

use Yajra\Datatables\Datatables;
use Input;
use Session;
use Auth;
use Illuminate\Support\MessageBag;
use App\Traits\DoesBulkActions;
use App\Traits\TrailsBreadcrumbs;
use Request;
use Response;
use App\Libraries\ActionMenu;
use App\Models\InsightReport;
use App\Models\InsightCampaignPlacement;
use App\Models\InsightDimension;
use App\Models\InsightData;
use App\Libraries\NavActions;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Collections\RowCollection;
use Maatwebsite\Excel\Collections\SheetCollection;
use Maatwebsite\Excel\Readers\LaravelExcelReader;
use HTML;
use Form;
use App\Traits\UpdatesStatus;
use \stdClass;
use \Exception;
use App\Libraries\ReportQuery;
use PDF;
use DB;

class InsightReportController extends Controller {
    use DoesBulkActions, UpdatesStatus, TrailsBreadcrumbs;

    protected static $section = 'insight-reports';

    /**
     * Constructor
     */
    public function __construct() {
        // required by UpdatesStatus trait
        $this->model = new InsightReport();
        parent::__construct();
    }

    /**
     * Show the list of insight reports
     *
     * @return object
     */
    public function getIndex() {
        return view('dashboard.insight-reports.list', [
            'sales_rep_user_id'         => '',
            'csm_user_id'               => '',
            'ta_user_id'                => '',
            'account_manager_user_id'   => '',
            'line_item_id'              => '',
        ]);
    }

    /**
     * Shows a insight report create screen (basic settings)
     *
     * @return object
     */
    public function getCreateReport($order_id = null) {
        $insight_report = new InsightReport(['order_id' => $order_id ]);
        return view('dashboard.insight-reports.create', compact('insight_report'));
    }

    /**
     * Collect POST values from a create screen and process
     *
     * @return object
     */
    public function postCreateReport() {
        $input = Input::all();
        $insight_report = new InsightReport($input);

        if (!$insight_report->save()) {
            Input::flash();
            return back()->withErrors($insight_report->getErrors());
        } else {
            Session::flash('message', trans('insight-reports.created'));
            Input::flashOnly('message');

            if (isset($input['save_and_add'])) {
                return redirect("/insight-reports/create");
            } elseif (isset($input['save_and_stay'])) {
                return redirect("/insight-reports/edit/{$insight_report->id}");
            } elseif (isset($input['save_and_configure_placement'])) {
                return redirect("/insight-reports/configure-placements/{$insight_report->id}");
            } else {
                return redirect('insight-reports/index');
            }
        }
    }

    /**
     * Shows a insight report edit screen (basic settings)
     *
     * @param  int      $insight_report_id     The ID of the report to edit
     * @return object
     */
    public function getEditReport($insight_report_id) {
        $insight_report = InsightReport::find($insight_report_id);
        return view('dashboard.insight-reports.edit', compact('insight_report'));
    }

    /**
     * Collect POST values from an edit screen and process
     *
     * @param  int      $id     The ID of the record to edit
     * @return object
     */
    public function postEditReport($report_id) {
        $input = Input::all();
        $insight_report = InsightReport::find($report_id);

        if (!$insight_report) {
            Input::flash();
            return redirect('insight-reports')->withErrors(new MessageBag([trans('insight-reports.missing')]));
        }

        $insight_report->fill($input);
        $success = $insight_report->save();

        if (!$success) {
            return back()->withErrors($insight_report->getErrors());
        } else {
            Session::flash('message', trans('insight-reports.updated'));
            Input::flashOnly('message');

            if (isset($input['save_and_add'])) {
                return redirect("/insight-reports/create");
            } elseif (isset($input['save_and_stay'])) {
                return redirect("/insight-reports/edit/{$insight_report->id}");
            } elseif (isset($input['save_and_configure_placement'])) {
                return redirect("/insight-reports/configure-placements/{$insight_report->id}");
            } else {
                return redirect('insight-reports/index');
            }
        }
    }

    /**
     * Shows configure insight placements screen
     *
     * @param  int      $insight_report_id     The ID of the report to edit
     * @return object
     */
    public function getConfigurePlacements($insight_report_id) {
        $insight_report = InsightReport::find($insight_report_id);
        return view('dashboard.insight-reports.configure-campaign-placements', compact('insight_report'));
    }

    /**
     * Shows configure insight placements screen
     *
     * @param  int      $insight_report_id     The ID of the report to edit
     * @return object
     */
    public function postConfigurePlacements($report_id) {
        //TO DO: ensure everything is ready before moving to view
        $insight_report = InsightReport::find($report_id);
        return view('dashboard.insight-reports.data-editor', compact('insight_report'));
    }

    /**
     * Shows data editor screen
     *
     * @param  int      $insight_report_id     The ID of the report to edit
     * @return object
     */
    public function getDataEditor($insight_report_id) {
        $insight_report = InsightReport::find($insight_report_id);
        return view('dashboard.insight-reports.data-editor', compact('insight_report'));
    }

    /**
     * Fetch the list of insight reports using Ajax
     *
     * @return object
     */
    public function postReportsList() {
        $result = InsightReport::leftJoin('orders', 'orders.id', '=', 'insight_reports.order_id')
            ->leftJoin('sales_rep_users', 'orders.sales_rep_user_id', '=', 'sales_rep_users.id')
            ->leftJoin('users as csm_users', 'orders.csm_user_id', '=', 'csm_users.id')
            ->leftJoin('ta_users', 'orders.ta_user_id', '=', 'ta_users.id')
            ->leftJoin('account_manager_users', 'orders.account_manager_user_id', '=', 'account_manager_users.id')
            ->where('insight_reports.id', '>', 0)
            ->where('insight_reports.status', '<>', InsightReport::STATUS_DELETED)
            ->select([
                'insight_reports.id AS checkbox',
                'insight_reports.id',
                'insight_reports.name',
                'orders.machine_name AS order_name',
                'sales_rep_users.name AS sr_user',
                'csm_users.name AS csm_user',
                'ta_users.name AS ta_user',
                'account_manager_users.name AS am_user',
                'insight_reports.updated_at',
            ]);

        $auth_user = Auth::user();
        $data_tables = Datatables::of($result)
            ->filter(function($query) {
                $search_value = Input::get('search.value');
                if ($search_value) {
                    $query->where(function($query) use ($search_value) {
                        $query->orWhere('insight_reports.id', 'LIKE', "%$search_value%")
                            ->orWhere('insight_reports.name', 'LIKE', "%$search_value%");
                    });
                }

                $sales_rep_user_id = Input::get('filters.sales_rep_user_id');
                if (strlen($sales_rep_user_id) > 0) {
                    $query->where('orders.sales_rep_user_id', $sales_rep_user_id);
                }

                $csm_user_id = Input::get('filters.csm_user_id');
                if (strlen($csm_user_id) > 0) {
                    $query->where('orders.csm_user_id', $csm_user_id);
                }

                $ta_user_id = Input::get('filters.ta_user_id');
                if (strlen($ta_user_id) > 0) {
                    $query->where('orders.ta_user_id', $ta_user_id);
                }

                $account_manager_user_id = Input::get('filters.account_manager_user_id');
                if (strlen($account_manager_user_id) > 0) {
                    $query->where('orders.account_manager_user_id', $account_manager_user_id);
                }
            })
            ->editColumn('checkbox', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            // ->editColumn('name', function($insight_report) use ($auth_user) {
            //     if ($auth_user->hasRoute('GET|insight-reports/edit')) {
            //         return '<a href="/insight-reports/edit/' . $insight_report->id . '">' . $insight_report->name . '</a>';
            //     } else {
            //         return $insight_report->name;
            //     }
            // })
            ->addColumn('actions', function($insight_report) {
                return $this->makeActionMenu($insight_report);
            })
            ->removeColumn('id');

        return $data_tables
            ->escapeColumns([]) // remove XSS filtering
            ->make();
    }

    /**
     * Fetch the list of insight report placements using Ajax
     *
     * @return object
     */
    public function postAjaxPlacementsList($report_id = null) {
        $result = InsightCampaignPlacement::leftJoin('insight_reports', 'insight_reports.id', '=', 'insight_campaign_placements.insight_report_id')
            ->where('insight_campaign_placements.status', '<>', InsightCampaignPlacement::STATUS_DELETED)
            ->where('insight_campaign_placements.insight_report_id', '=', $report_id)
            ->select([
                'insight_campaign_placements.id AS checkbox',
                'insight_campaign_placements.id',
                'insight_campaign_placements.name',
                'insight_campaign_placements.type',
                'insight_campaign_placements.imps',
                'insight_campaign_placements.enabled_fields AS checkbox2',
                'insight_campaign_placements.ctr',
                'insight_campaign_placements.enabled_fields AS checkbox3',
                'insight_campaign_placements.vcr',
                'insight_campaign_placements.enabled_fields AS checkbox4',
                'insight_campaign_placements.expand_rate',
                'insight_campaign_placements.enabled_fields AS checkbox5',
                'insight_campaign_placements.engagement_rate',
            ]);

        $data_tables = Datatables::of($result)
            ->filter(function($query) {
                $search_value = Input::get('search.value');
                if ($search_value) {
                    $query->where(function($query) use ($search_value) {
                        $query->orWhere('insight_campaign_placements.id', 'LIKE', "%$search_value%")
                            ->orWhere('insight_campaign_placements.name', 'LIKE', "%$search_value%");
                    });
                }
            })
            ->editColumn('checkbox', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            ->editColumn('checkbox2', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            ->editColumn('checkbox3', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            ->editColumn('checkbox4', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            ->editColumn('checkbox5', "{!! Form::checkbox('bulk_action_ids[]', \$id) !!}")
            ->editColumn('name', function ($insight_placement) {
                return HTML::link('#', $insight_placement->name, [
                    'class'            => 'editable',
                    'id'               => 'name-' . $insight_placement->id,
                    'data-name'        => 'name',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_placement->id,
                    'data-url'         => '/insight-reports/update-insight-campaign-placement',
                ])->toHtml();
            })
            ->editColumn('ctr', function ($insight_placement) {
                return HTML::link('#', $insight_placement->ctr, [
                    'class'            => 'editable',
                    'id'               => 'ctr-' . $insight_placement->id,
                    'data-name'        => 'ctr',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_placement->id,
                    'data-url'         => '/insight-reports/update-insight-campaign-placement',
                ])->toHtml();
            })
            ->editColumn('vcr', function ($insight_placement) {
                return HTML::link('#', $insight_placement->vcr, [
                    'class'            => 'editable',
                    'id'               => 'vcr-' . $insight_placement->id,
                    'data-name'        => 'vcr',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_placement->id,
                    'data-url'         => '/insight-reports/update-insight-campaign-placement',
                ])->toHtml();
            })
            ->editColumn('engagement_rate', function ($insight_placement) {
                return HTML::link('#', $insight_placement->engagement_rate, [
                    'class'            => 'editable',
                    'id'               => 'engagement_rate-' . $insight_placement->id,
                    'data-name'        => 'engagement_rate',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_placement->id,
                    'data-url'         => '/insight-reports/update-insight-campaign-placement',
                ])->toHtml();
            })
            ->editColumn('expand_rate', function ($insight_placement) {
                return HTML::link('#', $insight_placement->expand_rate, [
                    'class'            => 'editable',
                    'id'               => 'expand_rate-' . $insight_placement->id,
                    'data-name'        => 'expand_rate',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_placement->id,
                    'data-url'         => '/insight-reports/update-insight-campaign-placement',
                ])->toHtml();
            })
            ->addColumn('actions', function($campaign_placement) {
                return HTML::link('#', 'Delete', [
                    'class'            => 'btn btn-default delete',
                    'id'               => 'delete-' . $campaign_placement->id,
                    'data-name'        => 'delete',
                    'data-type'        => 'text',
                    'data-primary-key' => $campaign_placement->id,
                    'data-url'         => '/insight-reports/delete-placement',
                ])->toHtml();
            })
            ->removeColumn('id');

        return $data_tables
            ->escapeColumns([]) // remove XSS filtering
            ->make();
    }

    /**
     * Updates insight data through ajax via datatables x-editable
     *
     * @return object
     */
    public function postAjaxUpdateInsightPlacement() {
        $input = Input::all();
        $insight_placement = InsightCampaignPlacement::find($input['primary_key']);
        $insight_placement->update([$input['name'] => $input['value']]);

        $errors = $insight_placement->getErrors();
        if ($errors->isEmpty()) {
            return response()->json($insight_placement, 200);
        } else {
            return response()->json($errors->first(), 400);
        }
    }

    /**
     * Deletes a placement along with it's associated dimensions and the dimensions associated data
     *
     * @return  string
     */
    public function postDeletePlacement() {
        $input = Input::all();
        $placement_id = $input['primary_key'];
        try {
            InsightCampaignPlacement::join('insight_dimensions', 'insight_dimensions.insight_campaign_placement_id', 'insight_campaign_placements.id')
                ->join('insight_data', 'insight_data.insight_dimension_id', 'insight_dimensions.id')
                ->where('insight_campaign_placements.id', '=', $placement_id)
                ->update([
                    'insight_campaign_placements.status' => InsightCampaignPlacement::STATUS_DELETED,
                    'insight_dimensions.status' => InsightDimension::STATUS_DELETED,
                    'insight_data.status' => InsightData::STATUS_DELETED
                ]);
        } catch (\Exception $error) {
            return response()->json($error->getMessage(), 400);
        }
        return response()->json(InsightCampaignPlacement::find($placement_id), 200);
    }

    /**
     * Fetch the dimension values using Ajax
     *
     * @return object
     */
    public function postAjaxInsightData($dimension_id = null) {
        $result = InsightData::where('insight_data.status', '<>', InsightData::STATUS_DELETED)
            ->where('insight_data.insight_dimension_id', '=', $dimension_id)
            ->orderBy('order')
            ->select([
                'insight_data.id',
                'insight_data.order',
                'insight_data.name',
                'insight_data.imps',
                'insight_data.ctr',
                'insight_data.vcr',
                'insight_data.expand_rate',
                'insight_data.engagement_rate',
            ]);

        $data_tables = Datatables::of($result)
            ->filter(function($query) {
                $search_value = Input::get('search.value');
                if ($search_value) {
                    $query->where(function($query) use ($search_value) {
                        $query->orWhere('insight_data.name', 'LIKE', "%$search_value%");
                    });
                }
            })
            ->setRowAttr([
                'data-primary-key' => function($insight_data) {
                    return $insight_data->id;
                },
                'data-order' => function($insight_data) {
                    return $insight_data->order;
                },
            ])
            ->editColumn('order', function ($insight_data) {
                return '<i class="fa fa-arrows-v"></i>';
            })
            ->editColumn('name', function ($insight_data) {
                return HTML::link('#', $insight_data->name, [
                    'class'            => 'editable',
                    'id'               => 'name-' . $insight_data->id,
                    'data-name'        => 'name',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/update-insight-data',
                ])->toHtml();
            })
            ->editColumn('ctr', function ($insight_data) {
                return HTML::link('#', $insight_data->ctr, [
                    'class'            => 'editable',
                    'id'               => 'ctr-' . $insight_data->id,
                    'data-name'        => 'ctr',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/update-insight-data',
                ])->toHtml();
            })
            ->editColumn('vcr', function ($insight_data) {
                return HTML::link('#', $insight_data->vcr, [
                    'class'            => 'editable',
                    'id'               => 'vcr-' . $insight_data->id,
                    'data-name'        => 'vcr',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/update-insight-data',
                ])->toHtml();
            })
            ->editColumn('engagement_rate', function ($insight_data) {
                return HTML::link('#', $insight_data->engagement_rate, [
                    'class'            => 'editable',
                    'id'               => 'engagement_rate-' . $insight_data->id,
                    'data-name'        => 'engagement_rate',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/update-insight-data',
                ])->toHtml();
            })
            ->editColumn('expand_rate', function ($insight_data) {
                return HTML::link('#', $insight_data->expand_rate, [
                    'class'            => 'editable',
                    'id'               => 'expand_rate-' . $insight_data->id,
                    'data-name'        => 'expand_rate',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/update-insight-data',
                ])->toHtml();
            })
            ->addColumn('actions', function($insight_data) {
                return HTML::link('#', 'Delete', [
                    'class'            => 'btn btn-default delete',
                    'id'               => 'delete-' . $insight_data->id,
                    'data-name'        => 'delete',
                    'data-type'        => 'text',
                    'data-primary-key' => $insight_data->id,
                    'data-url'         => '/insight-reports/delete-data',
                ])->toHtml();
            })
            ->removeColumn('id');

        return $data_tables
            ->escapeColumns([]) // remove XSS filtering
            ->make();
    }

    /**
     * Updates insight data through ajax via datatables x-editable
     *
     * @return object
     */
    public function postAjaxUpdateInsightData() {
        $input = Input::all();
        $insight_data = InsightData::find($input['primary_key']);
        $insight_data->update([$input['name'] => $input['value']]);

        $errors = $insight_data->getErrors();
        if ($errors->isEmpty()) {
            return response()->json($insight_data, 200);
        } else {
            return response()->json($errors->first(), 400);
        }
    }

    /**
     * Deletes dimension data
     *
     * @param   int       $placement_id
     * @return  string
     */
    public function postDeleteData() {
        $input = Input::all();
        $insight_data = InsightData::find($input['primary_key']);
        $insight_data->update(['status' => InsightData::STATUS_DELETED]);

        $errors = $insight_data->getErrors();
        if ($errors->isEmpty()) {
            return response()->json($insight_data, 200);
        } else {
            return response()->json($errors->first(), 400);
        }
    }

    /**
     * Deletes a dimension
     *
     * @param   int       $dimension_id
     * @return  string
     */
    public function getDeleteDimension($dimension_id) {

    }

    /**
     * Reorders dimension data
     *
     * @param   int       $dimension_id
     * @return  string
     */
    public function postReorderData() {
        $input = Input::all();
        $updates = json_decode($input['updates']);
        foreach ($updates as $update) {
            $insight_data = InsightData::find($update[0]);
            $insight_data->update(['order' => $update[1]]);
            $errors = $insight_data->getErrors();
            if (!$errors->isEmpty()) {
                return response()->json($errors->first(), 400);
            }
        }
        return response()->json('success', 200);
    }

    /**
     * @param   int       $report_id
     * @param   string    $old_dimension_name
     * @param   string    $new_dimension_name
     * @return  string
     */
    public function getRenameDimension($report_id, $old_dimension_name, $new_dimension_name) {

    }

    /**
     * @param   int       $report_id
     * @param   string    $dimension_name
     * @param   string    $dimension_value
     * @return  string                      'success' or empty/error string
     */
    public function getAddDimensionValue($report_id, $dimension_name, $dimension_value) {

    }

    /**
     *
     * @param   stdClass    $report_data
     * @param   array       $dimension_values
     * @param   stdClass    $metrics
     */
    protected static function addDimensionValues(&$report_data, $dimension_name, array $dimension_values, stdClass $metrics = null) {

    }

    /**
     * @param   int       $report_id
     * @param   string    $dimension_name
     * @return  string
     */
    public function getAddDimension($report_id, $dimension_name) {

    }

    /**
     * @param int $report_id
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    // public function getCopyInsightReportConfirmation($report_id) {
    //     $insight_report = InsightReport::find($report_id);
    //     return view('dashboard.insight-reports.copy', ['insight_report' => $insight_report]);
    // }

    /**
     * Takes the name of the Insight Report and generates a new copy
     *
     * @param   Request     $report_id
     * @return  Redirect
     */
    // public function postCopyInsightReport() {
    //     $insight_report = InsightReport::find(Input::get('report_id'));
    //     $result = $insight_report->copy(Input::get('report_name'));
    //
    //     if ($result !== true) {
    //         return back()->withErrors($result);
    //     }
    //
    //     return redirect('insight-reports')->withMessage('Insight Report Copy Successful');
    // }

    /**
     * Returns class needed to generate action menu in the view
     *
     * @param   InsightReport   $insight_report
     * @return  string
     */
    protected function makeActionMenu(InsightReport $insight_report) {
        $action_menu = new ActionMenu();

        $action_menu->newGroup()
            ->action("/insight-reports/edit/{$insight_report->id}")
            ->action("/insight-reports/edit/{$insight_report->id}?basic_settings=1", 'Basic Settings')
            ->action("/insight-reports/configure-placements/{$insight_report->id}")
            ->action("/insight-reports/build-widgets/{$insight_report->id}", 'Build Widgets')
            ->action("/audit-trails/filter/insight_reports/{$insight_report->id}", NavActions::LABEL_AUDIT_TRAIL)
            ->action("/insight-reports/download-data/{$insight_report->id}")
            ->action("/insight-reports/copy-insight-report-confirmation/{$insight_report->id}", NavActions::LABEL_COPY)
            ->action("/insight-reports/delete/{$insight_report->id}", null, 'class="confirm-action"');

        return $action_menu->render();
    }

    /**
     * Exports data for existing insight report to Excel
     *
     * @param   int     $insight_report_id
     *
     * @return  View
     */
    public function getExportExcel($insight_report_id) {
        $insight_report = InsightReport::find($insight_report_id);
        $data = $insight_report->data;

        Excel::create('Insight Report', function ($excel) use ($insight_report, $data) {
            $blade_base = 'dashboard.reports.insight-reports.export-csv';

            $excel->sheet('Overview', function ($sheet) use ($insight_report, $data, $blade_base) {
                $sheet->setColumnFormat([
                    'B' => '0.00%',
                    'D' => '0.00%',
                    'F' => '0.00%',
                    'H' => '0.00%',
                ]);

                $sheet->loadView("$blade_base.overview", compact('insight_report', 'data'));
            });

            foreach ($data->meta->line_items as $ubimo_id => $line_item_name) {
                if ($ubimo_id !== 'all') {
                    $sheet_name = substr($line_item_name, 0, 31);

                    $dimension_data = collect($data->report)->transform(function ($dimension, $key) {
                        $dimension->key = $key;
                        return $dimension;
                    })->sort(function($a, $b) {
                        if ($a->key === 'general') {
                            return -1;
                        }

                        if ($a->key === 'mindset') {
                            if ($b->key === 'general') {
                                return 1;
                            } else {
                                return -1;
                            }
                        }

                        if ($b->key === 'general' || $b->key === 'mindset') {
                            return 1;
                        }

                        if ($a->key === $b->key) {
                            return 0;
                        }

                        return $a->key > $b->key ? 1 : -1;
                    });

                    $excel->sheet($sheet_name, function ($sheet) use ($dimension_data, $ubimo_id, $line_item_name, $blade_base) {
                        $column_formats = [
                            'B' => '0.00%',
                            'C' => '0.00%',
                            'D' => '0.00%',
                            'E' => '0.00%',
                        ];

                        if ($dimension_data->has('mindset')) {
                            $last_mindset_row = 9 + count((array) $dimension_data->get('mindset')->data->$ubimo_id->dimensions);
                            $column_formats['B9:B' . $last_mindset_row] = '0';
                        }

                        $sheet->setColumnFormat($column_formats);

                        $sheet->loadView("$blade_base.line_item", compact('dimension_data', 'ubimo_id', 'line_item_name'));
                    });
                }
            }

            // Activate the first tab by default
            $excel->setActiveSheetIndex(0);
        })->export('xlsx');
    }

    /**
     * Updates checkbox value for a metric
     *
     * @return  View
     */
    public function postUpdateMetricCheckbox() {
        extract(Input::all());

        $insight_report = InsightReport::find($insight_report_id);

        $data = $insight_report->data;

        @$data->report->$dimension->data->$ubimo_id->metric_statuses->$metric = !empty($is_checked);

        $insight_report->data = $data;
        $insight_report->save();

        return response()->json('success');
    }

    /**
     * Gets checkbox values for a dimension
     *
     * @return  View
     */
    public function postGetMetricCheckboxes() {
        $response = [];

        extract(Input::all());

        if (isset($insight_report_id)) {
            $insight_report = InsightReport::find($insight_report_id);

            if (isset($insight_report->data->report->$dimension->data->$ubimo_id)) {
                $line_item = $insight_report->data->report->$dimension->data->$ubimo_id;

                if (property_exists($line_item, 'metric_statuses')) {
                    $response = $line_item->metric_statuses;
                }
            }
        }

        return response()->json($response);
    }

    /**
     * Build UI widgets for an Insight Report so they can be exported to a PDF
     *
     * @param  int      $report_id     The ID of the record to edit
     * @return object
     */
    public function getBuildWidgets($report_id) {
        $insight_report = InsightReport::find($report_id);
        $insight_dimensions = $this->model->fetchInsightDimensions($report_id);

        if ($insight_report) {
            if ($insight_report->status == InsightReport::STATUS_DELETED) {
                return redirect('insight-reports')->withErrors(new MessageBag([trans('generic.is-deleted')]));
            }

            $grid_html = '';
            $grid_html .= '<div style="background-color: #eeeeee;" class="grid tab-pane fade in active" id="tab1default"></div>';
            $grid_html .= '<div style="background-color: #eeeeee;" class="grid tab-pane fade in" id="tab2default"></div>';
            for ($i = 1; $i <= count($insight_report->insightCampaignPlacements); $i++) {
                $grid_html .= '<div style="background-color: #eeeeee;" class="grid tab-pane fade in" id="tab' . ($i+2) . 'default"></div>';
            }

            return view('dashboard.insight-reports.build-widgets', [
                'report_id'                 => $report_id,
                'insight_report'            => $insight_report,
                'insight_dimensions'        => $insight_dimensions,
                'grid_html'                 => $grid_html,
            ]);
        } else {
            return back()->withErrors(new MessageBag([trans('insight-reports.missing')]));
        }
    }

    /**
     * Export the HTML widgets built by the user in the UI to a PDF file
     *
     * @param  int      $report_id     The ID of the record to edit
     * @return object
     */
    public function postAjaxHtmlToPdf() {
        $report_id = Input::get('report_id');
        $insight_report = InsightReport::find($report_id);
        $insight_dimensions = $this->model->fetchInsightDimensions($report_id);
        $grid_html = Input::get('grid_html');

        $sales_rep_user_id = '';
        $csm_user_id = '';
        $ta_user_id = '';
        $account_manager_user_id = '';
        $line_item_id = '';

        $pdf =  PDF::loadView('dashboard.insight-reports.widget-canvas', compact(
            'report_id',
            'insight_report',
            'insight_dimensions',
            'grid_html'
        ));
        return $pdf->stream();
    }

    /**
     * Export the HTML widgets built by the user in the UI to a PDF file
     *
     * @param  string   $svg_html     HTML for this SVG tag
     * @param  int      $image_id     unique identifier for this particular image
     * @return string   $image_location     Location of the new image based off of the SVG
     */
    public function postAjaxSvgToImage() {
        $svg_html = Input::get('svg_html');
        $image_id = Input::get('image_id');
        $report_id = Input::get('report_id');

        $tmpfname = "/vagrant/storage/insight_report_svgs/SvgToImage_" . $report_id . "_" . $image_id . ".svg";
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $svg_html);
        fclose($handle);

        $response = [
            'image_path'    => $tmpfname
        ];

        return Response::json($response);
    }

    /**
     * Export the HTML widgets built by the user in the UI to a PDF file
     *
     * @param  string   $report_id     The ID for this insight report
     * @return array   $insight_dimensions     The insight_dimensions DB table in array format
     */
    public function postAjaxFetchInsightDimensions() {
        $report_id = Input::get('report_id');
        $dimensions = $this->model->fetchInsightDimensions($report_id)->toArray();

        for ($i=0; $i<count($dimensions); $i++) {
            $insight_data = InsightData::where('insight_dimension_id', $dimensions[$i]['id'])->get()->toArray();
            $dimensions[$i]['insight_data'] = $insight_data;
        }
        return $dimensions;
    }

    /**
     * Save the widget type (user selects this when they add a dimension to the HTML canvas)
     *
     * @param  string   $dimension_id     The ID for this dimension
     * @param  string   $chart_type     The the widget type, can be things like line chart, bar chart, horizontal list, etc.
     * @return array   $insight_dimensions     The insight_dimensions DB table in array format
     */
    public function postAjaxSaveDimensionWidgetType() {
        $dimension_id = Input::get('dimension_id');
        $widget_type = Input::get('chart_type');

        InsightDimension::where('id', $dimension_id)->update(['widget_type' => $widget_type]);

        $response = [
            'return_value'    => 'success'
        ];

        return Response::json($response);
    }

    /**
     * Get the insight report data
     *
     * @param  string   $report_id     The ID for this report
     * @return array   $insight_report     The insight report row from the DB
     */
    public function postAjaxFetchStaticPageData() {
        $report_id = Input::get('report_id');
        $insight_report = InsightReport::find($report_id);

        return $insight_report;
    }
}
