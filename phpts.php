$urls = array(
    'http://http://www.caizhuangguoji.com/',
    'http://www.caizhuangguoji.com/Home/Spike/detail/sp_id/2',
    'http://www.caizhuangguoji.com/Home/Products/index/bid/32',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/31',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/30',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/29',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/28',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/26',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/25',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/24',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/23',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/22',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/21',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/20',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/19',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/18',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/16',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/15',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/14',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/13',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/12',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/11',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/10',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/9',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/8',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/7',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/6',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/5',
	'http://www.caizhuangguoji.com/Home/Products/index/bid/4',
	'http://www.caizhuangguoji.com/Home/Spike/detail/sp_id/3',
	'http://www.caizhuangguoji.com/Home/Spike/detail/sp_id/3',
);
$api = 'http://data.zz.baidu.com/urls?site=www.caizhuangguoji.com&token=VU6EYM3UZ1Im0vle';
$ch = curl_init();
$options =  array(
    CURLOPT_URL => $api,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => implode("\n", $urls),
    CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
);
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
echo $result;