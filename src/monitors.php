<div id="button-bar">
    <div class="button"><a href="?p=menu">Back</a></div>
    <div class="button"><a href="?p=edit&new"><b>+</b></a></div>
</div>
<div id="menu">
<?php
loginRequired();
$db = loadDb(false);
foreach($db->monitors as $monitor) {
    if($monitor->user <> $_SESSION['id']) continue;
    $id = $monitor->id;
    $name = $monitor->name;
?>
<div class="menu-item">
    <a href="?p=monitor&id=<?php echo $id; ?>"><?php echo $name; ?></a>
</div>
<?php } ?>
</div>
