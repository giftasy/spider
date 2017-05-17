<?php
//载入配置文件
$configFile = 'config/'.(isset($argv[1]) ? $argv[1] : (isset($_REQUEST['file']) ? $_REQUEST['file'] : ''));
(is_file($configFile) && require($configFile)) || exit('The configuration file "'.$configFile.'" does not exist!');

//创建文件夹
$current = str_replace(array('config/spider.config_', '.php'), '', $configFile);

$categoryDir = '../upload/'.$current.'/c/';
if(!is_dir($categoryDir)){
	mkdir($categoryDir, 0777, true);
}

$productDir = '../upload/'.$current.'/p/';
if(!is_dir($productDir)){
	mkdir($productDir, 0777, true);
}

//优化系统环境变量
@set_time_limit(0);
@ini_set('display_errors', 'on');
@ini_set('max_execution_time', 0);
@ini_set('memory_limit', '2048M');
@ini_set('default_charset', 'utf-8');
@ini_set('date.timezone','PRC');

//系统常量
define('TIME', microtime(true));
define('ROOT', getcwd());

//智能加载
function autoload($class) {
    set_include_path('../../library/');
    spl_autoload($class);
}
spl_autoload_register('autoload');

//价格格式化
function getPriceNum($num){
    return (float)str_replace(array('€', '¥', '£', '$', ',', ' '), '', $num);
}

//补全+格式化网址
function completeUrl($url){
    if(empty($url)){
    	return $url;
	}
    $url = Tools::formatUrl($url);
	if(stripos($url, 'http://') === false){
		$target = parse_url(SPIDER_URL);
		$target = $target['scheme'].'://'.$target['host'];
		
		return $url{0} === '/' ? ($target.$url) : ($target.'/'.$url);
	}
	
	return $url;
}

//过滤数据
function clean($str){
	$str = trim(preg_replace('/\s\s+/isu', ' ', $str));
	
	if(DISABLE_WORD_PATTERN && DISABLE_WORD_REPLACEMENT){
		$str = preg_replace(DISABLE_WORD_PATTERN, DISABLE_WORD_REPLACEMENT, $str);
	}
	
	return $str;
}

//模仿浏览器
function addCurlOption(){
	global $crawlers;
	
	$crawlers->setReferer('http://www.dress2015.com/');//随机伪装来源
	$crawlers->setUserAgent();//随机伪装浏览器
	$crawlers->setHttpHeader();//随机伪装浏览器头
	$crawlers->setProxyMisLead();//随机伪装代理误导
	//$crawlers->setProxy(getProxy());//随机伪装IP
}

//提交事务，并记录Log
function addLog($file = 'log'){
	global $db;
	if(!$db->commit()){
		$db->rollBack();
		file_put_contents($file.'.txt', 'Time:'.date('Y-m-d H:i:s').'	errorInfo:'.join('--->', $db->errorInfo()).'	errorCode:'.$db->errorCode().PHP_EOL, FILE_APPEND);
	}else{
		//file_put_contents($file.'.txt', 'Time:'.date('Y-m-d H:i:s').'	PID:'.getmypid().PHP_EOL, FILE_APPEND);
	}
}

//获取代理IP列表
function getProxy(){
	global $db, $crawlers;
	
	$sql = 'SELECT `id`, `address` FROM `'.TABLE_PROXY.'` WHERE `times` <= ?';
		
	$categoryArray = array('nn', 'nt', 'wn', 'wt');
	while(!($result = $db->fetchAll($sql, array(SPIDER_MAX_PROXY_FAIL_TIMES)))){
		$crawlers->setModeToContent();
		if(preg_match_all('/<td>(\d+\.\d+\.\d+\.\d+)<\/td>\s+<td>(\d+)<\/td>(?:(?!<tr).)*?fast(?:(?!<tr).)*?fast/isu', $crawlers->spider('http://www.xici.net.co/'.$categoryArray[array_rand($categoryArray)].'/')->content, $ipArray) > 0){
			foreach($ipArray[1] as $k => $v){
				$crawlers->setModeToTest();
				$crawlers->setProxy(array($v.':'.$ipArray[2][$k]));
				
				if(!$crawlers->spider(SPIDER_URL)->error){
					$db->query('INSERT IGNORE INTO `'.TABLE_PROXY.'` (`address`) VALUES ("'.$crawlers->options[CURLOPT_PROXY].'");');
				}
			}
		}
		//休息，单位：微妙
		usleep(mt_rand(MIN_SLEEP_TIME, MAX_SLEEP_TIME));
	}
	
	return array_column($result, 'address', 'id');
}

//添加产品URL
function addProductUrl($content, $categoriesId){
    global $db;
	
	if(preg_match_all('/'.SPIDER_PRODUCT_URL_REG.'/isu', Tools::getStr($content, SPIDER_PRODUCT_LIST_CONTENT_BOF, SPIDER_PRODUCT_LIST_CONTENT_EOF), $urlArray) > 0){
		$urlArray = array_map('completeUrl', array_unique($urlArray[1]));
	}else{
	    return ;
	}
	
    foreach($urlArray as $v){
        $urlCrc32 = Validate::crc32($v);
        $categories = json_decode('['.$db->fetchColumn('SELECT `Categories` FROM `'.TABLE_PRODUCT.'` WHERE `urlCrc32` = '.$urlCrc32.'').']');
        if(!$categories){
            $db->insert(TABLE_PRODUCT, array('Categories' => $categoriesId, 'url' => $v, 'urlCrc32' => $urlCrc32));
        }else{
            if(!in_array($categoriesId, $categories)){
                array_push($categories, $categoriesId);
                $db->update(TABLE_PRODUCT, array('Categories' => str_replace(array('[', ']'), '', json_encode($categories))), 'urlCrc32 = '.$urlCrc32.'');
            }
        }
    }
}

//1.采集目录URL
function spiderCategoryUrl(){
    global $db,$crawlers;    
    addCurlOption();
	$crawlers->setModeToContent();
    //获取目录URL数组，插入目录URL
    if(preg_match_all('/'.SPIDER_CATEGORY_URL_REG.'/isu', Tools::getStr($crawlers->spider(SPIDER_URL)->content, SPIDER_CATEGORY_LIST_CONTENT_BOF, SPIDER_CATEGORY_LIST_CONTENT_EOF), $urlArray) > 0){
		$urlArray = array_map('completeUrl', array_unique($urlArray[1]));
		echo 'Insert `'.TABLE_CATEGORY.'` '.$db->query('INSERT INTO `'.TABLE_CATEGORY.'` (`url`, `urlCrc32`) VALUES '.join(',', array_map(function($url){return '("'.$url.'",'.Validate::crc32($url).')';}, $urlArray)).';')->rowCount().'.'.PHP_EOL;
	}else{
        exit('No Category Url.'.$crawlers->spider(SPIDER_URL)->error.'.Proxy IP:'.$crawlers->options[CURLOPT_PROXY].'.');
    }
}

//2.根据URL采集内容
function spiderContentByUrl($table){
    global $db,$crawlers;
	//初始化：Innodb事务锁定的时间
	$microtime = microtime(true);
	
	//开启事务
    $db->beginTransaction();
	
	//获取未采集的记录
    $sql = 'SELECT `id`, `url` FROM `'.$table.'` WHERE `state` = 0 LIMIT 0, '.SPIDER_MAX_RECORDS.' FOR UPDATE';
	
    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
		echo 'Lock Time:'.Tools::convertTime(microtime(true) - $microtime).PHP_EOL;
		
		//初始化：成功采集的数目
		$ok = 0;
		//初始化：采集时间
		$microtime = microtime(true);
        addCurlOption();
		$crawlers->setModeToContent();
        foreach($crawlers->spiders(array_column($urlArray, 'url', 'id')) as $k => $v){
            if(!$v->error && $v->info->http_code >= 200 && $v->info->http_code < 400){
				//成功：已采集
				$db->update($table, array('state' => 1, 'content' => $v->content), '`id` = '.$k);
				echo $table.' '.$k.' Spider OK.'.PHP_EOL;
				$ok++;
            }else{
				//失败：记录日志
				if($v->error && isset($crawlers->options[CURLOPT_PROXY])){
					$db->query('UPDATE `'.TABLE_PROXY.'` SET `times` = `times` + 1 WHERE `address` = ?', array($crawlers->options[CURLOPT_PROXY]));
				}
				$db->update($table, array('content' => $v->error), '`id` = '.$k);
            }
        }
		
		echo '['.$times.']. Times Spider.[Time:'.Tools::convertTime(microtime(true) - $microtime).', Ok:'.number_format(($ok/SPIDER_MAX_RECORDS) * 100, 2).'%, Memory:'.Tools::convertSzie(memory_get_usage()).', Peak:'.Tools::convertSzie(memory_get_peak_usage()).']'.PHP_EOL;
		
        //递归中 提交事务
        addLog('spider_while_'.$table);
		
		//休息，单位：微妙
		usleep(mt_rand(MIN_SLEEP_TIME, MAX_SLEEP_TIME));
		
		//记时Innodb事务锁定的时间
		$microtime = microtime(true);
		
        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }

    //提交事务
    addLog('spider_'.$table);
}

//3.分析目录数据
function analysisCategoryData(){
    global $db,$crawlers;
    $db->beginTransaction();//开启事务

    $sql = 'SELECT `id`, `url`, `content` FROM `'.TABLE_CATEGORY.'` WHERE `state` = 1 LIMIT 0, '.ANALYSIS_LIMIT.' FOR UPDATE';//获取未分析的记录（已采集）

    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
        foreach($urlArray as $v){
            $breadcrumb = Tools::getStr($v['content'], SPIDER_CATEGORY_CRUMB_CONTENT_BOF, SPIDER_CATEGORY_CRUMB_CONTENT_EOF);

            $name = explode(SPIDER_CATEGORY_CRUMB_EXPLODE, $breadcrumb);
            $name = end($name);
			$name = trim(Validate::strip_tags($name));
			
            $parent = Tools::getUrlFromStr($breadcrumb);
            $parent = (int)$db->fetchColumn('SELECT `id` FROM `'.TABLE_CATEGORY.'` WHERE `urlCrc32` = '.Validate::crc32(end($parent)).'');

            $description = Tools::getStr($v['content'], SPIDER_CATEGORY_DESCRIPTION_CONTENT_BOF, SPIDER_CATEGORY_DESCRIPTION_CONTENT_EOF);

            $metaTitle = Tools::getStr($v['content'], SPIDER_CATEGORY_MATA_TITLE_CONTENT_BOF, SPIDER_CATEGORY_MATA_TITLE_CONTENT_EOF);
            $metaKeywords = Tools::getStr($v['content'], SPIDER_CATEGORY_MATA_KEYWORDS_CONTENT_BOF, SPIDER_CATEGORY_MATA_KEYWORDS_CONTENT_EOF);
            $metaDescription = Tools::getStr($v['content'], SPIDER_CATEGORY_MATA_DESCRIPTION_CONTENT_BOF, SPIDER_CATEGORY_MATA_DESCRIPTION_CONTENT_EOF);
			
            $image = completeUrl(current(Tools::getImgFromStr(Tools::getStr($v['content'], SPIDER_CATEGORY_IMG_CONTENT_BOF, SPIDER_CATEGORY_IMG_CONTENT_EOF))));
			
			if(SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_MORE_CONTENT_BOF){
				$productNum = (int)Tools::getStr($v['content'], SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_MORE_CONTENT_BOF, SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_MORE_CONTENT_EOF);
				if(!$productNum){
					$productNum = (int)Tools::getStr($v['content'], SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_LESS_CONTENT_BOF, SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_LESS_CONTENT_EOF);
				}
				$pageNum = ceil($productNum/SPIDER_PRODUCT_LIST_PER_NUM);
			}else{
				$productNum = 0;
				$pageNum = 0;
				
				while(!$pageNum){
					addCurlOption();
					$crawlers->setModeToContent();
					preg_match_all('/'.SPIDER_CATEGORY_PAGING_MAX_NUM_REG.'/isu', Tools::getStr($crawlers->spider($v['url'].(strpos($v['url'], '?') ? '&' : '?').sprintf(SPIDER_PRODUCT_LIST_PAGING_STR, 10000))->content, SPIDER_CATEGORY_PAGING_CONTENT_BOF, SPIDER_CATEGORY_PAGING_CONTENT_EOF), $pageNum);
					$pageNum = (int)end($pageNum[1]);
					
					//休息，单位：微妙
					usleep(mt_rand(MIN_SLEEP_TIME, MAX_SLEEP_TIME));
				}
			}
			            
            //已分析
            $db->update(TABLE_CATEGORY, array(
                'Name' => clean($name),
                'Parent category' => $parent,
                'Description' => clean($description),
                'Meta title' => clean($metaTitle),
                'Meta keywords' => clean($metaKeywords),
                'Meta description' => clean($metaDescription),
                'Image' => $image,
                'state' => 2,
                'pageNum' => $pageNum,
                'productNum' => $productNum
            ), '`id` = ' . $v['id']);
            
            //分析目录第一页的产品URL
            addProductUrl($v['content'], $v['id']);

            echo TABLE_CATEGORY.' '.$v['id'].' Analysis OK. '.PHP_EOL;
        }
		
		echo '['.$times.']. Times Analysis.'.PHP_EOL;
         
        //递归中 提交事务
        addLog('analysis_while_'.TABLE_CATEGORY);

        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }

    //提交事务
    addLog('analysis_'.TABLE_CATEGORY);
}

//4.分析目录分页Url
function analysisCategoryPageUrl(){
	global $db;
    $db->beginTransaction();//开启事务
    
    $sql = 'SELECT `id`, `pageNum`, `url` FROM `'.TABLE_CATEGORY.'` WHERE `state` = 2 LIMIT 0, '.ANALYSIS_LIMIT.' FOR UPDATE';//获取未分页的记录（已分析）
    
    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
        foreach($urlArray as $v){
            for($page = 2; $page <= $v['pageNum']; $page ++){
				$url = $v['url'].(strpos($v['url'], '?') ? '&' : '?').sprintf(SPIDER_PRODUCT_LIST_PAGING_STR, $page);
				$urlCrc32 = Validate::crc32($url);
				if(!$db->fetchColumn('SELECT `id` FROM `'.TABLE_PAGE.'` WHERE `urlCrc32` = "'.$urlCrc32.'"')){
					$db->insert(TABLE_PAGE, array('categoryId' => $v['id'], 'url' => $url, 'urlCrc32' => $urlCrc32));
					echo 'Page '.$page.'/'.$v['pageNum'].'.'.PHP_EOL;
				}
            }
            $db->update(TABLE_CATEGORY, array('state' => 3), '`id` = '.$v['id']);//OK：已分页
        }
		
		echo '['.$times.']. Times Analysis. Category '.$v['id'].'.'.PHP_EOL;
        	
        //递归中 提交事务
        addLog('analysis_page_while_'.TABLE_CATEGORY);
    
        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }
    
    //提交事务
    addLog('analysis_page_'.TABLE_CATEGORY);
}

//5.采集分页内容，方法同spiderContentByUrl

//6.提取产品URL
function analysisProductPageUrl(){
    global $db;
    $db->beginTransaction();//开启事务

    $sql = 'SELECT `id`, `categoryId`, `content` FROM `'.TABLE_PAGE.'` WHERE `state` = 1 LIMIT 0, '.ANALYSIS_LIMIT.' FOR UPDATE';//获取未分析的记录（已采集）

    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
        foreach($urlArray as $v){
            $db->update(TABLE_PAGE, array('state' => 2), '`id` = '.$v['id']);//已分析

            addProductUrl($v['content'], $v['categoryId']);//产品URL入库

            echo TABLE_PAGE.' '.$v['id'].' Analysis OK. '.PHP_EOL;
        }
		
		echo '['.$times.']. Times Analysis.'.PHP_EOL;
		
        //递归中 提交事务
        addLog('analysis_while_'.TABLE_PAGE);

        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }

    //提交事务
    addLog('analysis_'.TABLE_PAGE);
}

//7.采集产品内容，方法同spiderContentByUrl

//8.分析产品数据
function analysisProductData(){
    global $db;
    $db->beginTransaction();//开启事务
    
    $sql = 'SELECT `id`, `content` FROM `'.TABLE_PRODUCT.'` WHERE `state` = 1 LIMIT 0, '.ANALYSIS_LIMIT.' FOR UPDATE';//获取未分析的记录（已采集）
    
    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
        foreach($urlArray as $v){
			$name = Tools::getStr($v['content'], SPIDER_PRODUCT_NAME_CONTENT_BOF, SPIDER_PRODUCT_NAME_CONTENT_EOF);
			$price = getPriceNum(Tools::getStr($v['content'], SPIDER_PRODUCT_PRICE_CONTENT_BOF, SPIDER_PRODUCT_PRICE_CONTENT_EOF));
			$wholesalePrice = getPriceNum(Tools::getStr($v['content'], SPIDER_PRODUCT_WHOLESALE_PRICE_CONTENT_BOF, SPIDER_PRODUCT_WHOLESALE_PRICE_CONTENT_EOF));
			$discountAmount = getPriceNum(Tools::getStr($v['content'], SPIDER_PRODUCT_DISCOUNT_AMOUNT_CONTENT_BOF, SPIDER_PRODUCT_DISCOUNT_AMOUNT_CONTENT_EOF));
			$discountPercent = getPriceNum(Tools::getStr($v['content'], SPIDER_PRODUCT_DISCOUNT_PERCENT_CONTENT_BOF, SPIDER_PRODUCT_DISCOUNT_PERCENT_CONTENT_EOF));
			$reference = Tools::getStr($v['content'], SPIDER_PRODUCT_REFERENCE_CONTENT_BOF, SPIDER_PRODUCT_REFERENCE_CONTENT_EOF);
			$manufacturer = Tools::getStr($v['content'], SPIDER_PRODUCT_MANUFACTURER_CONTENT_BOF, SPIDER_PRODUCT_MANUFACTURER_CONTENT_EOF);
			$weight = getPriceNum(Tools::getStr($v['content'],SPIDER_PRODUCT_WEIGHT_CONTENT_BOF, SPIDER_PRODUCT_WEIGHT_CONTENT_EOF));
			$shortDescription = Tools::getStr($v['content'], SPIDER_PRODUCT_SHORT_DESCRIPTION_CONTENT_BOF, SPIDER_PRODUCT_SHORT_DESCRIPTION_CONTENT_EOF);
			$description = Tools::getStr($v['content'], SPIDER_PRODUCT_DESCRIPTION_CONTENT_BOF, SPIDER_PRODUCT_DESCRIPTION_CONTENT_EOF);
			$tags = Tools::getStr($v['content'], SPIDER_PRODUCT_TAGS_CONTENT_BOF, SPIDER_PRODUCT_TAGS_CONTENT_EOF);
			if($tags){
				$tags = join(',', array_map('trim', array_map('Validate::strip_tags', explode(SPIDER_PRODUCT_TAGS_EXPLODE, $tags))));
			}
            $metaTitle = Tools::getStr($v['content'], SPIDER_PRODUCT_MATA_TITLE_CONTENT_BOF, SPIDER_PRODUCT_MATA_TITLE_CONTENT_EOF);
            $metaKeywords = Tools::getStr($v['content'], SPIDER_PRODUCT_MATA_KEYWORDS_CONTENT_BOF, SPIDER_PRODUCT_MATA_KEYWORDS_CONTENT_EOF);
            $metaDescription = Tools::getStr($v['content'], SPIDER_PRODUCT_MATA_DESCRIPTION_CONTENT_BOF, SPIDER_PRODUCT_MATA_DESCRIPTION_CONTENT_EOF);
			
			if(preg_match_all('/'.SPIDER_PRODUCT_IMG_URL_REG.'/isu', Tools::getStr($v['content'], SPIDER_PRODUCT_IMG_CONTENT_BOF, SPIDER_PRODUCT_IMG_CONTENT_EOF), $image) > 0){
				$image = array_map('completeUrl', array_unique($image[1]));
			}else{
				$image = array();
			}
			
			$feature = Tools::getStr($v['content'], SPIDER_PRODUCT_FEATURE_CONTENT_BOF, SPIDER_PRODUCT_FEATURE_CONTENT_EOF);
			            
            //已分析
            $db->update(TABLE_PRODUCT, array(
                'Name' => clean($name),
                'Price' => $price,
                'Wholesale price' => $wholesalePrice,
                'Discount amount' => $discountAmount,
                'Discount percent' => $discountPercent,
                'Reference' => clean($reference),
                'Manufacturer' => clean($manufacturer),
                'Weight' => $weight,
                'Short description' => clean($shortDescription),
                'Description' => clean($description),
				'Tags' => clean($tags),
				'Meta title' => clean($metaTitle),
				'Meta keywords' => clean($metaKeywords),
				'Meta description' => clean($metaDescription),
				'Feature' => clean($feature),
            ), '`id` = ' . $v['id']);
			
			if(!empty($image)){
				$db->update(TABLE_PRODUCT, array('Image' => join(',', $image), 'state' => 2), '`id` = ' . $v['id']);
			}else{
				$db->update(TABLE_PRODUCT, array('Image' => '', 'state' => 3), '`id` = ' . $v['id']);
			}
			
            echo TABLE_PRODUCT.' '.$v['id'].' Analysis OK. '.PHP_EOL;
        }
		
		echo '['.$times.']. Times Analysis.'.PHP_EOL;
         
        //递归中 提交事务
        addLog('analysis_while_'.TABLE_PRODUCT);
    
        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }
    
    //提交事务
    addLog('analysis_'.TABLE_PRODUCT);
}

//9.采集图片[注意：目录的state值未同步]
function spiderImage($table){
	global $db, $crawlers, $productDir;
	
	$db->beginTransaction();//开启事务

    $sql = 'SELECT `id`, `Image` FROM `'.$table.'` WHERE `state` = 2 LIMIT 0, '.SPIDER_MAX_RECORDS.' FOR UPDATE';//获取未采集的记录

    $times = 1;
    while($urlArray = $db->fetchAll($sql)){
		$imageArray = array();
		foreach($urlArray as $array){
			foreach(explode(',', $array['Image']) as $k => $v){
				$imageArray[$productDir.$array['id'].'_'.$k.'.jpg'] = $v;
			}
		}
		
		addCurlOption();
		$crawlers->setModeToContent();
		$crawlers->options[CURLOPT_TIMEOUT] = 3600;
		
		$imageUrls = array();
		foreach($crawlers->spiders($imageArray, array_combine(array_keys($imageArray), array_keys($imageArray))) as $k => $v){
			if($v->error || !Image::compress($k)){
				@unlink($k);
				if($v->error && isset($crawlers->options[CURLOPT_PROXY])){
					$db->query('UPDATE `'.TABLE_PROXY.'` SET `times` = `times` + 1 WHERE `address` = ?', array($crawlers->options[CURLOPT_PROXY]));
				}
				$imageUrls[preg_replace('/[\.|\/|a-z]|(_\d+)/isu', '', $k)][] = $v->info->http_code;
			}else{
				$imageUrls[preg_replace('/[\.|\/|a-z]|(_\d+)/isu', '', $k)][] = $k;
			}
		}
		
		foreach($imageUrls as $id => $imageUrls){
			if(!empty($imageUrls)){
				if(count($imageUrls) === 1 && key(array_flip($imageUrls)) === 404){
					$db->update($table, array('state' => 3, 'Image Urls' => '404'), '`id` = '.$id);//OK：已采集
				}else{
					$db->update($table, array('state' => 3, 'Image Urls' => join(',', $imageUrls)), '`id` = '.$id);//OK：已采集
				}
				echo $table.' '.$id.' Spider Image OK.'.PHP_EOL;
			}else{
				$db->update($table, array('Image Urls' => $v->error), '`id` = '.$id);//失败：记录当前采集到的内容
				echo $table.' '.$id.' Spider Image Fail!['.$v->error.']'.PHP_EOL;
			}
		}
		
		echo '['.$times.']. Times Spider.'.PHP_EOL;

        //递归中 提交事务
        addLog('spider_while_'.$table);
		
		//休息，单位：微妙
		usleep(mt_rand(MIN_SLEEP_TIME, MAX_SLEEP_TIME));

        //递归中 开始事务
        $db->beginTransaction();
		
        $times++;
    }

    //提交事务
    addLog('spider_'.$table);
}

echo 'Init Version '.VERSION.'...'.PHP_EOL;

//初始化数据库
$db = new Db('mysql:host=127.0.0.1;dbname=spider', 'root', 'letwangletwangletwang123', array(PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_AUTOCOMMIT => false, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

//初始化采集类
$crawlers = new Curl(array(CURLOPT_NOPROGRESS => true, CURLOPT_TIMEOUT => SPIDER_TIME_OUT));
$crawlers->setCookie(ROOT.'/cookies_'.getmypid().'.txt');//保存并读取Cookie，且可以真实模拟不同终端客户的有效访问

//探测性访问，只为生成Cookies文件且防止ZenCart类网站有Zenid的困扰
$crawlers->setModeToTest();
$crawlers->spider(SPIDER_URL);

//1.采集目录URL：判断数据库中的目录URL是否已经存在，如果不存在，则全新采集目录URL
echo '1/9.Spider Category Url.'.PHP_EOL;
if(!$db->fetchColumn('SELECT `id` FROM `'.TABLE_CATEGORY.'`')){
	echo 'From New Data.'.PHP_EOL;
	//初始化目录表
    $db->query('TRUNCATE TABLE `'.TABLE_CATEGORY.'`');
    $db->query('ALTER TABLE `'.TABLE_CATEGORY.'` AUTO_INCREMENT = '.TABLE_C_INIT_ID.'');
	//初始化分页表
	$db->query('TRUNCATE TABLE `'.TABLE_PAGE.'`');
	//初始化产品表
	$db->query('TRUNCATE TABLE `'.TABLE_PRODUCT.'`');
	$db->query('ALTER TABLE `'.TABLE_PRODUCT.'` AUTO_INCREMENT = '.TABLE_P_INIT_ID.'');
	//采集目录URL
    spiderCategoryUrl();
}else{
	echo 'From Old Data.'.PHP_EOL;
}

//2.采集目录内容：根据数据库中目录URL采集目录内容.
echo '2/9.Spider Category Data.'.PHP_EOL;
$db->query('UPDATE `'.TABLE_CATEGORY.'` SET `state` = 0 WHERE `content` NOT LIKE ? AND `state` > 0', array('%'.SPIDER_CATEGORY_CRUMB_CONTENT_BOF.'%'));//清理脏数据
spiderContentByUrl(TABLE_CATEGORY);

//3.分析目录数据：分析目录采集内容到各个数据字段.
echo '3/9.Analysis Category Data.'.PHP_EOL;
analysisCategoryData();

//4.分析目录分页Url：对已分析后的目录进行分页获取目录分页URL.
echo '4/9.Analysis Category Page Url.'.PHP_EOL;
analysisCategoryPageUrl();

//5.采集分页内容：采集目录分页URL数据.
echo '5/9.Spider Category Page Data.'.PHP_EOL;
$db->query('UPDATE `'.TABLE_PAGE.'` SET `state` = 0 WHERE `content` NOT LIKE ? AND `state` > 0', array('%'.SPIDER_CATEGORY_CRUMB_CONTENT_BOF.'%'));//清理脏数据
spiderContentByUrl(TABLE_PAGE);

//6.提取产品URL：从目录分页数据中提取产品URL.
echo '6/9.Analysis Product Url.'.PHP_EOL;
analysisProductPageUrl();

//7.采集产品内容：根据数据库中产品URL采集产品内容.
echo '7/9.Spider Product Data.'.PHP_EOL;
$db->query('UPDATE `'.TABLE_PRODUCT.'` SET `state` = 0 WHERE `content` NOT LIKE ? AND `state` > 0', array('%'.SPIDER_PRODUCT_NAME_CONTENT_BOF.'%'));//清理脏数据
spiderContentByUrl(TABLE_PRODUCT);

//8.分析产品数据：分析产品采集内容到各个数据字段.
echo '8/9.Analysis Product Data.'.PHP_EOL;
analysisProductData();

//9.采集产品图片
echo '9/9.Spider Product Image.'.PHP_EOL;
spiderImage(TABLE_PRODUCT);

echo PHP_EOL.'===========================Version:'.VERSION.'===============================';
echo PHP_EOL.'Time:		'.Tools::convertTime(microtime(true) - TIME, 4).'		End: 	'.date('Y-m-d H:i:s');
echo PHP_EOL.'Memory:		'.Tools::convertSzie(memory_get_usage(true)).'		Peak:	'.Tools::convertSzie(memory_get_peak_usage(true));
echo PHP_EOL.'===========================Version:'.VERSION.'===============================';