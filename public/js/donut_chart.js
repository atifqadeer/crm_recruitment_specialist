$(function() {

            /* ------------------------------------------------------------------------------
         *
         *  # Google Visualization - donut chart
         *
         *  Google Visualization donut chart demonstration
         *
         * ---------------------------------------------------------------------------- */


        // Setup module
        // ------------------------------

        var GoogleDonutBasic = function() {


            //
            // Setup module components
            //

            // Donut chart
            var _googleDonutBasic = function() {
                if (typeof google == 'undefined') {
                    console.warn('Warning - Google Charts library is not loaded.');
                    return;
                }

                // Initialize chart
                google.charts.load('current', {
                    callback: function () {

                        // Draw chart
                        drawAllCharts(); //drawDonut();

                        // Resize on sidebar width change
                        var sidebarToggle = document.querySelector('.sidebar-control');
                        sidebarToggle && sidebarToggle.addEventListener('click', drawAllCharts); //drawDonut

                        // Resize on window resize
                        var resizeDonutBasic;
                        window.addEventListener('resize', function() {
                            clearTimeout(resizeDonutBasic);
                            resizeDonutBasic = setTimeout(function () {
                                drawAllCharts(); //drawDonut();
                            }, 200);
                        });
                    },
                    packages: ['corechart']
                });

                function drawAllCharts() {

                    drawDonut('google-donut', 'donut_chart_data', 'donut_colors');
                    drawDonut('weekly_google-donut', 'weekly_donut_chart_data', 'weekly_donut_colors');
                    drawDonut('monthly_google-donut', 'monthly_donut_chart_data', 'monthly_donut_colors');
                    drawDonut('custom_google-donut', 'custom_donut_chart_data', 'custom_donut_colors');

                }
                // Chart settings
                function drawDonut(element, chart_data, color_data) {

                    // Define charts element
                    
                    var donut_chart_element = document.getElementById(element);
                    var donut_chart_data = $("#"+element).data(chart_data);
                    var donut_colors = $("#"+element).data(color_data);

                    var donut_color_array = createColorArray(donut_colors);
                    var donut_chart_array = createData(donut_chart_data);

                    // Data
                    /*var d_data = [
                        ['Task', 'Hours per Day'],
                        ['Work',     11],
                        ['Eat',      2],
                        ['Commute',  2],
                        ['Watch TV', 2],
                        ['Sleep',    7]
                    ];*/
                    var data = google.visualization.arrayToDataTable(donut_chart_array);
                    // Options
                    var options_donut = {
                        fontName: 'Roboto',
                        pieHole: 0.35, //0.55,
                        height: 225, //300,
                        width: 225, //500,
                        backgroundColor: 'transparent',
                        colors: donut_color_array,
                        chartArea: {
                            left: 10,
                            width: '90%',
                            height: '90%'
                        },
                        pieSliceText: 'value',
                        sliceVisibilityThreshold:0,
                        legend: 'none' //{position: 'right', maxLines: 3}
                    };
                    
                    // Instantiate and draw our chart, passing in some options.
                    var donut = new google.visualization.PieChart(donut_chart_element);
                    donut.draw(data, options_donut);

                }
            };

            function createData(donut_chart_data) {

                var parts = donut_chart_data.split('"');
                var donut_array = [];
                var i;
                for(i=1; i<parts.length;i+=2) {
                    //donut_array[parts[i]] = parts[i+2];
                    if(i==1) {
                        donut_array.push([parts[i], parts[i+2]]);
                        i=i+2;
                    } else {
                        var part_no = parts[i+1].split(';').join(':').split(':');
                        donut_array.push([parts[i]+' ('+part_no[2]+')', parseInt(part_no[2])]);
                    }                      
                }

                return donut_array;

            }

            function createColorArray(donut_colors) {
                var parts = donut_colors.split('"');
                var donut_color_array = [];
                var i;
                for(i=1; i<parts.length;i+=2) {
                    donut_color_array.push(parts[i]);
                }

                return donut_color_array;
            }

            //
            // Return objects assigned to module
            //

            return {
                init: function() {
                    _googleDonutBasic();
                }
            }
        }();

        // Initialize module
        // ------------------------------
        GoogleDonutBasic.init();

});