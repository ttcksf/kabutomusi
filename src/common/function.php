<?php
    /** ルートを取得する */
    function getRoot(){
        return $_SERVER['DOCUMENT_ROOT'];
    }

    /* 今いるページのURLのドメイン以降を取得する */
	function getRequestURL(){
		return $_SERVER["REQUEST_URI"];
    }

    /* DBの接続オブジェクトを取得します。 */
    function getDbh(){
        $dsn='mysql:dbname=test;host=127.0.0.1';
        $user='root';
        $pass='';
    try{
        /*データベースオブジェクトの作成 */
        $dbh = new PDO($dsn,$user,$pass);
        // if ($dbh == null) {
        //     p('接続に失敗しました');
        // }else{
        //     p('接続に成功しました');
        // }
        /*プログラムからデータベースへ登録したり更新するときの文字化けを防止 */
        $dbh->query('SET NAMES utf8');
    }catch(PDOException $e){
        p('Error:'.$e->getMessage());
        p('データベースへの接続に失敗しました。');
        die();
    }
        /*データベースとの接続を確立したオブジェクトを呼び出し元に返す */
    return $dbh;
    }

     /* print関数の略 */
    function p($str){
	    print $str;
    }

     /* 詳細なコンテンツリストを取得する */
    function getDetailContentsList($category = NULL, $disabled = true, $day_sort_flg = false, $archive = NULL, $limit = NULL){
        /*isset()は標準関数　引数で受け取った変数に値がセットされているかどうかを確認　 */
        if(isset($category)){
            $category_query = " AND category.category_id = ".$category;
        }else{
            $category_query = "";
        }
        if($disabled){
            $disabled_query = " AND contents.disabled_flg is null ";
        }else{
            $disabled_query = "";
        }
        if($day_sort_flg){
            $order_by_query = "ORDER BY contents.create_date DESC";
        }else{
            $order_by_query = "";
        }
        if(isset($archive)){
            $archive_query = " AND DATE_FORMAT(contents.create_date, '%Y%m') = '".$archive."'";
        }else{
            $archive_query = "";
        }
        if(isset($limit)){
            $limit_query = " LIMIT ".$limit;
        }else{
            $limit_query = "";
        }
        /*getContentSelectItemsQuery()文字列を返す*/
        /*FROM句ではカテゴリテーブルとコンテンツテーブルを呼び出している org_は省略可能  */
        /*WHERE句ではカテゴリテーブルとコンテンツテーブルを共通するカテゴリIDで結合 */
        $sql="
        SELECT ".getContentsSelectItemsQuery()."
        FROM org_category category,
            org_contents contents
        WHERE category.category_id = contents.category_id
            AND category.category_id > 1
            AND contents.contents_id <> 0".$category_query.$disabled_query.$archive_query.$order_by_query.$limit_query;
        /* カテゴリIDの1以下は管理系項目の為 */
        $stmt = getDbh()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $result;
    }

     /** 詳細なコンテンツ情報を取得するSQLのSELECT句を返す（複数の処理で同じ情報が必要になるから） */
     /*
        contents.category_id category_id,	--カテゴリID
        contents.contents_id contents_id,	--コンテンツID
        category.name category_name,	--カテゴリ名
        contents.name contents_name,	--コンテンツ名（記事の名前）
        contents.name name,	--コンテンツ名（記事の名前）
        category.url category_url,	--カテゴリのURL
        contents.url contents_url,	--コンテンツのURL
        CONCAT(category.url , contents.url) AS url,	--カテゴリURLとコンテンツURLを繋いだものをurlという名前で持たせる
        contents.create_date,	--コンテンツの作成日
        contents.update_date,	--コンテンツの更新日
        contents.disabled_flg	--コンテンツの無効フラグ
     */
    function getContentsSelectItemsQuery(){
        return "contents.category_id category_id,
            contents.contents_id contents_id,
            category.name category_name,
            contents.name contents_name,
            contents.name name,
            category.url category_url,
            contents.url contents_url,
            CONCAT(category.url , contents.url) AS url,
            contents.create_date,
            contents.update_date,
            contents.disabled_flg";
    }


     /* カテゴリリストを取得する */
    function getCategoryList($exclusion_id = NULL){
	    if(isset($exclusion_id)){$exclusion_query = " AND category_id <> ".$exclusion_id;}else{$exclusion_query = "";}
	    /** 管理系は常に除外（<> 0） */
	    $sql="SELECT * FROM org_category WHERE 1 and category_id <> 0".$exclusion_query;
	    $stmt = getDbh()->prepare($sql);
	    $stmt->execute();
	    $result = $stmt->fetchAll();
	    return $result;
   }

   /* アーカイブリストを取得する */
   /*カテゴリIDは0と1が管理者とトップページなので除外 */
   /*コンテンツIDは1からしか作ってないから0を除外 */
   /*contents.diabled_flg is NULLで有効記事だけを取得する */
   /*GROUP BYで日付のフォーマットを年月にしている */

    function getArchiveList(){
	    $sql="
	    SELECT DATE_FORMAT(contents.create_date, '%Y/%m') AS date,
	       CONCAT('/archive',DATE_FORMAT(contents.create_date, '/%Y%m/')) AS url,
	       count(*) AS count
	    FROM org_category category,
	       org_contents contents
	    WHERE category.category_id = contents.category_id
	       AND category.category_id > 1
	       AND contents.contents_id <> 0
	       AND contents.disabled_flg is null
	    GROUP BY DATE_FORMAT(contents.create_date, '%Y/%m')
        ORDER BY date DESC";

	    $stmt = getDbh()->prepare($sql);
	    $stmt->execute();
	    $result = $stmt->fetchAll();
	    return $result;
    }

     /* テーブルを全て取得する */
     /* SHOW TABLE STATUS LIKE 'org%' はテーブル名がorgで始まるもののみを取得 */
    function getTableList(){
        $sql = "SHOW TABLE STATUS LIKE 'org%'";
        $stmt = getDbh()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }