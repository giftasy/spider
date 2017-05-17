<?php
const SPIDER_URL = 'http://www.2015handbagmall.com/site_map.html';

const SPIDER_CATEGORY_LIST_CONTENT_BOF = '<div id="siteMapList">';
const SPIDER_CATEGORY_LIST_CONTENT_EOF = '<div class="buttonRow back">';

const SPIDER_CATEGORY_URL_REG = 'href="([^"]*?-c-[^"]*)"';
const SPIDER_PRODUCT_URL_REG = 'href="([^"]*?-p-[^"]*)"';

const SPIDER_PRODUCT_LIST_PER_NUM = 21;
const SPIDER_PRODUCT_LIST_PAGING_STR = 'page=%u&sort=20a';
const SPIDER_PRODUCT_LIST_CONTENT_BOF = '<div id="productListing">';
const SPIDER_PRODUCT_LIST_CONTENT_EOF = '<div id="productsListingBottomNumber"';

const SPIDER_CATEGORY_CRUMB_CONTENT_BOF = '<!-- bof  breadcrumb -->';
const SPIDER_CATEGORY_CRUMB_CONTENT_EOF = '<!-- eof breadcrumb -->';
const SPIDER_CATEGORY_CRUMB_EXPLODE = '&nbsp;::&nbsp;';
const SPIDER_CATEGORY_DESCRIPTION_CONTENT_BOF = '<div id="categoryDescription" class="catDescContent">';
const SPIDER_CATEGORY_DESCRIPTION_CONTENT_EOF = '<\/div>';
const SPIDER_CATEGORY_IMG_CONTENT_BOF = '';
const SPIDER_CATEGORY_IMG_CONTENT_EOF = '';

const SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_MORE_CONTENT_BOF = 'of <strong>';
const SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_MORE_CONTENT_EOF = '<\/strong>';
const SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_LESS_CONTENT_BOF = '';
const SPIDER_CATEGORY_PRODUCT_TOTAL_NUM_LESS_CONTENT_EOF = '';

const SPIDER_CATEGORY_PAGING_CONTENT_BOF = '';
const SPIDER_CATEGORY_PAGING_CONTENT_EOF = '';
const SPIDER_CATEGORY_PAGING_MAX_NUM_REG = '';

const SPIDER_PRODUCT_NAME_CONTENT_BOF = '<h1 id="productName" class="productGeneral">';
const SPIDER_PRODUCT_NAME_CONTENT_EOF = '<\/h1>';
const SPIDER_PRODUCT_PRICE_CONTENT_BOF = '<h2 id="productPrices" class="productGeneral">';
const SPIDER_PRODUCT_PRICE_CONTENT_EOF = '<\/h2>';
const SPIDER_PRODUCT_REFERENCE_CONTENT_BOF = 'Model: <\/span>';
const SPIDER_PRODUCT_REFERENCE_CONTENT_EOF = '<\/li>';
const SPIDER_PRODUCT_SHORT_DESCRIPTION_CONTENT_BOF = '';
const SPIDER_PRODUCT_SHORT_DESCRIPTION_CONTENT_EOF = '';
const SPIDER_PRODUCT_DESCRIPTION_CONTENT_BOF = '<div id="productDescription" class="productGeneral biggerText">';
const SPIDER_PRODUCT_DESCRIPTION_CONTENT_EOF = '<\/div>';
const SPIDER_PRODUCT_IMG_CONTENT_BOF = '<!--bof Main Product Image -->';
const SPIDER_PRODUCT_IMG_CONTENT_EOF = '<!--eof Additional Product Images -->';
const SPIDER_PRODUCT_IMG_URL_REG = 'src="([^"]*\.jpg)"';

//数据过滤，基于preg_replace正则数组对替换原理
const DISABLE_WORD_PATTERN = array();
const DISABLE_WORD_REPLACEMENT = array();

//通用，一般都无需改动
const SPIDER_CATEGORY_MATA_TITLE_CONTENT_BOF = '<title>';
const SPIDER_CATEGORY_MATA_TITLE_CONTENT_EOF = '<\/title>';
const SPIDER_CATEGORY_MATA_KEYWORDS_CONTENT_BOF = '<meta name="keywords" content="';
const SPIDER_CATEGORY_MATA_KEYWORDS_CONTENT_EOF = '" \/>';
const SPIDER_CATEGORY_MATA_DESCRIPTION_CONTENT_BOF = '<meta name="description" content="';
const SPIDER_CATEGORY_MATA_DESCRIPTION_CONTENT_EOF = '" \/>';

const SPIDER_PRODUCT_MATA_TITLE_CONTENT_BOF = '<title>';
const SPIDER_PRODUCT_MATA_TITLE_CONTENT_EOF = '<\/title>';
const SPIDER_PRODUCT_MATA_KEYWORDS_CONTENT_BOF = '<meta name="keywords" content="';
const SPIDER_PRODUCT_MATA_KEYWORDS_CONTENT_EOF = '" \/>';
const SPIDER_PRODUCT_MATA_DESCRIPTION_CONTENT_BOF = '<meta name="description" content="';
const SPIDER_PRODUCT_MATA_DESCRIPTION_CONTENT_EOF = '" \/>';

const SPIDER_PRODUCT_WHOLESALE_PRICE_CONTENT_BOF = '';
const SPIDER_PRODUCT_WHOLESALE_PRICE_CONTENT_EOF = '';

const SPIDER_PRODUCT_DISCOUNT_AMOUNT_CONTENT_BOF = '';
const SPIDER_PRODUCT_DISCOUNT_AMOUNT_CONTENT_EOF = '';

const SPIDER_PRODUCT_DISCOUNT_PERCENT_CONTENT_BOF = '';
const SPIDER_PRODUCT_DISCOUNT_PERCENT_CONTENT_EOF = '';

const SPIDER_PRODUCT_WEIGHT_CONTENT_BOF = '';
const SPIDER_PRODUCT_WEIGHT_CONTENT_EOF = '';

const SPIDER_PRODUCT_MANUFACTURER_CONTENT_BOF = '';
const SPIDER_PRODUCT_MANUFACTURER_CONTENT_EOF = '';

const SPIDER_PRODUCT_TAGS_CONTENT_BOF = '';
const SPIDER_PRODUCT_TAGS_CONTENT_EOF = '';
const SPIDER_PRODUCT_TAGS_EXPLODE = '';

const SPIDER_PRODUCT_FEATURE_CONTENT_BOF = '';
const SPIDER_PRODUCT_FEATURE_CONTENT_EOF = '';

//系统，一般无需改动
const VERSION = '1.0';//软件版本

const DB_PREFIX = '';//表前缀

const TABLE_CATEGORY = 'category_bag';//目录表的名称
const TABLE_PRODUCT = 'product_bag';//产品表的名称
const TABLE_PAGE = 'page_bag';//目录分页表的名称
const TABLE_PROXY = 'proxy';//代理IP表的名称

const TABLE_C_INIT_ID = 3;//目录起始ID
const TABLE_P_INIT_ID = 1;//产品起始ID

const ANALYSIS_LIMIT = 100;//每批分析多少条本地大数据

const SPIDER_TIME_OUT = 120;//采集超时秒数
const SPIDER_MAX_RECORDS = 20;//每批最多采集多少URL
const SPIDER_MAX_PROXY_FAIL_TIMES = 10;//代理IP最大失败次数，超过此数值将不出现在采集代理IP列表中

const MIN_SLEEP_TIME = 1000;//每批采集后最少休息多少微妙
const MAX_SLEEP_TIME = 10000;//每批采集后最多休息多少微妙