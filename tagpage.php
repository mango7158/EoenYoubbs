<?php
define('IN_SAESPOT', 1);
define('CURRENT_DIR', pathinfo(__FILE__, PATHINFO_DIRNAME));

include(CURRENT_DIR . '/config.php');
include(CURRENT_DIR . '/common.php');

$tag = $_GET['tag'];
$page = intval($_GET['page']);

// 验证字符
if(!preg_match('/^[a-zA-Z0-9\x80-\xff]{1,20}$/i', $tag)){
    header("HTTP/1.0 404 Not Found");
    header("Status: 404 Not Found");
    include(CURRENT_DIR . '/404.html');
    exit;
}


// 获取tag数据
$tag_obj = $DBS->fetch_one_array("SELECT * FROM `yunbbs_tags` WHERE `name`='".$tag."'");

if(empty($tag_obj) || $tag_obj['ids']=== ''){
    header("HTTP/1.0 404 Not Found");
    header("Status: 404 Not Found");
    include(dirname(__FILE__) . '/404.html');
    exit;
}


// 处理正确的页数
// 第一页是1
if($tag_obj && $tag_obj['articles']){
    $taltol_page = ceil($tag_obj['articles']/$options['list_shownum']);
    if($page<0){
        header('location: /tag/'.$tag);
        exit;
    }else if($page==1){
        header('location: /tag/'.$tag);
        exit;
    }else{
        if($page>$taltol_page){
            header('location: /tag/'.$tag.'/'.$taltol_page);
            exit;
        }
    }
}else{
    $page = 0;
}

// 获取文章列表
if($tag_obj['articles']){
    if($page == 0) $page = 1;
    $from_i = $options['list_shownum']*($page-1);
    $to_i = $from_i + $options['list_shownum'];

    if($tag_obj['articles'] > 1){
        $id_arr = array_slice( explode(',', $tag_obj['ids']), $from_i, $to_i);
    }else{
        $id_arr = array($tag_obj['ids']);
    }
    $ids = implode(',', $id_arr);
    //exit($ids);
    $query_sql = "SELECT a.id,a.uid,a.cid,a.ruid,a.title,a.content,a.views,a.addtime,a.edittime,a.comments,a.isred,c.name as cname,u.avatar as uavatar,u.name as author,ru.name as rauthor
        FROM `yunbbs_articles` a
        LEFT JOIN `yunbbs_categories` c ON c.id=a.cid
        LEFT JOIN `yunbbs_users` u ON a.uid=u.id
        LEFT JOIN `yunbbs_users` ru ON a.ruid=ru.id
        WHERE a.id in(".$ids.") AND `cid` > '1'
        ORDER BY `edittime` DESC LIMIT ".($page-1)*$options['list_shownum'].",".$options['list_shownum'];

    $query = $DBS->query($query_sql);
    $articledb=array();
    // 按id添加顺序排列
    foreach($id_arr as $aid){
        $articledb[$aid] = '';
    }

    while ($article = $DBS->fetch_array($query)) {
        // 格式化内容
        $article['addtime'] = showtime($article['addtime']);
        $article['edittime'] = showtime($article['edittime']);
		$artid = $article['id'];
		$article['content'] = set_content(mb_strlen($article['content'], 'utf-8') > 200 ? mb_substr($article['content'], 0, 200, 'utf-8').'<p class="topic-more"><a href="/topics/'.$artid.'">阅读全部<i></i></a></p>' : $article['content'], 1);
        $articledb[$article['id']] = $article;
    }
    unset($article);
    $DBS->free_result($query);
}

// 页面变量
$title = '标签：'.$tag;
$newest_nodes = get_newest_nodes();
if(count($newest_nodes)==$options['newest_node_num']){
    $bot_nodes = get_bot_nodes();
}

$meta_kws = $tag . ',';
$meta_des = '标签： '.$tag;

$show_sider_ad = "1";

$pagefile = CURRENT_DIR . '/templates/default/'.$tpl.'tag.php';

include(CURRENT_DIR . '/templates/default/'.$tpl.'layout.php');

?>
