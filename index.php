<?php
//Process the Read Me File
$handle = fopen("README.md", "r");
if ($handle) {
    $currentCategory = NULL;
    $talks = array();
    $pattern_category = '/^(###)(.*)/';
    $pattern_talks = '/^(\* \[\*\*)(.*)(\*\*)(.*)(\(http.*\))(.*\[)(\d+:\d+)/';

    //Iterate over each line in the file
    while (($buffer = fgets($handle)) !== false) {
        //Use RegEx to Identify Category Line
        preg_match($pattern_category, $buffer, $category_matches, PREG_OFFSET_CAPTURE);
        if(count($category_matches)>0) {
            //Store Category name as the current category
            $currentCategory = $category_matches[2][0];
        }

        //Use RegEx to Identify Video Line
        preg_match($pattern_talks, $buffer, $talk_matches, PREG_OFFSET_CAPTURE);
        if(count($talk_matches)>0) {
            //Store Video details in video array
            $talks[md5(trim($talk_matches[5][0],'()[]'))] = array(
                'id' => md5(trim($talk_matches[5][0],'()[]')),
                'category' => $currentCategory,
                'title' => trim($talk_matches[2][0],'()[]'),
                'url' => trim($talk_matches[5][0],'()[]'),
                'duration' => trim($talk_matches[7][0],'()[]'),
                'watched' => NULL,
                'rating' => NULL,
            );
        }
    }
    fclose($handle);
}

//If a talk has been selected to watch
if(isset($_GET['watch']) && isset($talks[$_GET['watch']]['url'])) {
    //Add watch to the Log file
    $handle = fopen('phpmustwatch.log', 'a');
    if ($handle) {
        fwrite($handle, "WATCH\t".date('YmdHis')."\t".$talks[$_GET['watch']]['id']."\n");
    }
    fclose($handle);
    //Redirect to the talk
    header('Location:'.$talks[$_GET['watch']]['url']);
    exit;
}

//If a talk has been rated
if(isset($_GET['rate']) && isset($_GET['rating']) && isset($talks[$_GET['rate']])) {
    //Add rating to the Log file
    $handle = fopen('phpmustwatch.log', 'a');
    if ($handle) {
        fwrite($handle, "RATE\t".date('YmdHis')."\t".intval($_GET['rating'])."\n");
    }
    fclose($handle);
    //Redirect to the talk
    header('Location:/');
    exit;
}

//Process the Log File
if(file_exists('phpmustwatch.log')) {
    $handle = fopen("phpmustwatch.log", "r");
    if ($handle) {
        //Iterate over each line in the file
        while (($buffer = fgets($handle)) !== false) {
            //Process Log Record
            $log = explode("\t", trim($buffer,"\n"));
            switch ($log[0]) {
                case 'WATCH':
                    if (isset($talks[$log[2]])) {
                        $talks[$log[2]]['watched'] = $log[1];
                    }
                    break;
                case 'RATE':
                    if (isset($talks[$log[2]])) {
                        $talks[$log[2]]['rating'] = $log[1];
                    }
                    break;
            }
        }
        fclose($handle);
    }
}

?><html>
<head>
<title>PHP Must Watch</title>

<!-- JQuery Library -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>

<!-- JQuery UI -->
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>

<!-- compiled and minified CSS Bootstrap-->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">

<!-- Bootstrap Theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">

<!-- compiled and minified JavaScript Bootstrap-->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/js/bootstrap.min.js"></script>

<style>
    th {
        text-align: center;
    }
</style>
</head>
<body>

<h1>PHP Must Watch</h1>

<table class="table table-striped">
    <thead>
    <tr>
        <th>Title</th>
        <th>URL</th>
        <th>Duration</th>
        <th>Watched</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($talks AS $id => $talk) { ?>
    <?php if($talk['category'] !== $currentCategory) { $currentCategory = $talk['category']; ?>
    <tr>
        <th colspan="6"><?= $currentCategory ?></th>
    </tr>
    <?php } ?>
    <tr>
        <td><?= $talk['title'] ?></td>
        <td><a href="?watch=<?= $talk['id']?>" target="_blank" ><?= $talk['url'] ?></a></td>
        <td align="center"><?= $talk['duration'] ?></td>
        <td align="center"><?= (is_null($talk['watched']) ? '&nbsp;' : '&#x2714;') ?></td>
    </tr>
    <?php } ?>
    </tbody>
</table>
</body>
</html>