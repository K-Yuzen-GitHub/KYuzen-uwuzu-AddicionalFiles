<?php 
$serverlogofile = "../server/serverlogo.txt";
$serverlogodata = file_get_contents($serverlogofile);
$serverlogodata = explode( "\n", $serverlogodata );
$cnt = count( $serverlogodata );
for( $i=0;$i<$cnt;$i++ ){
    $serverlogo_link[$i] = ($serverlogodata[$i]);
}
?>
<div class="userleftbox">
    <?php if(!empty($serverlogo_link[0])){ ?>
    <div class="logo">
        <img src=<?php echo htmlspecialchars($serverlogo_link[0], ENT_QUOTES, 'UTF-8');?>>
    </div>
    <?php }else{?>
    <div class="logo">
        <img src="../img/uwuzucolorlogo.svg">
    </div>
    <?php }?>
    <hr>
    <div class="lbtnzone">
        <a href="/home/index-demo" class="leftbutton"><svg><use xlink:href="../img/sysimage/menuicon/home.svg#home"></use></svg>ホーム</a>
        <hr> 
        <a href="/" class="leftbutton">デモを終了</a>
        </form>
    </div>
</div>
<?php ?>