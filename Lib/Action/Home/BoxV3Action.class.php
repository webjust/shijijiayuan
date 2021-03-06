<?php
class BoxV3Action extends HomeAction {  

    public function OrderBox(){
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $mainBox[0] = array('x'=>14,'y'=>7,'z'=>4);
        $mainBox[1] = array('x'=>16,'y'=>9,'z'=>4);
        $mainBox[2] = array('x'=>20,'y'=>12,'z'=>7);
        $mainBox[3] = array('x'=>22,'y'=>12,'z'=>7);
        $mainBox[4] = array('x'=>24,'y'=>14,'z'=>7);
        $mainBox[5] = array('x'=>26,'y'=>15,'z'=>8);
        $mainBox[6] = array('x'=>28,'y'=>16,'z'=>9);
        $mainBox[7] = array('x'=>30,'y'=>17,'z'=>10);
        $mainBox[8] = array('x'=>22,'y'=>22,'z'=>12);
        $mainBox[9] = array('x'=>32,'y'=>19,'z'=>10);
        $mainBox[10] = array('x'=>34,'y'=>20,'z'=>11);
        $mainBox[11] = array('x'=>30,'y'=>30,'z'=>17);
        //从小到大


        if($_POST['p1_l']&&$_POST['p1_w']&&$_POST['p1_h']){
            $products[0] = array('x'=>$_POST['p1_l'],'y'=>$_POST['p1_w'],'z'=>$_POST['p1_h'],'p'=>array('1'));

            echo '商品1：'.$_POST['p1_l'].','.$_POST['p1_w'].','.$_POST['p1_h'].'<br>';
        }
        if($_POST['p2_l']&&$_POST['p2_w']&&$_POST['p2_h']){
            $products[1] = array('x'=>$_POST['p2_l'],'y'=>$_POST['p2_w'],'z'=>$_POST['p2_h'],'p'=>array('2'));
            echo '商品2：'.$_POST['p2_l'].','.$_POST['p2_w'].','.$_POST['p2_h'].'<br>';
        }
        if($_POST['p3_l']&&$_POST['p3_w']&&$_POST['p3_h']){
            $products[2] = array('x'=>$_POST['p3_l'],'y'=>$_POST['p3_w'],'z'=>$_POST['p3_h'],'p'=>array('3'));
            echo '商品3：'.$_POST['p3_l'].','.$_POST['p3_w'].','.$_POST['p3_h'].'<br>';
        }
        if($_POST['p4_l']&&$_POST['p4_w']&&$_POST['p4_h']){
            $products[3] = array('x'=>$_POST['p4_l'],'y'=>$_POST['p4_w'],'z'=>$_POST['p4_h'],'p'=>array('4'));
            echo '商品4：'.$_POST['p4_l'].','.$_POST['p4_w'].','.$_POST['p4_h'].'<br>';
        }
        if($_POST['p5_l']&&$_POST['p5_w']&&$_POST['p5_h']){
            $products[4] = array('x'=>$_POST['p5_l'],'y'=>$_POST['p5_w'],'z'=>$_POST['p5_h'],'p'=>array('5'));
            echo '商品5：'.$_POST['p5_l'].','.$_POST['p5_w'].','.$_POST['p5_h'].'<br>';
        }
        $pci = count($products);//最开始的商品数量
        //$GLOBALS['is_loop'] = 1;//最开始的商品数量
        $products = $this->combination_loop($products);
        //var_dump($products);
        //echo $pci;
        $r=0;
        $m = 12;
        $pc = count($products);
        for($i=0;$i<$pc;$i++){
            if(count($products[$i]['p'])==$pci){
                //对长宽高排序
                $p = array($products[$i]['x'],$products[$i]['y'],$products[$i]['z']);
                rsort($p);
                for($j=0;$j<12;$j++){
                    if($p[0]<=$mainBox[$j]['x']&&$p[1]<=$mainBox[$j]['y']&&$p[2]<=$mainBox[$j]['z']){
                        if($j<$m){
                            $m = $j;
                            //$r = $i;
                        }
                    }
                }
            }
        }

        $nums = $m+1;
        echo '第'.$nums.'号箱,..内尺寸：'.$mainBox[$m]['x'].','.$mainBox[$m]['y'].','.$mainBox[$m]['z'].'<br>';
        if($_GET['test']){
             $p = array($products[$r]['x'],$products[$r]['y'],$products[$r]['z']);
            rsort($p);
            echo '商品组合尺寸：'.$p[0].','.$p[1].','.$p[2];           
        }

        //print_r($products[$r]);
        exit();       
        //var_dump($box);
    }

    private function combination(&$products,$i,$j,$x,$y,$z){
        //if($x>30||$y>30||$z>30){
        //    return;
        //}
        $intersection = array_intersect($products[$i]['p'], $products[$j]['p']);//交集
        if(!$intersection){//无交集
            $p = array_merge($products[$i]['p'], $products[$j]['p']);//并集
            //sort($p);
            $pitem = array('x'=>$x+0,'y'=>$y+0,'z'=>$z+0,'p'=>$p);
            if(!in_array($pitem, $products)){
                $products[] = array('x'=>$x+0,'y'=>$y+0,'z'=>$z+0,'p'=>$p);

                $sql = 'insert into fx_box(x,y,z,p) values('.$x.','.$y.','.$z.','.$p[0].$p[1].$p[2].$p[3].$p[4].')';
                D('Orders')->query($sql);
            }
        }
        return;
    }

    private function combination_loop($products){
        $pc = count($products);

        for($i=0;$i<$pc;$i++){
            for($j=0;$j<$pc;$j++){
                if($i!=$j){
                     //第一面
                    //x1y1*x2y2
                    $x = $products[$i]['x']>$products[$j]['x']?$products[$i]['x']:$products[$j]['x'];
                    $y = $products[$i]['y']>$products[$j]['y']?$products[$i]['y']:$products[$j]['y'];
                    $z = $products[$i]['z']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    
                    //x1y1*y2x2
                    $x = $products[$i]['x']>$products[$j]['y']?$products[$i]['x']:$products[$j]['y'];
                    $y = $products[$i]['y']>$products[$j]['x']?$products[$i]['y']:$products[$j]['x'];
                    $z = $products[$i]['z']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //x1y1*x2z2
                    $x = $products[$i]['x']>$products[$j]['x']?$products[$i]['x']:$products[$j]['x'];
                    $y = $products[$i]['y']>$products[$j]['z']?$products[$i]['y']:$products[$j]['z'];
                    $z = $products[$i]['z']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //x1y1*z2x2
                    $x = $products[$i]['x']>$products[$j]['z']?$products[$i]['x']:$products[$j]['z'];
                    $y = $products[$i]['y']>$products[$j]['x']?$products[$i]['y']:$products[$j]['x'];
                    $z = $products[$i]['z']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //x1y1*y2z2
                    $x = $products[$i]['x']>$products[$j]['y']?$products[$i]['x']:$products[$j]['y'];
                    $y = $products[$i]['y']>$products[$j]['z']?$products[$i]['y']:$products[$j]['z'];
                    $z = $products[$i]['z']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //x1y1*z2y2
                    $x = $products[$i]['x']>$products[$j]['z']?$products[$i]['x']:$products[$j]['z'];
                    $y = $products[$i]['y']>$products[$j]['y']?$products[$i]['y']:$products[$j]['y'];
                    $z = $products[$i]['z']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //第二面
                    //x1z1*x2y2
                    $x = $products[$i]['x']>$products[$j]['x']?$products[$i]['x']:$products[$j]['x'];
                    $y = $products[$i]['z']>$products[$j]['y']?$products[$i]['z']:$products[$j]['y'];
                    $z = $products[$i]['y']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //x1z1*y2x2
                    $x = $products[$i]['x']>$products[$j]['y']?$products[$i]['x']:$products[$j]['y'];
                    $y = $products[$i]['z']>$products[$j]['x']?$products[$i]['z']:$products[$j]['x'];
                    $z = $products[$i]['y']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //x1z1*x2z2
                    $x = $products[$i]['x']>$products[$j]['x']?$products[$i]['x']:$products[$j]['x'];
                    $y = $products[$i]['z']>$products[$j]['z']?$products[$i]['z']:$products[$j]['z'];
                    $z = $products[$i]['y']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //x1z1*z2x2
                    $x = $products[$i]['x']>$products[$j]['z']?$products[$i]['x']:$products[$j]['z'];
                    $y = $products[$i]['z']>$products[$j]['x']?$products[$i]['z']:$products[$j]['x'];
                    $z = $products[$i]['y']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //x1z1*y2z2
                    $x = $products[$i]['x']>$products[$j]['y']?$products[$i]['x']:$products[$j]['y'];
                    $y = $products[$i]['z']>$products[$j]['z']?$products[$i]['z']:$products[$j]['z'];
                    $z = $products[$i]['y']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //x1z1*z2y2
                    $x = $products[$i]['x']>$products[$j]['z']?$products[$i]['x']:$products[$j]['z'];
                    $y = $products[$i]['z']>$products[$j]['y']?$products[$i]['z']:$products[$j]['y'];
                    $z = $products[$i]['y']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //第三面
                    //y1z1*x2y2
                    $x = $products[$i]['y']>$products[$j]['x']?$products[$i]['y']:$products[$j]['x'];
                    $y = $products[$i]['z']>$products[$j]['y']?$products[$i]['z']:$products[$j]['y'];
                    $z = $products[$i]['x']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //y1z1*y2x2
                    $x = $products[$i]['y']>$products[$j]['y']?$products[$i]['y']:$products[$j]['y'];
                    $y = $products[$i]['z']>$products[$j]['x']?$products[$i]['z']:$products[$j]['x'];
                    $z = $products[$i]['x']+$products[$j]['z']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //y1z1*x2z2
                    $x = $products[$i]['y']>$products[$j]['x']?$products[$i]['y']:$products[$j]['x'];
                    $y = $products[$i]['z']>$products[$j]['z']?$products[$i]['z']:$products[$j]['z'];
                    $z = $products[$i]['x']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //y1z1*z2x2
                    $x = $products[$i]['y']>$products[$j]['z']?$products[$i]['y']:$products[$j]['z'];
                    $y = $products[$i]['z']>$products[$j]['x']?$products[$i]['z']:$products[$j]['x'];
                    $z = $products[$i]['x']+$products[$j]['y']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);

                    //y1z1*y2z2
                    $x = $products[$i]['y']>$products[$j]['y']?$products[$i]['y']:$products[$j]['y'];
                    $y = $products[$i]['z']>$products[$j]['z']?$products[$i]['z']:$products[$j]['z'];
                    $z = $products[$i]['x']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    //y1z1*z2y2
                    $x = $products[$i]['y']>$products[$j]['z']?$products[$i]['y']:$products[$j]['z'];
                    $y = $products[$i]['z']>$products[$j]['y']?$products[$i]['z']:$products[$j]['y'];
                    $z = $products[$i]['x']+$products[$j]['x']+1;
                    $this->combination($products,$i,$j,$x,$y,$z);
                    $pc = count($products);
                }
                //$pc = count($products); 
            }
        }
        return $products;
    }
}