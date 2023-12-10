<?php
$servernamefile = "../server/servername.txt";

$mojisizefile = "../server/textsize.txt";

$banurldomainfile = "../server/banurldomain.txt";
$banurl_info = file_get_contents($banurldomainfile);
$banurl = preg_split("/\r\n|\n|\r/", $banurl_info);

function createUniqId(){
    list($msec, $sec) = explode(" ", microtime());
    $hashCreateTime = $sec.floor($msec*1000000);
    
    $hashCreateTime = strrev($hashCreateTime);

    return base_convert($hashCreateTime,10,36);
}
require('../db.php');

// 変数の初期化
$datetime = array();
$user_name = null;
$message = array();
$message_data = null;
$error_message = array();
$pdo = null;
$stmt = null;
$res = null;
$option = null;

session_name('uwuzu_s_id');
session_start();
session_regenerate_id(true);

//------------------------------------------
// データベースに接続
try {

    $option = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    );
    $pdo = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST , DB_USER, DB_PASS, $option);

} catch(PDOException $e) {

    // 接続エラーのときエラー内容を取得する
    $error_message[] = $e->getMessage();
}


$notificationcount = $notiData['notification_count'];

//-------------------------------------------
function get_mentions_userid($postText) {
    // @useridを検出する
    $usernamePattern = '/@(\w+)/';
    $mentionedUsers = [];

    preg_replace_callback($usernamePattern, function($matches) use (&$mentionedUsers) {
        $mention_username = $matches[1];

        $dbh = new PDO('mysql:charset=utf8mb4;dbname='.DB_NAME.';host='.DB_HOST, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ));
    
        $mention_userQuery = $dbh->prepare("SELECT username, userid FROM account WHERE userid = :userid");
        $mention_userQuery->bindValue(':userid', $mention_username);
        $mention_userQuery->execute();
        $mention_userData = $mention_userQuery->fetch();   
        
        if (!empty($mention_userData)) {
            $mentionedUsers[] = $mention_username;
        }
    }, $postText);

    return $mentionedUsers;
}
function rotate($image, $exif)
{
    $orientation = $exif['Orientation'] ?? 1;

    switch ($orientation) {
        case 1: //no rotate
            break;
        case 2: //FLIP_HORIZONTAL
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 3: //ROTATE 180
            $image = imagerotate($image, 180, 0);
            break;
        case 4: //FLIP_VERTICAL
            imageflip($image, IMG_FLIP_VERTICAL);
            break;
        case 5: //ROTATE 270 FLIP_HORIZONTAL
            $image = imagerotate($image, 270, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 6: //ROTATE 90
            $image = imagerotate($image, 270, 0);
            break;
        case 7: //ROTATE 90 FLIP_HORIZONTAL
            $image = imagerotate($image, 90, 0);
            imageflip($image, IMG_FLIP_HORIZONTAL);
            break;
        case 8: //ROTATE 270
            $image = imagerotate($image, 90, 0);
            break;
    }
    return $image;
}


if( !empty($_POST['btn_submit']) ) {
	$ueuse = htmlentities($_POST['ueuse']);

	if(isset($_POST['nsfw_chk'])){
		$nsfw_chk = htmlentities($_POST['nsfw_chk']);
	}else{
		$nsfw_chk = "false";
	}

	if($nsfw_chk === "true"){
		$save_nsfw = "true";
	}else{
		$save_nsfw = "false";
	}

	// メッセージの入力チェック
	if( empty($ueuse) ) {
		$error_message[] = '内容を入力してください。';
	} else {
        // 文字数を確認
        if( (int)htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8') < mb_strlen($ueuse, 'UTF-8') ) {
			$error_message[] = '内容は'.htmlspecialchars(file_get_contents($mojisizefile), ENT_QUOTES, 'UTF-8').'文字以内で入力してください。';
		}

		// 禁止url確認
		for($i = 0; $i < count($banurl); $i++) {
			if (false !== strpos($ueuse, 'https://'.$banurl[$i])) {
				$error_message[] = '投稿が禁止されているURLが含まれています。';
			}
		}

    }


	if (empty($_FILES['upload_images']['name'])) {
		$photo1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile = $_FILES['upload_images'];

		// アップロードされたファイルの拡張子を取得
		$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename = uniqid() . '-'.$userid.'.' . $extension;
		
		// 保存先のパスを生成
		$uploadedPath = '../ueuseimages/' . $newFilename;
		
		// ファイルを移動
		$result = move_uploaded_file($uploadedFile['tmp_name'], $uploadedPath);

		// EXIF削除
		if($extension == "jpg" || $extension == "jpeg"){
			$gd = imagecreatefromjpeg($uploadedPath);
			$w = imagesx($gd);
			$h = imagesy($gd);
			$gd_out = imagecreatetruecolor($w,$h);
			imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
			$exif = exif_read_data($uploadedPath); 
			$gd_out = rotate($gd_out, $exif);
			imagejpeg($gd_out, $uploadedPath);
			imagedestroy($gd_out);
		}
		
		if ($result) {
			$photo1 = $uploadedPath; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_images2']['name'])) {
		$photo2 = "none";
	} else {

		if (empty($_FILES['upload_images']['name'])){
			$error_message[] = '画像1から画像を選択してください！！！';
		}
		// アップロードされたファイル情報
		$uploadedFile2 = $_FILES['upload_images2'];

		if( 10000000 < $uploadedFile2["size"] ) {
			$error_message[] = 'ファイルサイズが大きすぎます！';
		}
		// アップロードされたファイルの拡張子を取得
		$extension2 = pathinfo($uploadedFile2['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename2 = uniqid() . '-'.$userid.'.' . $extension2;
		
		// 保存先のパスを生成
		$uploadedPath2 = '../ueuseimages/' . $newFilename2;
		
		// ファイルを移動
		$result2 = move_uploaded_file($uploadedFile2['tmp_name'], $uploadedPath2);

		// EXIF削除
		if($extension2 == "jpg" || $extension2 == "jpeg"){
			$gd = imagecreatefromjpeg($uploadedPath2);
			$w = imagesx($gd);
			$h = imagesy($gd);
			$gd_out = imagecreatetruecolor($w,$h);
			imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
			$exif = exif_read_data($uploadedPath2); 
			$gd_out = rotate($gd_out, $exif);
			imagejpeg($gd_out, $uploadedPath2);
			imagedestroy($gd_out);
		}
		
		if ($result2) {
			$photo2 = $uploadedPath2; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile2['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_images3']['name'])) {
		$photo3 = "none";
	} else {

		if (empty($_FILES['upload_images2']['name'])){
			$error_message[] = '画像2から画像を選択してください！！！';
		}
		// アップロードされたファイル情報
		$uploadedFile3 = $_FILES['upload_images3'];

		if( 10000000 < $uploadedFile3["size"] ) {
			$error_message[] = 'ファイルサイズが大きすぎます！';
		}
		// アップロードされたファイルの拡張子を取得
		$extension3 = pathinfo($uploadedFile3['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename3 = uniqid() . '-'.$userid.'.' . $extension3;
		
		// 保存先のパスを生成
		$uploadedPath3 = '../ueuseimages/' . $newFilename3;
		
		// ファイルを移動
		$result3 = move_uploaded_file($uploadedFile3['tmp_name'], $uploadedPath3);

		// EXIF削除
		if($extension3 == "jpg" || $extension3 == "jpeg"){
			$gd = imagecreatefromjpeg($uploadedPath3);
			$w = imagesx($gd);
			$h = imagesy($gd);
			$gd_out = imagecreatetruecolor($w,$h);
			imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
			$exif = exif_read_data($uploadedPath3); 
			$gd_out = rotate($gd_out, $exif);
			imagejpeg($gd_out, $uploadedPath3);
			imagedestroy($gd_out);
		}
		
		if ($result3) {
			$photo3 = $uploadedPath3; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile3['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(3)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_images4']['name'])) {
		$photo4 = "none";
	} else {

		if (empty($_FILES['upload_images3']['name'])){
			$error_message[] = '画像3から画像を選択してください！！！';
		}
		// アップロードされたファイル情報
		$uploadedFile4 = $_FILES['upload_images4'];

		if( 10000000 < $uploadedFile4["size"] ) {
			$error_message[] = 'ファイルサイズが大きすぎます！';
		}
		// アップロードされたファイルの拡張子を取得
		$extension4 = pathinfo($uploadedFile4['name'], PATHINFO_EXTENSION);
		
		// 新しいファイル名を生成（uniqid + 拡張子）
		$newFilename4 = uniqid() . '-'.$userid.'.' . $extension4;
		
		// 保存先のパスを生成
		$uploadedPath4 = '../ueuseimages/' . $newFilename4;
		
		// ファイルを移動
		$result4 = move_uploaded_file($uploadedFile4['tmp_name'], $uploadedPath4);

		// EXIF削除
		if($extension4 == "jpg" || $extension4 == "jpeg"){
			$gd = imagecreatefromjpeg($uploadedPath4);
			$w = imagesx($gd);
			$h = imagesy($gd);
			$gd_out = imagecreatetruecolor($w,$h);
			imagecopyresampled($gd_out, $gd, 0,0,0,0, $w,$h,$w,$h);
			$exif = exif_read_data($uploadedPath4); 
			$gd_out = rotate($gd_out, $exif);
			imagejpeg($gd_out, $uploadedPath4);
			imagedestroy($gd_out);
		}
		
		if ($result4) {
			$photo4 = $uploadedPath4; // 保存されたファイルのパスを使用
		} else {
			$errnum = $uploadedFile4['error'];
			if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
			if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
			if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
			if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
			if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
			if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
			if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
			$error_message[] = 'アップロード失敗！(4)エラーコード：' .$errcode.'';
		}
	}

	if (empty($_FILES['upload_videos1']['name'])) {
		$video1 = "none";
	} else {
		// アップロードされたファイル情報
		$uploadedFile3 = $_FILES['upload_videos1'];
		
		// アップロードされたファイルの拡張子を取得
		$extension3 = strtolower(pathinfo($uploadedFile3['name'], PATHINFO_EXTENSION)); // 小文字に変換

		// サポートされている動画フォーマットの拡張子を配列で定義
		$supportedExtensions = array("mp4", "avi", "mov", "webm");

		if (in_array($extension3, $supportedExtensions)) {
			// 正しい拡張子の場合、新しいファイル名を生成
			$newFilename3 = uniqid() . '-'.$userid.'.' . $extension3;
			// 保存先のパスを生成
			$uploadedPath3 = '../ueusevideos/' . $newFilename3;
		
			// ファイルを移動
			$result3 = move_uploaded_file($uploadedFile3['tmp_name'], $uploadedPath3);
		
			if ($result3) {
				$video1 = $uploadedPath3; // 保存されたファイルのパスを使用
			} else {
				$errnum = $uploadedFile3['error'];
				if($errnum === 1){$errcode = "FILE_DEKASUGUI_PHP_INI_KAKUNIN";}
				if($errnum === 2){$errcode = "FILE_DEKASUGUI_HTML_KAKUNIN";}
				if($errnum === 3){$errcode = "FILE_SUKOSHIDAKE_UPLOAD";}
				if($errnum === 4){$errcode = "FILE_UPLOAD_DEKINAKATTA";}
				if($errnum === 6){$errcode = "TMP_FOLDER_NAI";}
				if($errnum === 7){$errcode = "FILE_KAKIKOMI_SIPPAI";}
				if($errnum === 8){$errcode = "PHPINFO()_KAKUNIN";}
				$error_message[] = 'アップロード失敗！(2)エラーコード：' .$errcode.'';
			}
		} else {
			$error_message[] = '対応していないファイル形式です！';
		}
		
		
	}

	if( empty($error_message) ) {
		
		    // 書き込み日時を取得
            $datetime = date("Y-m-d H:i:s");
			$uniqid = createUniqId();
			$abi = "none";

            // トランザクション開始
            $pdo->beginTransaction();

            try {

                // SQL作成
                $stmt = $pdo->prepare("INSERT INTO ueuse (username, account, uniqid, ueuse, photo1, photo2, photo3, photo4, video1, datetime, abi, nsfw) VALUES (:username, :account, :uniqid, :ueuse, :photo1, :photo2, :photo3, :photo4, :video1, :datetime, :abi, :nsfw)");
        
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->bindParam(':account', $userid, PDO::PARAM_STR);
				$stmt->bindParam(':uniqid', $uniqid, PDO::PARAM_STR);
                $stmt->bindParam(':ueuse', $ueuse, PDO::PARAM_STR);

				$stmt->bindParam(':photo1', $photo1, PDO::PARAM_STR);
				$stmt->bindParam(':photo2', $photo2, PDO::PARAM_STR);
				$stmt->bindParam(':photo3', $photo3, PDO::PARAM_STR);
				$stmt->bindParam(':photo4', $photo4, PDO::PARAM_STR);
				$stmt->bindParam(':video1', $video1, PDO::PARAM_STR);
                $stmt->bindParam(':datetime', $datetime, PDO::PARAM_STR);

				$stmt->bindParam(':nsfw', $save_nsfw, PDO::PARAM_STR);

				$stmt->bindParam(':abi', $abi, PDO::PARAM_STR);

                // SQLクエリの実行
                $res = $stmt->execute();

                // コミット
                $res = $pdo->commit();

				$mentionedUsers = get_mentions_userid($ueuse);

				foreach ($mentionedUsers as $mentionedUser) {
				
					$pdo->beginTransaction();

					try {
						$touserid = $mentionedUser;
						$datetime = date("Y-m-d H:i:s");
						$msg = "" . $ueuse . "";
						$title = "" . $userid . "さんにメンションされました！";
						$url = "/!" . $uniqid . "~" . $userid . "";
						$userchk = 'none';

						// 通知用SQL作成
						$stmt = $pdo->prepare("INSERT INTO notification (touserid, msg, url, datetime, userchk, title) VALUES (:touserid, :msg, :url, :datetime, :userchk, :title)");


						$stmt->bindParam(':touserid', htmlentities($touserid), PDO::PARAM_STR);
						$stmt->bindParam(':msg', $msg, PDO::PARAM_STR);
						$stmt->bindParam(':url', htmlentities($url), PDO::PARAM_STR);
						$stmt->bindParam(':userchk', htmlentities($userchk), PDO::PARAM_STR);
						$stmt->bindParam(':title', htmlentities($title), PDO::PARAM_STR);

						$stmt->bindParam(':datetime', htmlentities($datetime), PDO::PARAM_STR);

						// SQLクエリの実行
						$res = $stmt->execute();

						// コミット
						$res = $pdo->commit();

					} catch(Exception $e) {

						// エラーが発生した時はロールバック
						$pdo->rollBack();
					}
			
				}

            } catch(Exception $e) {

                // エラーが発生した時はロールバック
                $pdo->rollBack();
        	}

            if( $res ) {
				$url = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header("Location:".$url."");
				exit;  
            } else {
                $error_message[] = $e->getMessage();
            }

            // プリペアドステートメントを削除
            $stmt = null;
	}
}



require('../logout/logout.php');



// データベースの接続を閉じる
$pdo = null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script src="../js/unsupported.js"></script>
<script src="../js/console_notice.js"></script>
<script src="../js/nsfw_event.js"></script>
<link rel="manifest" href="../manifest/manifest.json" />
<script>
if ("serviceWorker" in navigator) {
	navigator.serviceWorker.register("../sw.js").then(reg => {
		console.log("ServiceWorker OK", reg);
	}).catch(err => {
		console.log("ServiceWorker BAD", err);
	});
}
</script>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="apple-touch-icon" type="image/png" href="../favicon/apple-touch-icon-180x180.png">
<link rel="icon" type="image/png" href="../favicon/icon-192x192.png">
<link rel="stylesheet" href="../css/home.css">
<title>ローカルタイムライン - <?php echo file_get_contents($servernamefile);?></title>

</head>

<body>

	<div>
		<div id="clipboard" class="online" style="display:none;">
			<p>🗒️📎 ユーズのURLをコピーしました！</p>
		</div>
		<div id="offline" class="offline" style="display:none;">
			<p>🦖💨 インターネットへの接続が切断されました...</p>
		</div>
		<div id="online" class="online" style="display:none;">
			<p>🌐💫 インターネットへの接続が復帰しました！！！</p>
		</div>
	</div>

	<?php require('../require/leftbox-demo.php');?>
	
	<main class="outer">
		<?php if(empty($_COOKIE['event'])){
			  if (date("md") == "0101") {?>
			<div class="hny" id="osho_gats">
				<div class="top">Happy New Year <?php echo date("Y")?> !!!</div>
				<div class="textmain">
					<h1>あけましておめでとうございます！</h1>
					<p>あけましておめでとうございます<br>今日から<?php echo date("Y年")?>ですね～！<br>今年もどうぞuwuzuをよろしくお願いいたします！</p>
					<p><script type="text/javascript">
					rand = Math.floor(Math.random()*8);
										
					if (rand == 0) msg = "早速ですが年越しジャンプしました？";
					if (rand == 1) msg = "早速ですがお餅は食べましたか？";
					if (rand == 2) msg = "お餅を喉に詰まらせないよう気をつけてくださいね～";
					if (rand == 3) msg = "福袋とか買いましたか～？";
					if (rand == 4) msg = "やっぱりこたつでゆっくりしたいね...";
					if (rand == 5) msg = "みかんでも食べます？";
					if (rand == 6) msg = "お鍋でもどうですか～？";
					if (rand == 7) msg = "一生こたつにいたい...";
											
					document.write(msg);
					</script></p>
					<div class="rp"><?php echo date("Y年n月j日")?></div>
					<button class="os_exit_btn">とじる</button>
				</div>
			</div>
			<?php }?>
		<?php }?>

		<div class="tlchange">
				<a href="index-demo" class="on">LTL</a>
		</div>
		<?php if( !empty($error_message) ): ?>
			<ul class="errmsg">
				<?php foreach( $error_message as $value ): ?>
					<p>・ <?php echo $value; ?></p>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if(!($role ==="ice")){?>
		<?php }?>
		<script>
			document.getElementById("upload_videos1").addEventListener('change', function(e){
				var file_reader = new FileReader();
				// ファイルの読み込みを行ったら実行
				file_reader.addEventListener('load', function(e) {
					const element = document.querySelector('#videos1');
					const createElement = '<p>動画を選択しました。</p>';
					element.insertAdjacentHTML('afterend', createElement);
				});
				file_reader.readAsText(e.target.files[0]);
			});
			document.getElementById("upload_images4").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
				const element = document.querySelector('#images4');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
			});
			file_reader.readAsText(e.target.files[0]);
			});

			document.getElementById("upload_images3").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
				const element = document.querySelector('#images3');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
				$("#images4").show();
			});
			file_reader.readAsText(e.target.files[0]);
			});

			document.getElementById("upload_images2").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
				const element = document.querySelector('#images2');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
				$("#images3").show();
			});
			file_reader.readAsText(e.target.files[0]);
			});
			document.getElementById("upload_images").addEventListener('change', function(e){
			var file_reader = new FileReader();
			// ファイルの読み込みを行ったら実行
			file_reader.addEventListener('load', function(e) {
				const element = document.querySelector('#images');
				const createElement = '<p>画像を選択しました。</p>';
				element.insertAdjacentHTML('afterend', createElement);
				$("#images2").show();
			});
			file_reader.readAsText(e.target.files[0]);
			});
		</script>

		<section class="inner">
			<div id="postContainer">
				

			</div>
		</section>

		<div id="loading" class="loading" style="display: none;">
			🤔
		</div>
		<div id="error" class="error" style="display: none;">
			<h1>エラー</h1>
			<p>サーバーの応答がなかったか不完全だったようです。<br>ネットワークの接続が正常かを確認の上再読み込みしてください。</p>
		</div>

	</main>

	<?php require('../require/rightbox.php');?>
	<?php require('../require/botbox.php');?>

</body>

<script>
$(document).ready(function() {

	loadPosts();

    var pageNumber = 1;
    var isLoading = false;

    function loadPosts() {
        if (isLoading) return;
        isLoading = true;
		$("#loading").show();
		var userid = '<?php echo $userid; ?>';
        $.ajax({
            url: '../nextpage/nextpage.php', // PHPファイルへのパス
            method: 'GET',
            data: { page: pageNumber, userid: userid },
            dataType: 'html',
			timeout: 300000,
            success: function(response) {
                $('#postContainer').append(response);
                pageNumber++;
                isLoading = false;
				$("#loading").hide();
            },
			error: function (xhr, textStatus, errorThrown) {  // エラーと判定された場合
				isLoading = false;
				$("#loading").hide();
				$("#error").show();
			},
        });
    }

	$('.outer').on('scroll', function() {
		var innerHeight = $('.inner').innerHeight(), //内側の要素の高さ
			outerHeight = $('.outer').innerHeight(), //外側の要素の高さ
			outerBottom = innerHeight - outerHeight; //内側の要素の高さ - 外側の要素の高さ
		if (outerBottom <= $('.outer').scrollTop()) {
			var elem = document.getElementById("noueuse");

			if (elem === null){
				// 存在しない場合の処理
				loadPosts();
			} else {
				// 存在する場合の処理
				return;
			}
		}
	});




	
    


	window.addEventListener('online', function(){
		checkOnline();
	});
	window.addEventListener('offline', function(){
		checkOnline();
	});
	function checkOnline() {
		if( navigator.onLine ) {
			$("#online").show();
			$("#offline").hide();
		} else {
			$("#online").hide();
			$("#offline").show();
		}
	}
	});

	$(document).on('click', '.share', function (event) {

		var domain = "<?php echo $domain;?>";
		var share_uniqid = $(this).attr('data-uniqid');
		var share_userid = $(this).attr('data-userid');

		if (typeof navigator.share === 'undefined') {
			navigator.clipboard.writeText("https://"+domain+"/!"+share_uniqid+"")
			$("#clipboard").show();
			window.setTimeout(function(){
				$("#clipboard").hide();
			}, 5000);
            return;
        }

        var shareData = {
            title: ''+share_userid+'さんのID '+share_uniqid+' のユーズ - uwuzu',
        	text: '',
            url: "https://"+domain+"/!"+share_uniqid+"",
        };

		navigator.share(shareData)
		.then(function () {
			// シェア完了後の処理
		})
		.catch(function (error) {
			// シェア失敗時の処理
		});

	});

	var osho_gats = document.getElementById('osho_gats');
	$(document).on('click', '.os_exit_btn', function (event) {
		document.cookie = "event=done; max-age=86400";
		osho_gats.style.display = 'none';
	});

</script>
</html>