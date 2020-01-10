<?php
    loginRequired();
    if(!$monitorid) die('Missing monitor id.');

    $stats = loadStats(false);
    
    // Size
    $width = 330;
    $height = 100;
    $link = "?p=graph&id=$monitorid";
    if($large) {
        $width = 490;
        $height = 200;
        $link = "?p=monitor&id=$monitorid";
    }

    // Generate rows data
    $rows = "[";
    foreach($stats as $stat)
        if($stat[0] == $_GET['id']) {
            $year = date("Y", $stat[1]);
            $month = date("n", $stat[1])-1;
            $day = date("j", $stat[1]);
            $hours = date("G", $stat[1]);
            $minutes = date("i", $stat[1]);
            $seconds = date("s", $stat[1]);
            $datetime = "new Date($year,$month,$day,$hours,$minutes,$seconds)";
            $rows .= "[$datetime,$stat[2]],";
        }
    $rows .= "]";
?>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(drawChart);
    function drawChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('datetime', 'Time');
        data.addColumn('number', 'Response time (ms)');
        data.addRows(<?php echo $rows; ?>);
        var options = {
            backgroundColor: { fill:'transparent' },
            colors: ['SeaGreen'],
            legend: 'none',
            title: '',
            width: <?php echo $width; ?>,
            height: <?php echo $height; ?>,
            hAxis: { format: 'dd H:mm' }
        };
        var dtformat = new google.visualization.DateFormat({pattern: "yyyy-MM-dd H:mm"});
        dtformat.format(data, 0);
        var chart = new google.visualization.LineChart(
            document.getElementById('chart_div'));
        chart.draw(data, options);
    }
</script>
<div id="chart_div" onclick="window.location.href='<?php echo $link; ?>';"></div>
