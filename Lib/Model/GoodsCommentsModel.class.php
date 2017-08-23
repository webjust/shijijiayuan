<?php
/**
 *
 * @author WangHaoYu <why419163@163.com>
 * @version 7.2
 * @date 2013-08-31
 * @param $int_gcom_id 评论的id 
 * @param $ary_fields  评论表里的字段 默认为所有字段
 * @param  返回二维数组 
 */

class GoodsCommentsModel extends GyfxModel {
	public function getGoodsComments($int_gcom_id,$ary_fields="*"){
		return $this->where(array('gcom_id'=>$int_gcom_id))->field($ary_fields)->find();
	}	
    /**
     * 获取商品评论数
     * @author Nick <shanguangkun>
     * @date 2014-06-11
     */
    public function getGoodCommentsCount($int_g_id){
		return $this->where(array('fx_goods_comments.g_id'=>$int_g_id))->count();
	}
	
	/**
	 * 添加评论到评论统计表
	 * @param $ary_comment
	 * @return bool
	 */
	public function addGoodsCommentStatistics($ary_comment) {

		if(!$ary_comment['gcom_status']) return false;

		$ary_gcs = M('goods_comment_statistics', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
			'g_id' => $ary_comment['g_id'],
		))->find();
		$gcom_star_score = $ary_comment['gcom_star_score'];
		$five_star_count = $four_star_count = $three_star_count = $two_star_count = $one_star_count = 0;
		$pic_comment_count = 0;
		switch($gcom_star_score) {
			case 20:
				$one_star_count ++;
				break;
			case 40:
				$two_star_count ++;
				break;
			case 60:
				$three_star_count ++;
				break;
			case 80:
				$four_star_count ++;
				break;
			case 100:
				$five_star_count ++;
				break;
		}
		//晒单图片
		$gcom_pics = trim($ary_comment['gcom_pics']);
		if(!empty($gcom_pics)) {
			$pic_comment_count++;
		}

		if(!empty($ary_gcs)) {

			$data = array(
				'one_star_count' => $ary_gcs['one_star_count'] + $one_star_count,
				'two_star_count' => $ary_gcs['two_star_count'] + $two_star_count,
				'three_star_count' => $ary_gcs['three_star_count'] + $three_star_count,
				'four_star_count' => $ary_gcs['four_star_count'] + $four_star_count,
				'five_star_count' => $ary_gcs['five_star_count'] + $five_star_count,
				'total_count' => $ary_gcs['total_count'] + 1,
				'pic_comment_count' => $ary_gcs['pic_comment_count'] + $pic_comment_count,
			);

		}else {
			$data = array(
				'g_id' => $ary_comment['g_id'],
				'one_star_count' => $one_star_count,
				'two_star_count' => $two_star_count,
				'three_star_count' => $three_star_count,
				'four_star_count' => $four_star_count,
				'five_star_count' => $five_star_count,
				'total_count' => 1,
				'pic_comment_count' => $pic_comment_count,
			);
		}
		//平均评论得分
		$average_score = ($data['one_star_count']*20 + $data['two_star_count']*40 +
				$data['three_star_count']*60 + $data['four_star_count']*80 + $data['five_star_count']*100)/$data['total_count'];
		$data['average_score'] = (int) $average_score;
		//好评率，4颗星5颗星都算好评
		$positive_ratio = ($data['four_star_count'] + $data['five_star_count'])/$data['total_count'];
		$data['positive_ratio'] = $positive_ratio;
		//最近一个月评论数
		$last_month = date('Y-m-d', strtotime('-30 days')) . ' 00:00:00';
		$data['last_month_count'] = $this->where(array(
			'g_id' => $ary_comment['g_id'],
			'gcom_status' => 1,
			'gcom_create_time' => array('GT', $last_month),
		))->count('gcom_id');
		//最近两个月评论数
		$last_two_month = date('Y-m-d', strtotime('-60 days')) . ' 00:00:00';
		$data['two_months_count'] = $this->where(array(
			'g_id' => $ary_comment['g_id'],
			'gcom_status' => 1,
			'gcom_create_time' => array('GT', $last_two_month),
		))->count('gcom_id');
		//最近三个月评论数
		$last_three_month = date('Y-m-d', strtotime('-90 days')) . ' 00:00:00';
		$data['three_months_count'] = $this->where(array(
			'g_id' => $ary_comment['g_id'],
			'gcom_status' => 1,
			'gcom_create_time' => array('GT', $last_three_month),
		))->count('gcom_id');

		//好评数
		$data['positive_count'] = $data['four_star_count'] + $data['five_star_count'];
		$gcs_id = false;
		if(!empty($ary_gcs)) {
			$res = M('goods_comment_statistics', C('DB_PREFIX'), 'DB_CUSTOM')->where(array(
				'g_id' => $ary_comment['g_id'],
			))->data($data)->save();
			if($res) {
				$gcs_id = $ary_comment['g_id'];
			}
		}else {
			$gcs_id = M('goods_comment_statistics', C('DB_PREFIX'), 'DB_CUSTOM')->add($data);
		}

		return $gcs_id;
	}
}