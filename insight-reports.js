var InsightReport = {
    initialize: function () {
        InsightReport.bindDeleteButton();
        // InsightReport.renderEditor();
        InsightReport.generateEditableFields();
    },

    generateEditableFields: function (callback) {
        $.fn.editable.defaults.mode = 'inline';
        $.fn.editable.defaults.params = function (params) {
            params._token = xsrf_token;
            params.primary_key = $(this).data('primary-key');
            params.name = $(this).data('name');
            return params;
        }
        $('.editable').editable({
            send: 'always',
            ajaxOptions: {
                success: function (data) {
                    if (data['primary-key']) {
                        InsightReport.target.data('primary-key', data['primary-key']);
                    }
                },
                error: function (response) {
                    if (response.statusCode().status == 500) {
                        response.responseText = 'Error Saving Note: Server Error.'
                    } else {
                        response.responseText = 'Error Saving Note.';
                    }
                    return response;
                }
            }
        });
    },

    bindDeleteButton: function () {
        $('body').on('click', '.delete', function () {
            var row = $(this).parents('tr')[0];
            var xhttp = new XMLHttpRequest();
            xhttp.open("POST", $(this).data('url'), true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send(`primary_key=${$(this).data('primary-key')}&_token=${window.xsrf_token}`);
            xhttp.onreadystatechange = function() {
                if (xhttp.readyState === 4) {
                    if (xhttp.status === 200) {
                        row.remove();
                    } else {
                        alert('Deletion failed.');
                    }
                }
            }
        });
    },
};

$('table').on('init.dt', function(e, settings, json){
    $(InsightReport.initialize);
});
$('table').on('draw.dt', function () {
    $(InsightReport.initialize);
});
$('table').on('row-reorder', function ( e, details, changes ) {
    editor
        .edit(changes.nodes, false, {
            submit: 'changed'
        })
        .multiSet(changes.dataSrc, changes.values)
        .submit();
});

var tabs_drawn_array = [];

$(document).ready(function() {

    var shapeshift_config = {
        selector: 'div',
        align: 'left',
        enableDrag: true,
        enableCrossDrop: false,
        defaultFont: 'sans-serif',
        minColumns: 8,
        colWidth: 49,
        paddingX: 17,
        paddingY: 17,
        gutterX: 17,
        gutterY: 17,
    }

    var uneditable_shapeshift_config = {
        selector: 'div',
        align: 'left',
        enableDrag: false,
        enableCrossDrop: false,
        defaultFont: 'sans-serif',
        minColumns: 8,
        colWidth: 49,
        paddingX: 17,
        paddingY: 17,
        gutterX: 17,
        gutterY: 17,
    }

    var widget_options = {};
    var widgets_by_tab_array = [];

    var widget_chart_options = {
        '0': {name: 'Bar Chart', image: '/img/insight-reports/bar_chart.png'},
        '1': {name: 'Column Chart', image: '/img/insight-reports/column_chart.png'},
        '2': {name: 'Line Chart', image: '/img/insight-reports/line_chart.png'},
        '3': {name: 'Donut Chart', image: '/img/insight-reports/donut_chart.png'},
        '4': {name: 'Vertical List', image: '/img/insight-reports/vertical_list.png'},
        '5': {name: 'Horizontal List', image: '/img/insight-reports/horizontal_list.png'},
        '6': {name: 'Mindset Index', image: '/img/insight-reports/mindset_index.png'},
        '7': {name: 'Screenshots', image: '/img/insight-reports/screenshots.png'},
    }

    $.ajax({
        url: '/insight-reports/ajax-fetch-insight-dimensions',
        type: 'POST',
        data: {
            _token: window.xsrf_token,
            'report_id': $('#report_id').data('report-id-value'),
        },
        dataType:'json',
        success: function (data) {
            widget_options = data;

            for (var $i=0; $i<$('.grid').length; $i++) {
                for(var $j=0; $j<widget_options.length; $j++) {
                    widgets_by_tab_array.push({'dimension_id': widget_options[$j].id, 'grid_id': $('.grid:eq(' + $i + ')').attr('id'), 'selected': 0});
                }
            }
        },
        error: function() {
            console.log('Error: failed to save SVG as an image');
        }
    });

    function findWidget(dimension_id) {
        for (var i=0; i<widget_options.length; i++) {
            if (widget_options[i].id == dimension_id) {
                return widget_options[i];
            }
        }
        return null;
    }

    $('a[data-toggle=tab]').click(function(){
        var href_array = this.href.split('#');
        switchToTab(href_array[1]);
    });

    var $insight_dimension_btns = $('.dimension_btn');

    // set event listener on page load for insight dimension buttons
    for (var i = 0; i < $insight_dimension_btns.length; i++) {
        insightDimensionButtonListener($($insight_dimension_btns[i]));
    }

    $('[data-toggle="popover"]').each(function () {
        var dimension_id = $(this).data('graph-dimension-id');
        var $chart_option_content = "";
        for (var i = 0; i < Object.keys(widget_chart_options).length; i++) {
            $chart_option_content += (i % 2 == 0) ? '<div class="row">' : '';
            $chart_option_content += "<div style='cursor: pointer;' class='chart_type col-xs-6' data-chart-type='" + i + "' id='chart_type_option' data-dimension-id='" + dimension_id + "'><div class='row' style='padding-left: 15px;'>" +
                widget_chart_options[i]['name'] + "</div><div class='row' style='padding-left: 15px; padding-bottom: 15px;'><img style='border: 1px grey solid;' src='" + widget_chart_options[i]['image'] + "'/></div></div>";
            $chart_option_content += (i % 2 != 0) ? '</div>' : '';
        }
        var popover_panel = $(this);

        $(this).popover({
            html: true,
            content: $chart_option_content,
        }).parent().delegate('div.chart_type', 'click', function() {
            var chart_type = $(this).data('chart-type');
            popover_panel.popover('hide');
            var dimension_btn = popover_panel.find('.dimension_btn');
            if ($(this).data('dimension-id') === dimension_id) {
                if (dimension_btn.hasClass('btn-default')) {
                    dimension_btn.removeClass('btn-default');
                    dimension_btn.addClass('btn-primary');
                    loadChart(dimension_id, chart_type);
                    addDimensionToTabArray(dimension_id);

                    $.ajax({
                        url: '/insight-reports/ajax-save-dimension-widget-type',
                        type: 'POST',
                        data: {
                            _token: window.xsrf_token,
                            'dimension_id': dimension_id,
                            'chart_type': chart_type,
                        },
                        dataType:'json',
                        success: function (data) {
                            //do nothing
                        },
                        error: function() {
                            console.log('Error: failed to save dimension\'s widget type');
                        }
                    });

                } else if (dimension_btn.hasClass('btn-primary')) {
                    removeDimension(dimension_id);
                }
            }
        });
    });

    function insightDimensionButtonListener(button) {
        $(button).click(function() {
            if ($(this).hasClass('btn-primary')) {
                removeDimension($(this).data('dimension-id'));
            }
        });
    }

    function addDimensionToTabArray(dimension_id) {
        var current_grid_id = $('ul[id="insight-tabs"] li.active a').attr("href");
        current_grid_id = current_grid_id.replace('#', '');
        widgets_by_tab_array = widgets_by_tab_array.map(function (widget) {
            if (widget.dimension_id == dimension_id && widget.grid_id == current_grid_id) {
                widget.selected = 1;
                return widget;
            } else {
                return widget;
            }
        });
    }

    function removeDimension(dimension_id) {
        var widget_details = findWidget(dimension_id);
        var active_tab = $('ul[id="insight-tabs"] li.active a').attr("href").replace('#', '');
        var widget_to_remove = document.getElementById(widget_details.id + '_' + active_tab);
        widgets_by_tab_array = widgets_by_tab_array.map(function (widget) {
            if (widget.dimension_id == dimension_id && widget.grid_id == active_tab && widget.selected == 1) {
                widget.selected = 0;
                $('[data-dimension-id='+dimension_id+']').removeClass('btn-primary');
                $('[data-dimension-id='+dimension_id+']').addClass('btn-default');
                widget_to_remove != null ? widget_to_remove.remove() : null;
                $('#'+active_tab).shapeshift(shapeshift_config);
                return widget;
            } else {
                return widget;
            }
        });
    }

    function switchToTab(tab_href) {
        //unselect all dimension buttons
        $('.dimension_btn').each(function () {
            if ($(this).hasClass('btn-primary')) {
                $(this).removeClass('btn-primary');
                $(this).addClass('btn-default');
            }
            if (tab_href === 'tab1default' || tab_href === 'tab2default') {
                $(this).attr('disabled', true);
            } else {
                $(this).attr('disabled', false);
            }
        });
        $.each(widgets_by_tab_array, function(index, data) {
            if (data.grid_id == tab_href) {
                if (data.selected == 1) {
                    $('[data-dimension-id='+data.dimension_id+']').removeClass('btn-default');
                    $('[data-dimension-id='+data.dimension_id+']').addClass('btn-primary');
                }
            }
        });
    }

    function loadChart(dimension_id, chart_type) {
        var widget_details = findWidget(dimension_id);
        var active_tab = $('ul[id="insight-tabs"] li.active a').attr("href").replace('#', '');
        var $width_options = '';
        var $height_options =  '';
        var widget_width = shapeshift_config['colWidth'];
        var widget_height = shapeshift_config['colWidth'];

        var min_height = 2;
        var height = widget_details.default_height < 2 ? 2 : widget_details.default_height;
        if (widget_details['insight_data'].length > 1) {
            $width_options = "data-ss-colspan='" + widget_details['insight_data'].length + "'";
            widget_width = (shapeshift_config['colWidth'] * widget_details['insight_data'].length) + (shapeshift_config['gutterX'] * (widget_details['insight_data'].length - 1));
        }
        if (height > 1) {
            $height_options = "data-ss-rowspan='" + height + "'";
            widget_height = (shapeshift_config['colWidth'] * height) + (shapeshift_config['gutterY'] * (height - 1));
        }

        if (chart_type === 4) {
            var num_rows = widget_details['insight_data'].length + 1;
            var num_grid_rows = Math.floor(num_rows / 2);
            num_grid_rows += (num_rows % 2 > 0) ? 1 : 0;
            $height_options = "data-ss-rowspan='" + num_grid_rows + "'";
            widget_height = (shapeshift_config['colWidth'] * num_grid_rows) + (shapeshift_config['gutterY'] * (num_grid_rows - 1));
        }

        if (chart_type === 5) {
            var num_cols = Math.floor((widget_details['insight_data'].length * 2) * 0.75);
            $width_options = "data-ss-colspan='" + num_cols + "'";
            widget_width = (shapeshift_config['colWidth'] * num_cols) + (shapeshift_config['gutterY'] * (num_cols - 1));
        }

        var new_widget = $("<div " + $width_options + " " + $height_options + " id='" + widget_details.id + "_" + active_tab + "'></div>");
        new_widget.css({'width': widget_width + 'px', 'height': widget_height + 'px', 'position': 'absolute', 'display': 'block'});

        //append the widget to the canvas
        $('#'+active_tab).append(new_widget);
        $('#'+active_tab).shapeshift(shapeshift_config);
        chart_title = widget_details.name;

        if (chart_type === 0 || chart_type == 1) { //bar chart or column chart
            google.charts.load('current', {'packages':['corechart', 'bar']});
            google.charts.setOnLoadCallback(() => {
                drawChart(widget_details, active_tab, widget_width, widget_height, chart_type);
            });
        } else if (chart_type === 2) { //line chart
            google.charts.load('current', {'packages':['corechart', 'line']});
            google.charts.setOnLoadCallback(() => {
                drawChart(widget_details, active_tab, widget_width, widget_height, chart_type);
            });
        } else if (chart_type === 3) { //donut chart
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(() => {
                drawChart(widget_details, active_tab, widget_width, widget_height, chart_type);
            });
        } else if (chart_type === 4 || chart_type === 5) { //vertical or horizontal list
            drawChart(widget_details, active_tab, widget_width, widget_height, chart_type);
        }

    }

    function drawChart(widget_details, active_tab, widget_width, widget_height, chart_type) {
        var chart_title = widget_details.name;
        var chart_container = document.getElementById(widget_details.id + '_' + active_tab);

        if (chart_type === 3) { //donut chart
            for (var k = 0; k < widget_details['insight_data'].length; k++) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', "");
                data.addColumn('number', "avg");

                var data_array = [];
                data_array.push([widget_details['insight_data'][k]['name'], widget_details['insight_data'][k]['ctr']]);

                data.addRows(data_array);

                chart_container.style.display = 'block';
                var chart_options = {
                    title: chart_title,
                    width: widget_width,
                    height: widget_height,
                    fontName: 'sans-serif',
                    colors: ['#5ebd5e'],
                    series: {},
                    vAxis: {
                        minValue: 0
                    },
                    hAxis: {
                        minValue: 0
                    },
                    legend: 'none',
                    pointSize: 5,
                };
                var chart = new google.visualization.PieChart(chart_container);
                chart_options['pieHole'] = 0.85;
                chart.draw(data, chart_options);
            }
        } else if (chart_type === 4 || chart_type === 5) { //vertical or horizontal list
            var list_div = getListHtml(widget_details, widget_width, widget_height, chart_type);
            $('#' + widget_details.id + '_' + active_tab).append(list_div);
        } else {
            var data = new google.visualization.DataTable();
            data.addColumn('string', "");
            data.addColumn('number', "avg");

            var data_array = [];
            for (var i = 0; i < widget_details['insight_data'].length; i++) {
                data_array.push([widget_details['insight_data'][i]['name'], widget_details['insight_data'][i]['ctr']]);
            }

            data.addRows(data_array);

            chart_container.style.display = 'block';
            var chart_options = {
                title: chart_title,
                width: widget_width,
                height: widget_height,
                fontName: 'sans-serif',
                colors: ['#5ebd5e'],
                series: {},
                vAxis: {
                    minValue: 0
                },
                hAxis: {
                    minValue: 0
                },
                targetAxisIndex: 0,
                legend: 'none',
                pointSize: 5,
            };
            var chart = null;
            switch (chart_type) {
                case 0: //bar chart
                    chart = new google.visualization.BarChart(chart_container);
                    chart.draw(data, chart_options);
                    break;
                case 1: //column chart
                    chart = new google.visualization.ColumnChart(chart_container);
                    chart.draw(data, chart_options);
                    break;
                case 2: //line chart
                    chart = new google.visualization.LineChart(chart_container);
                    chart.draw(data, chart_options);
                    break;
            }
        }
    }

    function getListHtml(widget_details, widget_width, widget_height, chart_type) {
        var list_div = '';
        var row_height = Math.floor((widget_height - 16) / (widget_details['insight_data'].length + 1));
        var extra_height_pixels = ((widget_height - 16) % (widget_details['insight_data'].length + 1));
        var right_column_position = Math.floor((widget_height - 16) * 0.66);
        if (chart_type === 4) { //vertical list
            var num_grid_rows = Math.floor((widget_details['insight_data'].length + 1) / 2);
            num_grid_rows += ((widget_details['insight_data'].length + 1) % 2 > 0) ? 1 : 0;
            var top_padding = 3 * num_grid_rows;
            list_div += "<div style='background-color: white; position: relative; width: " + widget_width + "px; height: " + widget_height + "px;'>"
            list_div += "<div style='background-color: white; position: relative; width: " + (widget_width - 16) + "px; height: " + (widget_height - 16) + "px; top: 8; left: 8;'>";
            list_div += "<div style='background-color: white; height: " + (row_height + extra_height_pixels) + "px; width: 100%; font-size: small; color: black; font-weight: bold; font-family: sans-serif;'>" + widget_details.name + "</div>"
            for (var k = 0; k < widget_details['insight_data'].length; k++) {
                var border_str = (k < (widget_details['insight_data'].length - 1)) ? 'border-bottom: 1px solid #f2f2f2;' : '';
                list_div += "<div style='background-color: white; height: " + row_height + "px; width: 100%; font-size: x-small; color: #939598; " + border_str + "'>";
                list_div += "<div style='padding-left: 5px; float: left; padding-top: " + top_padding + "px; font-family: sans-serif;'>" + widget_details['insight_data'][k]['name'] + "</div>";
                list_div += "<div style='float: right; padding-right: 5px; padding-top: " + top_padding + "px; color: #5ebd5e; font-size: small; font-weight: bold; font-family: sans-serif;'>" + widget_details['insight_data'][k]['ctr'] + "%</div>";
                list_div += "</div>";
            }
            list_div += "</div></div>";
        } else if (chart_type === 5) { //horizontal list
            var num_grid_rows = Math.floor((widget_details['insight_data'].length + 1) / 2);
            var col_width = Math.floor(widget_width / widget_details['insight_data'].length);
            num_grid_rows += ((widget_details['insight_data'].length + 1) % 2 > 0) ? 1 : 0;
            var top_padding = 3 * num_grid_rows;
            list_div += "<div style='background-color: white; position: relative; width: " + widget_width + "px; height: " + widget_height + "px;'>"
            list_div += "<div style='background-color: white; position: relative; width: " + (widget_width - 16) + "px; height: " + (widget_height - 16) + "px; top: 8; left: 8;'>";
            list_div += "<div style='background-color: white; height: " + (row_height + extra_height_pixels) + "px; width: 100%; font-size: small; color: black; font-weight: bold; font-family: sans-serif;'>" + widget_details.name + "</div>"



            list_div += "<div style='background-color: white; height: " + row_height + "px; width: 100%; font-size: x-small; color: #939598; " + border_str + "'>";
            for (var k = 0; k < widget_details['insight_data'].length; k++) {
                var border_str = (k < (widget_details['insight_data'].length - 1)) ? 'border-right: 1px solid #f2f2f2;' : '';
                list_div += "<div style='float: left; width=" + col_width + "%; background-color: white; " + border_str + "'>";
                list_div += "<div style='height: " + row_height + "px; color: #5ebd5e; font-size: small; font-weight: bold; font-family: sans-serif; clear: both;'>" + widget_details['insight_data'][k]['ctr'] + "%</div>";
                list_div += "<div style='height: " + row_height + "px; font-family: sans-serif;'>" + widget_details['insight_data'][k]['name'] + "</div>";
                list_div += "</div>";
            }
            list_div += "</div></div></div>";
        }
        return list_div;
    }

    function getStaticVerticalListHtml(data_length, widget_width, widget_height, mindset_array, title) {
        var list_div = '';
        var row_height = Math.floor((widget_height - 16) / (data_length + 1));
        var extra_height_pixels = ((widget_height - 16) % (data_length + 1));
        var right_column_position = Math.floor((widget_height - 16) * 0.66);
        var num_grid_rows = Math.floor((data_length + 1) / 2);
        num_grid_rows += ((data_length + 1) % 2 > 0) ? 1 : 0;
        var top_padding = 3 * num_grid_rows;
        list_div += "<div style='background-color: white; position: relative; width: " + widget_width + "px; height: " + widget_height + "px;'>"
        list_div += "<div style='background-color: white; position: relative; width: " + (widget_width - 16) + "px; height: " + (widget_height - 16) + "px; top: 8; left: 8;'>";
        list_div += "<div style='background-color: white; height: " + (row_height + extra_height_pixels) + "px; width: 100%; font-size: small; color: black; font-weight: bold; font-family: sans-serif;'>" + title + "</div>"
        for (var k = 0; k < data_length; k++) {
            var border_str = (k < (data_length - 1)) ? 'border-bottom: 1px solid #f2f2f2;' : '';
            list_div += "<div style='background-color: white; height: " + row_height + "px; width: 100%; font-size: x-small; color: #939598; " + border_str + "'>";
            list_div += "<div style='padding-left: 5px; float: left; padding-top: " + top_padding + "px; font-family: sans-serif;'>" + mindset_array[k] + "</div>";
            list_div += "</div>";
        }
        list_div += "</div></div></div></div></div>";
        return list_div;
    }

    var static_widget_options = {
        '0': {name: 'Campaign Goals', width: 6, height: 2, left: 17, top: 17},
        '1': {name: 'Mindsets', width: 2, height: 3, left: 413, top: 17},
        '2': {name: 'Flight Dates', width: 3, height: 1, left: 17, top: 149},
        '3': {name: 'Targeting', width: 3, height: 1, left: 215, top: 149},
        '4': {name: 'Overall Performance', width: 8, height: 4, left: 17, top: 215},
        '5': {name: 'Key Findings', width: 8, height: 1, left: 17, top: 479},
    }

    function getTextWidgetHtml(widget_width, widget_height, title, content) {
        var html = "<div style='background-color: white; position: relative; width: " + widget_width + "px; height: " + widget_height + "px;'>"
        html += "<div style='background-color: white; position: relative; width: " + (widget_width - 16) + "px; height: " + (widget_height - 16) + "px; top: 8; left: 8;'>";
        html += "<div style='background-color: white; width: 100%; font-size: small; color: black; font-weight: bold; font-family: sans-serif;'>" + title + "</div>"
        html += "<div style='background-color: white; width: 100%; font-family: sans-serif; font-size: x-small; color: #939598; padding-top: 5px;'>" + content + "</div>";
        html += "</div></div></div>";
        return html;
    }




    function drawOverallPerformanceTable(widget_width, widget_height) {
        var table_container = document.getElementById('overall_performance_table');

        var data = new google.visualization.DataTable();
        data.addColumn('string', "Placement");
        data.addColumn('string', "CTR/Exp Rate");
        data.addColumn('string', "Eng Rate");

        var data_array = [];
        data_array.push(['Contextual 320x50 Smartphone', '0.59%', 'N/A']);
        data_array.push(['Keyword 320x50 Smartphone', '0.87%', 'N/A']);
        data_array.push(['Mindset 320x50 Smartphone', '0.67%', 'N/A']);
        data_array.push(['Mindset Retargeting 320x50 Smartphone', '0.66%', 'N/A']);
        data_array.push(['Standard Overall', '0.68%', 'N/A']);
        data_array.push(['Mindset Rich Media 320x50 -> 320x480 SP Expandable', '0.99%', '7.91%']);

        data.addRows(data_array);

        var cssClasses = {
            'headerRow': 'cssHeaderRow',
            'tableRow': 'cssTableRow',
            'oddTableRow': 'cssOddTableRow',
            'selectedTableRow': 'cssSelectedTableRow',
            'hoverTableRow': 'cssHoverTableRow',
            'headerCell': 'cssHeaderCell',
            'tableCell': 'cssTableCell',
            'rowNumberCell': 'cssRowNumberCell'
        };

        // table_container.style.display = 'block';
        var table_options = {
            allowHTML: true,
            cssClassNames: cssClasses,
        };

        var table = new google.visualization.Table(table_container);
        table.draw(data, table_options);
    }

    function setupCoverPage() {
        var static_widget_data = null;
        var table_width = 0;
        var table_height = 0;
        var widget_width = (uneditable_shapeshift_config['colWidth'] * static_widget_options[i]['width']) + (uneditable_shapeshift_config['gutterX'] * (static_widget_options[i]['width'] - 1));
        var widget_height = (uneditable_shapeshift_config['colWidth'] * static_widget_options[i]['height']) + (uneditable_shapeshift_config['gutterY'] * (static_widget_options[i]['height'] - 1));
        var new_widget_html = "<div data-ss-colspan='" + static_widget_options[i]['width'] + "' data-ss-rowspan='" + static_widget_options[i]['height'] + "'>";
        new_widget_html += "<div><img src='/img/insight-report-cover.jpg'/></div>";
        new_widget_html += "</div>";
        var new_widget = $(new_widget_html);

        new_widget.css({'width': widget_width + 'px', 'height': widget_height + 'px', 'position': 'absolute', 'display': 'block', 'top': static_widget_options[i]['top'] + 'px', 'left': static_widget_options[i]['left'] + 'px'});

        //append the widget to the canvas
        $('#tab1default').append(new_widget);
        $('#tab1default').shapeshift(uneditable_shapeshift_config);

    }

    function setupStaticPages() {
        var static_widget_data = null;
        var table_width = 0;
        var table_height = 0;

        $.ajax({
            url: '/insight-reports/ajax-fetch-static-page-data',
            type: 'POST',
            data: {
                _token: window.xsrf_token,
                'report_id': $('#report_id').data('report-id-value'),
            },
            dataType:'json',
            success: function (data) {
                static_widget_data = data;
                for (var i = 0; i < Object.keys(static_widget_options).length; i++) {
                    var widget_width = (uneditable_shapeshift_config['colWidth'] * static_widget_options[i]['width']) + (uneditable_shapeshift_config['gutterX'] * (static_widget_options[i]['width'] - 1));
                    var widget_height = (uneditable_shapeshift_config['colWidth'] * static_widget_options[i]['height']) + (uneditable_shapeshift_config['gutterY'] * (static_widget_options[i]['height'] - 1));
                    var new_widget_html = "<div data-ss-colspan='" + static_widget_options[i]['width'] + "' data-ss-rowspan='" + static_widget_options[i]['height'] + "'>";
                    switch (i) {
                        case 0:
                            new_widget_html += getTextWidgetHtml(widget_width, widget_height, 'Campaign Goals', static_widget_data['campaign_goals']);
                            break;
                        case 1:
                            var temp_mindsets = ['DIYers', 'The Guy\'s Guy', "Weekend Warriors"];
                            new_widget_html += getStaticVerticalListHtml(temp_mindsets.length, widget_width, widget_height, temp_mindsets, 'Mindsets');
                            break;
                        case 2:
                            break;
                        case 3:
                            new_widget_html += getTextWidgetHtml(widget_width, widget_height, 'Targeting', static_widget_data['targeting_notes']);
                            break;
                        case 4:
                            new_widget_html = "<div data-ss-colspan='" + static_widget_options[i]['width'] + "' data-ss-rowspan='" + static_widget_options[i]['height'] + "' id='overall_performance_table'>";
                            table_width = widget_width;
                            table_height = widget_height;
                            break;
                        case 5:
                            new_widget_html += getTextWidgetHtml(widget_width, widget_height, 'Key Findings', static_widget_data['key_findings']);
                            break;
                    }
                    new_widget_html += "</div>";
                    var new_widget = $(new_widget_html);

                    new_widget.css({'width': widget_width + 'px', 'height': widget_height + 'px', 'position': 'absolute', 'display': 'block', 'top': static_widget_options[i]['top'] + 'px', 'left': static_widget_options[i]['left'] + 'px'});

                    //append the widget to the canvas
                    $('#tab2default').append(new_widget);
                    $('#tab2default').shapeshift(uneditable_shapeshift_config);
                    if (i === 4) {
                        //render the overall performance table
                        google.charts.load('current', {'packages':['table']});
                        google.charts.setOnLoadCallback(() => {
                            drawOverallPerformanceTable(table_width, table_height);
                        });
                    }
                }
            },
            error: function() {
                console.log('Error: failed to fetch campaign overview data');
            }
        });
    }

    setupCoverPage();
    setupStaticPages();

    $("#export_pdf").click(function() {
        var $report_id = $('#report_id').data('report-id-value');

        $tab_content_clone = $('.tab-content').clone();
        var count = $($tab_content_clone).find('svg').length;
        var i = 0;
        var current_tab_id = '#tab1default';
        var grid_counter = 0;
        var total_grids = $('.grid').length;
        var xhrs = [];

        $($tab_content_clone).find('.grid').wrap('<p/>').parent().each(function() {

            $(this).find('svg').wrap('<p/>').parent().each(function() {
                var svg_tag = $(this);
                var xhr = $.ajax({
                    url: '/insight-reports/ajax-svg-to-image',
                    type: 'POST',
                    data: {
                        _token: window.xsrf_token,
                        'svg_html': svg_tag.html(),
                        'image_id': i++,
                        'report_id': $('#report_id').data('report-id-value'),
                    },
                    dataType:'json',
                    success: function (data) {
                        var svg_parent = svg_tag.parent().parent().parent();
                        var widget_div = svg_parent.parent();
                        svg_parent.remove();
                        widget_div.append("<img src='" + data.image_path + "'/>");
                        $(this).unwrap();
                    },
                    error: function() {
                        console.log('Error: failed to save SVG as an image');
                    }
                });
                xhrs.push(xhr);
            });

            //dont put a pagebreak at the bottom of the last page
            if (grid_counter < (total_grids - 1)) {``
                $(this).append("<div style='page-break-before: always;'></div>");
            }
            grid_counter++;
        });

        $.when.apply($, xhrs).done(function(){
            $('input#grid_html').val($tab_content_clone.html());
            $('#invisible_form').submit();
        });
    });

    var tables = $('table');
    tables.on('init.dt', function(e, settings, json){
        $(InsightReport.initialize);
    });
    tables.on('draw.dt', function () {
        $(InsightReport.generateEditableFields);
    });
    tables.on('row-reorder.dt', function (e, diff, edit) {
        var updates = [];
        var xhttp = new XMLHttpRequest();
        for(var i=0, ien=diff.length ; i<ien ; i++ ) {
            updates.push([diff[i].node.dataset.primaryKey, diff[i].newPosition+1]);
        }
        updates = encodeURIComponent(JSON.stringify(updates));
        var postData = `updates=${updates}&_token=${window.xsrf_token}`;
        xhttp.open('POST', '/insight-reports/reorder-data', true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(postData);
        xhttp.onreadystatechange = function() {
            if (xhttp.readyState === 4) {
                if (xhttp.status !== 200) {
                    alert('Cannot re-order rows.');
                }
            }
        }
    });
});

//Remove Bootstrap Popovers if you click elsewhere on the page
$(document).on('click', function (e) {
    $('[data-toggle="popover"],[data-original-title]').each(function () {
        //the 'is' for buttons that trigger popups
        //the 'has' for icons within a button that triggers a popup
        if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
            (($(this).popover('hide').data('bs.popover')||{}).inState||{}).click = false  // fix for BS 3.3.6
        }
    });
});
