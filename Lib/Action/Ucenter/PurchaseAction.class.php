<?php

class PurchaseAction extends CommonAction
{
    private function skin()
    {
        $str_header_include_file = FXINC . '/Tpl/Ucenter/Common/oHeader.html';

        $this->assign("srt_inc_footer", '');
        $this->assign("str_header_include_file", $str_header_include_file);
    }

    public function PendingList()
    {
        $M = M('');
        $member = session("Members");

        $condition = 'status=0 and to_m_id=' . $member['m_id'];

        $count = M('in_pending')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $List = M('in_pending')
            ->where($condition)->limit($limit['start'], $limit['end'])->select();
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display(FXINC . '/Tpl/Ucenter/Supplier/PendingList.html');
    }

    /*
     * 个人中心→供应商商品列表页展示
     * */
    public function supplierGoodsList()
    {
        $brand = trim($_GET['brand']);
        $condition = '1';
        if ($brand) {
            // 根据品牌名称进行查询
            $condition .= ' and brand_en like "%' . $brand . '%"';
            $this->assign('brand', $brand);
        }
        $name = trim($_GET['name']);
        if ($name) {
            // 根据商品的名称查询，匹配字段包括：中文名或者英文名
            $condition .= ' and (language_country like "%' . $name . '%" or name_cn like "%' . $name . '%" or name_en like "%' . $name . '%")';
            $this->assign('name', $name);
        }

        // 匹配当前商品状态
        $match = trim($_GET['match']);
        if ($match) {
            if ($match == '1') {
                // 未识别或识别不到 (mnc_in_id, mn_in_id, mc_in_id, nc_in_id, m_in_id, n_in_id, c_in_id 字段全部为空)
                $condition .= ' and mnc_in_id is null and mn_in_id is null and mc_in_id is null and nc_in_id is null and m_in_id is null and n_in_id is null and c_in_id is null';
            } elseif ($match == '2') {
                // 系统识别过且未审核 (in_id为空, mnc_in_id, mn_in_id, mc_in_id, nc_in_id, m_in_id, n_in_id, c_in_id有一个不为空)
                $condition .= ' and in_id is null and (0 or mnc_in_id is not null or mn_in_id is not null or mc_in_id is not null or nc_in_id is not null or m_in_id is not null or n_in_id is not null or n_in_id is not null)';
            } elseif ($match == '3') {
                // 已人工审核（in_id不为空,也叫：识别表id）
                $condition .= ' and in_id is not null';
            }
            $this->assign('match', $match);
        }

        $count = M('in_goods_supplier')->where($condition)->count();
        $obj_page = new Page($count, 200);      // 每页显示200条
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $List = M('in_goods_supplier')
            ->where($condition)->limit($limit['start'], $limit['end'])->select();
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();      // 引入自定义的样式
        $this->display();
    }

    /*
     * 根据主键 id 删除指定的供应商商品
     * */
    public function ajaxDelSupplierGood()
    {
        $id = $_POST['id'];
        $condition = 'id=' . $id;
        $result = M('in_goods_supplier')->where($condition)->delete();
        if ($result) {
            echo json_encode(array('result' => true));
        } else {
            echo json_encode(array('result' => false));
        }
        exit;
    }

    /*
     * 根据主键 id 查询相似的产品
     * */
    public function signList()
    {
        $id = $_GET['id'];
        $findAll = isset($_GET['findAll']) ? 1 : 0;
        $brand = isset($_GET['brand']) ? $_GET['brand'] : null;

        $rs = M('in_goods_supplier')->where('id=' . $id)->find();
        $condition = '0';

        if ($rs['mnc_in_id']) {
            $condition .= ' or id=' . $rs['mnc_in_id'];
        }
        if ($rs['mn_in_id']) {
            $condition .= ' or id=' . $rs['mn_in_id'];
        }
        if ($rs['mc_in_id']) {
            $condition .= ' or id=' . $rs['mc_in_id'];
        }
        if ($rs['nc_in_id']) {
            $condition .= ' or id=' . $rs['nc_in_id'];
        }
        if ($rs['m_in_id']) {
            $condition .= ' or id=' . $rs['m_in_id'];
        }
        if ($rs['n_in_id']) {
            $condition .= ' or id=' . $rs['n_in_id'];
        }
        if ($rs['c_in_id']) {
            $condition .= ' or id=' . $rs['c_in_id'];
        }

        if ($findAll && $brand) {
            $condition .= " or brand_en like '%" . $brand . "%' or brand_cn like '%" . $brand . "%'";
        }

        // 如果没有匹配的结果，根据当前的品牌查询
        if (!($rs['mnc_in_id'] OR $rs['mn_in_id'] OR $rs['mc_in_id'] OR $rs['nc_in_id'] OR $rs['m_in_id'] OR $rs['n_in_id'] OR $rs['c_in_id'])) {
            $condition = "brand_en like '%" . $rs['brand_en'] . "%'";
        }
        $count = M('in_goods')->where($condition)->count();
        $obj_page = new Page($count, 500);      // 每页显示500条
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $List = M('in_goods')->where($condition)->limit($limit['start'], $limit['end'])->select();
//        echo M('in_goods')->getLastSql();
        $this->assign('List', $List);
        $this->assign('page', $page);
        $this->skin();
        $this->display();
    }

    /*
     * 根据 id 查询数据，返回 数据按照表格布局
     * */
    public function goodsSearchByid()
    {
        if ($this->isAjax()) {
            // 校验
            // 1. 接收查询的条件
            $id = trim($this->_post('id'));

            // 2. 查询
            $res = M('in_goods')->where('id = ' . $id)->select();

            // 3. 给查询后的数据排版
            if (!$res) {
                // 判断如果查询为 null, 返回的数据
                echo '';
            }
            // 4. 输出结果
            echo $this->formatGoodsToTable($res);
        } else {
            echo '';
        }
    }

    /*
     * 根据关键词查询数据，返回数据按照表格布局
     * 关键词关联：用材、自命名、主分类、分类一、分类二、分类三这几个字段
     * */
    public function goodsSearchByKeyword()
    {
        if ($this->isAjax()) {
            // 校验
            // 查询条件
            $keyword = trim($this->_post('keyword'));

            $condition = array();
            $condition['material'] = array('like', '%' . $keyword . '%');
            $condition['nomenclature'] = array('like', '%' . $keyword . '%');
            $condition['main_class'] = array('like', '%' . $keyword . '%');
            $condition['class1'] = array('like', '%' . $keyword . '%');
            $condition['class2'] = array('like', '%' . $keyword . '%');
            $condition['class3'] = array('like', '%' . $keyword . '%');
            $condition['unit'] = array('like', '%' . $keyword . '%');
            $condition['_logic'] = 'OR';


            // 给查询后的数据排版
//            echo M('in_goods')->getLastSql();

            $count = M('in_goods')->where($condition)->count();
            $obj_page = new Page($count, 500);
            // $page = $obj_page->show();
            $limit['start'] = $obj_page->firstRow;
            $limit['end'] = $obj_page->listRows;
            $ret = M('in_goods')->where($condition)->limit($limit['start'], $limit['end'])->select();
            echo '共有条' . $count . '记录, 仅显示前500条<br/>';

            echo $this->formatGoodsToTable($ret);

            // 输出结果
        }
    }

    /*
     * 将数据库查询的数组，转换为表格形式的html
     * */
    public function formatGoodsToTable($data)
    {
        if (!$data) {
            return "查无此id的数据";
        }

        $str .= '<table width="100%" class="tbList" border="1">';
        $str .= '<thead><tr><th>id</th><th>品牌属国</th><th>进口</th><th>国货</th><th>中文名英文名</th><th>英文名</th><th>主要功效</th><th>功效一</th><th>功效二</th><th>功效三</th><th>颜色</th><th>用材</th><th>自命名</th><th>主分类</th><th>分类一</th><th>分类二</th><th>分类三</th><th>单位含量</th><th>单位包装数量</th><th>规格</th><th>使用部位</th><th>平台一销售价</th><th>平台二销售价</th><th>平台一URL</th><th>平台二URL</th></tr></thead><tbody>';

        foreach ($data as $v) {
            $str .= "<tr><th>{$v['id']}</th><th>{$v['country']}</th><th>{$v['importation']}</th><th>{$v['home_made']}</th><th>{$v['brand_cn']}</th><th>{$v['brand_en']}</th>";
            $str .= '<th><div class="edit" id="main_fun-' . $v['id'] . '">';
            $str .= "{$v['main_fun']}</div></th><th>";
            $str .= '<div class="edit" id="fun1-' . $v['id'] . '">';
            $str .= "{$v['fun1']}</div></th><th>{$v['fun2']}</th><th>{$v['fun3']}</th><th>{$v['color']}</th><th>";
            $str .= '<div class="edit" id="material-' . $v['id'] . '">';
            $str .= "{$v['material']}</div></th><th>";
            $str .= '<div class="edit" id="nomenclature-' . $v['id'] . '">';
            $str .= "{$v['nomenclature']}</div></th><th>";
            $str .= '<div class="edit" id="main_fun-' . $v['id'] . '">';
            $str .= "{$v['main_class']}</div></th><th>";
            $str .= '<div class="edit" id="class1-' . $v['id'] . '">';
            $str .= "{$v['class1']}</div></th><th>";
            $str .= '<div class="edit" id="class2-' . $v['id'] . '">';
            $str .= "{$v['class2']}</div></th><th>";
            $str .= '<div class="edit" id="class3-' . $v['id'] . '">';
            $str .= "{$v['class3']}</div></th><th>";
            $str .= '<div class="edit" id="unit-' . $v['id'] . '">';
            $str .= "{$v['unit']}</div></th><th>";
            $str .= '<div class="edit" id="package-' . $v['id'] . '">';
            $str .= "{$v['package']}</div></th><th>";
            $str .= '<div class="edit" id="spec-' . $v['id'] . '">';
            $str .= "{$v['spec']}</div></th><th>";
            $str .= '<div class="edit" id="partused-' . $v['id'] . '">';
            $str .= "{$v['partused']}</div></th><th>";
            $str .= '<div class="edit" id="tmall_price-' . $v['id'] . '">';
            $str .= "{$v['tmall_price']}</div></th><th>";
            $str .= '<div class="edit" id="jumei_price-' . $v['id'] . '">';
            $str .= "{$v['jumei_price']}</div></th><th>";
            $str .= '<div class="edit" id="tmall_url-' . $v['id'] . '">';
            $str .= "<a href='{$v['tmall_url']}' class='website' target='_blank'>{$v['tmall_url']}</a></div></th><th>";
            $str .= '<div class="edit" id="jumei_url-' . $v['id'] . '">';
            $str .= "<a href='{$v['jumei_url']}' class='website' target='_blank'>{$v['jumei_url']}</a></div></th></tr>";
        }

        $str .= "</tbody></table>";

        $jsTpl = <<<EOT
<script>
    // 编辑
    $('.edit').editable('/Ucenter/Purchase/TableEdit/table/in_goods');
    
    // 选中当前行时，给当前行添加了背景色，其他行移除背景色    
    $('tr').click(function () {
        $(this).siblings('tr').removeClass('pink');
        $(this).addClass('pink');
    });
    
    $('.edit').click(function () {
        $(this).parents('tr').siblings('tr').removeClass('pink');
        $(this).parents('tr').addClass('pink');
    });
</script>
EOT;

        echo $str . $jsTpl;
    }

    public function AutoMatchV3()
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $brand = trim($_GET['brand']);

        $M = M('');
        $sql = 'select * from fx_in_goods where `brand_en` like "%' . $brand . '%"';
        $goods = $M->query($sql);
        if (!$goods) {
            $this->error("商品识别表没有这品牌。");
            exit();
        }

        foreach ($goods as $k1 => $v1) {
            $v1['unit'] = str_replace('ml', '', $v1['unit']);
            $v1['unit'] = str_replace('mL', '', $v1['unit']);
            $v1['unit'] = str_replace('ML', '', $v1['unit']);
            $v1['unit'] = str_replace('g', '', $v1['unit']);
            $where = 'brand_en like "%' . $brand . '%"';
            if ($v1['main_class'] || $v1['class1'] || $v1['class2'] || $v1['class3']) {
                $wherec = ' and (0';
                $class = $v1['main_class'] . ',' . $v1['class1'] . ',' . $v1['class2'] . ',' . $v1['class3'];
                $c = explode(',', $class);
                foreach ($c as $value) {
                    if ($value) {
                        $wherec .= ' or name_cn like "%' . trim($value) . '%"';
                    }
                }
                $wherec .= ')';
            }
            if ($v1['nomenclature']) {
                $wheren = ' and name_cn like "%' . trim($v1['nomenclature']) . '%"';
            }
            if ($v1['material']) {
                $wherem = ' and name_cn like "%' . trim($v1['material']) . '%"';
            }
            if ($wherem && $wheren && $wherec) {
                $this->AutoMatchSql($v1, $where . $wherem . $wheren . $wherec, 'mnc_in_id');
            }
            if ($wherem && $wheren) {
                $this->AutoMatchSql($v1, $where . $wherem . $wheren, 'mn_in_id');
            }
            if ($wherem && $wherec) {
                $this->AutoMatchSql($v1, $where . $wherem . $wherec, 'mc_in_id');
            }
            if ($wheren && $wherec) {
                $this->AutoMatchSql($v1, $where . $wheren . $wherec, 'nc_in_id');
            }
            if ($wherem) {
                $this->AutoMatchSql($v1, $where . $wherem, 'm_in_id');
            }
            if ($wheren) {
                $this->AutoMatchSql($v1, $where . $wheren, 'n_in_id');
            }
            if ($wherec) {
                $this->AutoMatchSql($v1, $where . $wherec, 'c_in_id');
            }
        }
        //$this->success("商品识别结束。", U("/Ucenter/Purchase/supplierGoodsList/brand/" . $brand));
    }

    private function AutoMatchSql($v1, $where, $id = 'in_id')
    {
        $M = M('');
        $sql = 'select * from fx_in_goods_supplier where ' . $where;
        $supplier = $M->query($sql);
        if ($supplier) {
            foreach ($supplier as $k2 => $v2) {
                $v2['unit'] = str_replace('ml', '', $v2['unit']);
                $v2['unit'] = str_replace('mL', '', $v2['unit']);
                $v2['unit'] = str_replace('ML', '', $v2['unit']);
                $v2['unit'] = str_replace('g', '', $v2['unit']);
                if ($v1['unit'] == $v2['unit']) {
                    if ($v1['tmall_price'] && $v1['jumei_price']) {
                        $TJ_price = $v1['tmall_price'] > $v1['jumei_price'] ? $v1['jumei_price'] : $v1['tmall_price'];
                    } elseif ($v1['tmall_price']) {
                        $TJ_price = $v1['tmall_price'];
                    } elseif ($v1['jumei_price']) {
                        $TJ_price = $v1['jumei_price'];
                    }

                    $in_id = $v1['id'];
                    // $g_sn = 'czs'.$in_id.rand(1000,9999);
                    // $sale_price = $TJ_price*0.99;
                    // $trade_price_cn = $v2['trade_price_ori']*0.0059;
                    // $profit = ($sale_price-$trade_price_cn)/$trade_price_cn;
                    //$sql = 'update fx_in_goods_supplier set sale_price="'.$sale_price.'",trade_price_cn="'.$trade_price_cn.'",profit="'.$profit.'",g_sn="'.$g_sn.'", TJ_price="'.$TJ_price.'",in_id='.$in_id.',tmall_price="'.$v1['tmall_price'].'",jumei_price="'.$v1['jumei_price'].'",tmall_url="'.$v1['tmall_url'].'",jumei_url="'.$v1['jumei_url'].'" where id='.$v2['id'];

                    //$sql .= 'update fx_in_goods_supplier set TJ_price="' . $TJ_price . '",' . $id . '=' . $in_id . ',tmall_price="' . $v1['tmall_price'] . '",jumei_price="' . $v1['jumei_price'] . '",tmall_url="' . $v1['tmall_url'] . '",jumei_url="' . $v1['jumei_url'] . '" where id=' . $v2['id'];

                    //$sql = 'update fx_in_goods_supplier set TJ_price="' . $TJ_price . '",' . $id . '=' . $in_id . ',tmall_price="' . $v1['tmall_price'] . '",jumei_price="' . $v1['jumei_price'] . '",tmall_url="' . $v1['tmall_url'] . '",jumei_url="' . $v1['jumei_url'] . '" where id=' . $v2['id'];
                    //$sql = 'update fx_in_goods_supplier set ' . $id . '="' . $in_id . '" where id=' . $v2['id'];

                    $sql = 'update fx_in_goods_supplier set ' . $id . '=concat_ws(",",'.$id.',"' . $in_id . '") where id=' . $v2['id'];

                    //echo $sql.'<br>';

                    //update XXX set mnc_id=concat_ws(',',mnc_id,'22');


                    $M->query($sql);
                }
            }
        }

    }

    /*
     * 商品识别表：(对应表 in_goods)
     * */
    public function signGoodsList()
    {
        $brand = trim($_GET['brand']);
        $condition = '';
        if ($brand) {
            $condition .= 'brand_en like "%' . $brand . '%"';
            $this->assign('brand', $brand);
        }

        $count = M('in_goods')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $List = M('in_goods')
            ->where($condition)->limit($limit['start'], $limit['end'])->select();
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    /*
 * 根据主键 id 删除指定的商品识别表商品
 * */
    public function ajaxDelGood()
    {
        $id = $_POST['id'];
        $condition = 'id=' . $id;
        $result = M('in_goods')->where($condition)->delete();
        if ($result) {
            echo json_encode(array('result' => true));
        } else {
            echo json_encode(array('result' => false));
        }
        exit;
    }

    /*
     * 供应商/商品识别表
     * */
    public function supplierMatchList()
    {
        $brand = trim($_GET['brand']);
        $condition = 'in_id>0';
        $where = 'gs.in_id>0';
        if ($brand) {
            $condition .= ' and brand_en like "%' . $brand . '%"';
            $where .= ' and gs.brand_en like "%' . $brand . '%"';
            $this->assign('brand', $brand);
        }

        $count = M('in_goods_supplier')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;

        $M = M('');

        $fileds = 'gs.id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
        $fileds .= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,';
        $fileds .= 'gs.all_stock,gs.base_stock,gs.supplier,gs.website,';
        $fileds .= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url';
        $sql = 'select ' . $fileds . ' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id where ' . $where . ' limit ' . $limit['start'] . ',' . $limit['end'];
        $List = $M->query($sql);
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function supplierNotMatchList()
    {
        $brand = trim($_GET['brand']);

        $where = '(gs.in_id is null or gs.in_id=0)';
        $condition = '(in_id is null or in_id=0)';
        if ($brand) {
            $where .= ' and gs.brand_en like "%' . $brand . '%"';
            $condition .= ' and brand_en like "%' . $brand . '%"';
            $this->assign('brand', $brand);
        }

        $count = M('in_goods_supplier')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;

        $M = M('');
        $fileds = 'gs.id,gs.in_id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
        $fileds .= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,';
        $fileds .= 'gs.all_stock,gs.base_stock,gs.supplier,gs.website,';
        $fileds .= 'gs.purchase_price_cn,gs.supply_price_ori,gs.retail_price_ori,gs.discount_ori';
        $sql = 'select ' . $fileds . ' from fx_in_goods_supplier gs where ' . $where . ' limit ' . $limit['start'] . ',' . $limit['end'];

        $List = $M->query($sql);
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function C6List()
    {
        $brand = trim($_GET['brand']);

        $where = 'gs.gc_id is not null';
        $condition = 'gc_id is not null';
        if ($brand) {
            $where .= ' and gs.brand_en like "%' . $brand . '%"';
            $condition .= ' and brand_en like "%' . $brand . '%"';
            $this->assign('brand', $brand);
        }
        $count = M('in_goods_supplier')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;

        $M = M('');
        $fileds = 'gs.id,gs.in_id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
        $fileds .= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,gs.purchase_price_cn,gs.supply_price_cn,gs.retail_price_cn,';
        $fileds .= 'gs.discount_cn,gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.sale_price,gs.profit,gs.profit_base,gs.tmall_price,gs.jumei_price,gs.tmall_url,gs.jumei_url,gs.newRetail,gs.mayPurchase,';
        $fileds .= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url,';
        $fileds .= 'gc.korean_direct,gc.HK_direct,gc.domestic_shipment,gc.profit_notsole,gc.profit_sole,gc.person_normal,gc.person_notnormal,gc.BC_normal,gc.BC_notnormal';
        $sql = 'select ' . $fileds . ' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id left join fx_goods_category gc on gc.gc_id=gs.gc_id where ' . $where . ' limit ' . $limit['start'] . ',' . $limit['end'];

        $List = $M->query($sql);
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function C12List()
    {
        $M = M('');
        $sql = 'select * from fx_goods_category where gc_is_parent=1';
        $List = $M->query($sql);
        $this->assign('List', $List);
        $this->skin();
        $this->display();
    }

    public function supplierorderBillList()
    {
        $this->BillList('o_sn');
    }

    public function deliveryBillList()
    {
        $this->BillList('d_sn');
    }

    public function deliveryList()
    {
        $this->detailList();
    }

    public function receiptBillList()
    {
        $this->BillList('r_sn');
    }

    public function receiptList()
    {
        $this->detailList();
    }

    public function settlementBillList()
    {
        $this->BillList('s_sn');
    }

    public function settlementList()
    {
        $this->detailList();
    }

    private function BillList($sn)
    {
        $M = M('');
        $condition = $sn . ' is not null';
        $name = trim($_GET['name']);
        if ($name) {
            $condition .= ' and id in (select o_id from fx_in_goods_supplier_orders_item where bar_code in (select bar_code from fx_in_goods_supplier where (language_country like "%' . $name . '%" or name_cn like "%' . $name . '%" or name_en like "%' . $name . '%" or bar_code="' . $name . '")))';
        }

        $o_create_time_1 = trim($_GET['o_create_time_1']);
        $o_create_time_2 = trim($_GET['o_create_time_2']);
        if ($o_create_time_1 && $o_create_time_2) {
            $condition .= ' and o_create_time between "' . $o_create_time_1 . '" and "' . $o_create_time_2 . '"';
        }

        $o_reply_time_1 = trim($_GET['o_reply_time_1']);
        $o_reply_time_2 = trim($_GET['o_reply_time_2']);
        if ($o_reply_time_1 && $o_reply_time_2) {
            $condition .= ' and o_reply_time between "' . $o_reply_time_1 . '" and "' . $o_reply_time_2 . '"';
        }

        $o_status = trim($_GET['o_status']);
        if ($o_status) {
            $condition .= ' and o_status="' . $o_status . '"';
        }

        $count = M('in_goods_supplier_orders')->where($condition)->count();
        $obj_page = new Page($count, 200);
        $page = $obj_page->show();
        $limit['start'] = $obj_page->firstRow;
        $limit['end'] = $obj_page->listRows;
        $List = M('in_goods_supplier_orders')
            ->where($condition)->limit($limit['start'], $limit['end'])->select();
        //echo M('in_goods_supplier_orders')->getLastSql();
        $this->assign('List', $List);
        $this->assign("page", $page);
        $this->skin();
        $this->display();
    }

    public function supplierOrderList()
    {
        $M = M('');
        $sql = 'select * from fx_in_goods_supplier_orders order by id desc limit 1';
        $rs = $M->query($sql);
        if ($rs) {
            $o_id = $rs[0]['id'];
            $where = 'gs.bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id=' . $o_id . ')';
            $condition = 'bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id=' . $o_id . ')';
            $count = M('in_goods_supplier')->where($condition)->count();
            $obj_page = new Page($count, 200);
            $page = $obj_page->show();
            $limit['start'] = $obj_page->firstRow;
            $limit['end'] = $obj_page->listRows;

            $M = M('');
            $fileds = 'gs.id,gs.in_id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
            $fileds .= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,gs.purchase_price_cn,gs.supply_price_cn,gs.retail_price_cn,';
            $fileds .= 'gs.discount_cn,gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.sale_price,gs.profit,gs.profit_base,gs.tmall_price,gs.jumei_price,gs.tmall_url,gs.jumei_url,gs.newRetail,gs.mayPurchase,';
            $fileds .= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url,';
            $fileds .= 'gc.korean_direct,gc.HK_direct,gc.domestic_shipment,gc.profit_notsole,gc.profit_sole,gc.person_normal,gc.person_notnormal,gc.BC_normal,gc.BC_notnormal';
            $sql = 'select ' . $fileds . ' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id left join fx_goods_category gc on gc.gc_id=gs.gc_id where ' . $where . ' limit ' . $limit['start'] . ',' . $limit['end'];

            $List = $M->query($sql);
            $this->assign('List', $List);
            $this->assign("page", $page);
            $this->skin();
            $this->display();
        }
    }

    public function deliveryListlast()
    {
        $M = M('');
        $sql = 'select * from fx_in_goods_supplier_orders where d_sn is not null order by id desc limit 1';
        $rs = $M->query($sql);
        if ($rs) {
            $o_id = $rs[0]['id'];
            $where = 'gs.bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id=' . $o_id . ')';
            $condition = 'bar_code in (select bar_code from fx_in_goods_supplier_orders_item where o_id=' . $o_id . ')';
            $count = M('in_goods_supplier')->where($condition)->count();
            $obj_page = new Page($count, 200);
            $page = $obj_page->show();
            $limit['start'] = $obj_page->firstRow;
            $limit['end'] = $obj_page->listRows;

            $M = M('');
            $fileds = 'gs.id,gs.in_id,gs.language_country,gs.name_cn,gs.name_en,gs.brand_country,gs.import,gs.home_made,';
            $fileds .= 'gs.brand_en,gs.brand_cn,gs.unit,gs.package,gs.partused,gs.bar_code,gs.purchase_price_cn,gs.supply_price_cn,gs.retail_price_cn,';
            $fileds .= 'gs.discount_cn,gs.all_stock,gs.base_stock,gs.supplier,gs.website,gs.sale_price,gs.profit,gs.profit_base,gs.tmall_price,gs.jumei_price,gs.tmall_url,gs.jumei_url,gs.newRetail,gs.mayPurchase';
            //$fileds.= 'g.main_fun,g.fun1,g.fun2,g.fun3,g.color,g.material,g.nomenclature,g.main_class,g.class1,g.class2,g.class3,g.spec,g.tmall_url,g.jumei_url,';
            //$fileds.= 'gc.korean_direct,gc.HK_direct,gc.domestic_shipment,gc.profit_notsole,gc.profit_sole,gc.person_normal,gc.person_notnormal,gc.BC_normal,gc.BC_notnormal';
            //$sql = 'select '.$fileds.' from fx_in_goods_supplier gs left join fx_in_goods g on g.id=gs.in_id left join fx_goods_category gc on gc.gc_id=gs.gc_id where '.$where.' limit '.$limit['start'].','.$limit['end'];
            $sql = 'select ' . $fileds . ' from fx_in_goods_supplier gs where ' . $where . ' limit ' . $limit['start'] . ',' . $limit['end'];

            $List = $M->query($sql);

            foreach ($List as $key => $value) {
                $bar_code = $value['bar_code'];
                $sql = 'select g_picture from fx_goods_info where g_id=(select g_id from fx_goods_products where pdt_bar_code="' . $bar_code . '")';
                $rs = $M->query($sql);
                $List[$key]['g_picture'] = $rs[0]['g_picture'];

                $sql = 'select * from fx_in_goods_supplier_orders_item where o_id=' . $o_id . ' and bar_code="' . $bar_code . '"';
                $rs = $M->query($sql);
                $List[$key]['nums'] = $rs[0]['nums'];
                $List[$key]['supply_nums'] = $rs[0]['supply_nums'];
                $List[$key]['item_id'] = $rs[0]['id'];
            }
            $this->assign('List', $List);
            $this->assign("page", $page);
            $this->assign("o_id", $o_id);
            $this->skin();
            $this->display();
        }
    }

    public function TableEdit()
    {
        $table = $_GET['table'];
        $id = $_POST['id'];
        $value = $_POST['value'];

        $a = explode('-', $id);

        $item = $a[0];
        $id = $a[1];
        $M = M('');
        if ($table != 'goods_category') {
            $sql = 'update fx_' . $table . ' set ' . $item . '="' . $value . '" where id=' . $id;
        } else {
            $sql = 'update fx_' . $table . ' set ' . $item . '="' . $value . '" where gc_id=' . $id . ' or gc_parent_id=' . $id;
        }
        echo $value;
        $M->query($sql);
    }

    public function makeOrder()
    {
        $M = M('');
        $brand = trim($_GET['brand']);
        $sql = 'select bar_code,supplier_m_id,purchase_price_cn from fx_in_goods_supplier where mayPurchase="Y" and gc_id is not null and brand_en like "%' . $brand . '%"';

        $ary_data = $M->query($sql);

        if ($ary_data) {
            $data['o_sn'] = 'O' . date('mdHi') . rand(10, 99);
            $data['o_create_time'] = date("Y-m-d h:i:s");
            $data['o_status'] = 0;
            $data['supplier_m_id'] = $ary_data[0]['supplier_m_id'];

            $in_goods_supplier_orders = M('in_goods_supplier_orders', 'fx_');

            //$sql = "INSERT INTO `fx_in_goods_supplier_orders` (`o_sn`,`o_create_time`,`o_status`,`supplier_m_id`) VALUES ('".$o_sn."','".$o_create_time."','0','".$ary_data[0]['supplier_m_id']."')";
            //$M->query($sql);
            $o_id = $in_goods_supplier_orders->data($data)->add();

            $skuNums = 0;
            $skuMoney = 0;
            foreach ($ary_data as $vl) {
                $nums = 5;
                $sql = "INSERT INTO `fx_in_goods_supplier_orders_item` (`o_id`,`bar_code`,`nums`,`supply_nums`,`price_cn`) VALUES ('" . $o_id . "','" . $vl['bar_code'] . "','" . $nums . "','" . $nums . "','" . $vl['purchase_price_cn'] . "')";
                $M->query($sql);
                $skuNums += $nums;
                $skuMoney += $nums * $vl['purchase_price_cn'];
            }
            $sql = 'update fx_in_goods_supplier_orders set skuNums=' . $skuNums . ',skuMoney=' . $skuMoney . ' where id=' . $o_id;
            $M->query($sql);

            $name = '采购订单-' . $data['o_sn'];
            $url = '/Ucenter/Supplier/orderList/o_id/' . $o_id;
            $member = session("Members");
            $from_m_id = $member['m_id'];
            $to_m_id = $ary_data[0]['supplier_m_id'];
            $status = 0;
            $create_time = date("Y-m-d h:i:s");
            $sql = "INSERT INTO `fx_in_pending` (`name`,`url`,`from_m_id`,`to_m_id`,`status`,`create_time`) VALUES ('" . $name . "','" . $url . "','" . $from_m_id . "','" . $to_m_id . "','" . $status . "','" . $create_time . "')";
            $M->query($sql);
        }
    }
}