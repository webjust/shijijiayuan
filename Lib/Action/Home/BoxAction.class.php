<?php
class BoxAction extends HomeAction {  

    public function OrderBox(){
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
        $mainBox[0] = array('x'=>14,'y'=>7,'z'=>4,'v'=>392,'parent'=>array('x'=>14,'y'=>7,'z'=>4));
        $mainBox[1] = array('x'=>16,'y'=>9,'z'=>4,'v'=>576,'parent'=>array('x'=>16,'y'=>9,'z'=>4));
        $mainBox[2] = array('x'=>20,'y'=>12,'z'=>7,'v'=>1682,'parent'=>array('x'=>20,'y'=>12,'z'=>7));
        $mainBox[3] = array('x'=>22,'y'=>12,'z'=>7,'v'=>1848,'parent'=>array('x'=>22,'y'=>12,'z'=>7));
        $mainBox[4] = array('x'=>24,'y'=>14,'z'=>7,'v'=>2352,'parent'=>array('x'=>24,'y'=>14,'z'=>7));
        $mainBox[5] = array('x'=>26,'y'=>15,'z'=>8,'v'=>3120,'parent'=>array('x'=>26,'y'=>15,'z'=>8));
        $mainBox[6] = array('x'=>28,'y'=>16,'z'=>9,'v'=>4032,'parent'=>array('x'=>28,'y'=>16,'z'=>9));
        $mainBox[7] = array('x'=>30,'y'=>17,'z'=>10,'v'=>5100,'parent'=>array('x'=>30,'y'=>17,'z'=>10));
        $mainBox[8] = array('x'=>22,'y'=>22,'z'=>12,'v'=>5808,'parent'=>array('x'=>22,'y'=>22,'z'=>12));
        $mainBox[9] = array('x'=>32,'y'=>19,'z'=>10,'v'=>6080,'parent'=>array('x'=>32,'y'=>19,'z'=>10));
        $mainBox[10] = array('x'=>34,'y'=>20,'z'=>11,'v'=>7480,'parent'=>array('x'=>34,'y'=>20,'z'=>11));
        $mainBox[11] = array('x'=>30,'y'=>30,'z'=>17,'v'=>15300,'parent'=>array('x'=>30,'y'=>30,'z'=>17));
        //从小到大


        if($_REQUEST['p1_l']&&$_REQUEST['p1_w']&&$_REQUEST['p1_h']){
            $products[0] = array('x'=>$_REQUEST['p1_l'],'y'=>$_REQUEST['p1_w'],'z'=>$_REQUEST['p1_h']);

            echo '商品1：'.$_REQUEST['p1_l'].','.$_REQUEST['p1_w'].','.$_REQUEST['p1_h'].'<br>';
        }
        if($_REQUEST['p2_l']&&$_REQUEST['p2_w']&&$_REQUEST['p2_h']){
            $products[1] = array('x'=>$_REQUEST['p2_l'],'y'=>$_REQUEST['p2_w'],'z'=>$_REQUEST['p2_h']);
            echo '商品2：'.$_REQUEST['p2_l'].','.$_REQUEST['p2_w'].','.$_REQUEST['p2_h'].'<br>';
        }
        if($_REQUEST['p3_l']&&$_REQUEST['p3_w']&&$_REQUEST['p3_h']){
            $products[2] = array('x'=>$_REQUEST['p3_l'],'y'=>$_REQUEST['p3_w'],'z'=>$_REQUEST['p3_h']);
            echo '商品3：'.$_REQUEST['p3_l'].','.$_REQUEST['p3_w'].','.$_REQUEST['p3_h'].'<br>';
        }
        if($_REQUEST['p4_l']&&$_REQUEST['p4_w']&&$_REQUEST['p4_h']){
            $products[3] = array('x'=>$_REQUEST['p4_l'],'y'=>$_REQUEST['p4_w'],'z'=>$_REQUEST['p4_h']);
            echo '商品4：'.$_REQUEST['p4_l'].','.$_REQUEST['p4_w'].','.$_REQUEST['p4_h'].'<br>';
        }
        if($_REQUEST['p5_l']&&$_REQUEST['p5_w']&&$_REQUEST['p5_h']){
            $products[4] = array('x'=>$_REQUEST['p5_l'],'y'=>$_REQUEST['p5_w'],'z'=>$_REQUEST['p5_h']);
            echo '商品5：'.$_REQUEST['p5_l'].','.$_REQUEST['p5_w'].','.$_REQUEST['p5_h'].'<br>';
        }

        $mBc = count($mainBox);
        $pc = count($products);
        echo $pc;
        for($m=0;$m<$mBc;$m++){
            //unset($box);
            $box[0] = $mainBox[$m];
            //echo $m.'<br>';
            $i=0;
            for(;$i<$pc;){
                $box = $this->RefreshinBox($box,$products,$i);
                //var_dump($box);
                if($box==false&&$m==$mBc-1){
                    echo '装不下';
                    break;
                }
                elseif($box==false){
                    //echo 'ininin';
                    $i=0;
                    ++$m;
                    $box[0] = $mainBox[$m];
                    continue;
                }
                elseif($box==1){
                    $nums = $m+1;
                    echo '第'.$nums.'号箱,..内尺寸：'.$mainBox[$m]['x'].','.$mainBox[$m]['y'].','.$mainBox[$m]['z'];
                    exit();
                }
                elseif(count($box)&&$i==$pc-1){
                    $nums = $m+1;
                    echo '第'.$nums.'号箱,内尺寸：'.$mainBox[$m]['x'].','.$mainBox[$m]['y'].','.$mainBox[$m]['z'];
                    exit();
                }
                $i++;
            }
        }
        //var_dump($box);
    }


    private function RefreshinBox($box,$products,$pkey){
        //if($pkey==1){
            //echo $pkey.'to:';
            //var_dump($box);
            //exit();
        //}
        $pc = count($products);
        $px = $products[$pkey]['x'];
        $py = $products[$pkey]['y'];
        $pz = $products[$pkey]['z'];
        
        $bc = count($box);
        for($i=0;$i<$bc;$i++){
            $bx = $box[$i]['x'];
            $by = $box[$i]['y'];
            $bz = $box[$i]['z'];
            $parent = $box[$i]['parent'];
            $temp = array($bx,$by,$bz);
            rsort($temp);

            if($px<=$temp[0]&&$py<=$temp[1]&&$pz<=$temp[2]){
                //可以装得下

                if($pkey==$pc-1){
                    return 1;
                }

                if($parent['x']==$bx&&$parent['y']==$by&&$parent['z']==$bz){//主箱

                    $x = $bx-$px-1;
                    $y = $by;
                    $z = $bz;
                    $v = $x*$y*$z;
                    if($x>=1){
                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$bx,'y'=>$by,'z'=>$bz));
                    }
                    

                    $x = $bx;
                    $y = $by-$py-1;
                    $z = $bz;
                    $v = $x*$y*$z;
                    if($y>=1){
                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$bx,'y'=>$by,'z'=>$bz));
                    }

                    $x = $bx;
                    $y = $by;
                    $z = $bz-$pz-1;
                    $v = $x*$y*$z;
                    if($z>=1){
                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$bx,'y'=>$by,'z'=>$bz));
                    }  
                    
                    //if($pkey==$pc-1){
                    //    echo 'here';
                    //    return true;
                    //}
                    //else{
                      $box[$i] = array('x'=>0,'y'=>0,'z'=>0,'v'=>0,'parent'=>array('x'=>$box[$i]['x'],'y'=>$box[$i]['y'],'z'=>$box[$i]['z'])); 
                    //}          
                }

                else{
                    //选择商品最优摆放形态
                    $ab = array($bx,$by,$bz);
                    $ap = array($px,$py,$pz);
                    for($a=0;$a<3;$a++){
                        for($b=0;$b<3;$b++){
                            if($ab[$a]>=$ap[$b]){
                                $r = $ab[$a]-$ap[$b];
                                $bp[] = array('c'=>$r,'b'=>$a,'p'=>$b);
                            }
                        }
                    }
                    //找出箱边与货边差距最小组合；
                    foreach ($bp as $key => $row){
                        $v[$key]  = $row['c'];
                    }
                    array_multisort($v,SORT_ASC, $bp);

                    $b = $bp[0]['b'];
                    $p = $bp[0]['p'];

                    if($b==0){
                        if($p==0){
                            $PX = $px;
                        }
                        elseif($p==1){
                            $PX = $py;
                        }
                        elseif($p==2){
                            $PX = $pz;
                        }
                    }

                    elseif($b==1){
                        if($p==0){
                            $PY = $px;
                        }
                        elseif($p==1){
                            $PY = $py;
                        }
                        elseif($p==2){
                            $PY = $pz;
                        }
                    }

                    elseif($b==2){
                        if($p==0){
                            $PZ = $px;
                        }
                        elseif($p==1){
                            $PZ = $py;
                        }
                        elseif($p==2){
                            $PZ = $pz;
                        }
                    }

                    $bpc = count($bp);
                    $b2=-1;
                    $p2=-1;
                    for($k=1;$k<$bpc;$k++){
                        if($bp[$k]['b']!=$b&&$bp[$k]['p']!=$p&&$bp[$k]['b']!=$b2&&$bp[$k]['p']!=$p2){
                            if($b2==-1&&$p2==-1){
                                $b2 = $bp[$k]['b'];
                                $p2 = $bp[$k]['p'];
                            }
                            
                            if($bp[$k]['b']==0){
                                if($bp[$k]['p']==0){
                                    $PX = $px;
                                }
                                elseif($bp[$k]['p']==1){
                                    $PX = $py;
                                }
                                elseif($bp[$k]['p']==2){
                                    $PX = $pz;
                                }
                            }

                            elseif($bp[$k]['b']==1){
                                if($bp[$k]['p']==0){
                                    $PY = $px;
                                }
                                elseif($bp[$k]['p']==1){
                                    $PY = $py;
                                }
                                elseif($bp[$k]['p']==2){
                                    $PY = $pz;
                                }
                            }

                            elseif($bp[$k]['b']==2){
                                if($bp[$k]['p']==0){
                                    $PZ = $px;
                                }
                                elseif($bp[$k]['p']==1){
                                    $PZ = $py;
                                }
                                elseif($bp[$k]['p']==2){
                                    $PZ = $pz;
                                }
                            }
                            if($PX&&$PY&&$PZ){
                                break;
                            }                          
                        }
                    }

                    $px = $PX;
                    $py = $PY;
                    $pz = $PZ;
                    //找出箱边与货边差距最小组合结束
                    for($j=0;$j<$bc;$j++){
                        if($box[$j]['parent']==$parent){//对从同一父箱出来的子箱进行重计算

                            if($box[$j]['x']==$box[$j]['parent']['x']){//有两个箱

                                    $y_left = $box[$j]['parent']['y']-$py-1;                                    
                                    if($y_left>=1){
                                        $x = $box[$j]['x'];
                                        $y = $y_left;
                                        $z = $box[$j]['z'];
                                        $v = $x*$y*$z;
                                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$box[$j]['x'],'y'=>$box[$j]['y'],'z'=>$box[$j]['z']));
                                    }

                                    $z_left1 = $pz-($box[$j]['parent']['z']-$box[$j]['z'])-1;
                                    if($z_left1>=1){
                                        $x = $box[$j]['x']-$px-1;
                                        $y = $box[$j]['y'];
                                        $z = $z_left1;
                                        $v = $x*$y*$z;
                                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$box[$j]['x'],'y'=>$box[$j]['y'],'z'=>$box[$j]['z']));
                                    }

                                    $z_left2 = $box[$j]['parent']['z']-$pz-1;
                                    if($z_left2>=1){
                                        $x = $box[$j]['x'];
                                        $y = $box[$j]['y'];
                                        $z = $z_left2;
                                        $v = $x*$y*$z;
                                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$box[$j]['x'],'y'=>$box[$j]['y'],'z'=>$box[$j]['z']));
                                    }                        
                            }
                            if($box[$j]['y']==$box[$j]['parent']['y']&&$box[$j]['x']!=$box[$j]['parent']['x']){//有一个箱
                                    $x_left = $box[$j]['x']-$px-1;
                                    if($x_left>=1){
                                        $x = $x_left;
                                        $y = $box[$j]['y'];
                                        $z = $box[$j]['z'];
                                        $v = $x*$y*$z;
                                        $box[] = array('x'=>$x,'y'=>$y,'z'=>$z,'v'=>$v,'parent'=>array('x'=>$box[$j]['x'],'y'=>$box[$j]['y'],'z'=>$box[$j]['z']));                                       
                                    }   

                            }
                            $box[$j] = array('x'=>0,'y'=>0,'z'=>0,'v'=>0,'parent'=>array('x'=>$box[$j]['x'],'y'=>$box[$j]['y'],'z'=>$box[$j]['z']));
                            //unset($box[$j]);
                        }
                    }
                }
            }
            elseif($i==$bc-1){
                //echo 'i='.$i.'<br>';
                //此号主箱装不下
                //var_dump($products[$pkey]);
                return false;
            }
        }
        if($pkey==0){
            //$bc = count($box);
            //echo $bc;
            //var_dump($box);
        }
        //if($box){
            $box=array_values($box);
            // 取得列的列表
            foreach ($box as $key => $row)
            {
                $v[$key]  = $row['v'];
            }
            array_multisort($v,SORT_ASC, $box);
            //echo $pkey.'from:';
            //var_dump($box);

            $bc = count($box);
            if($bc==1&&$box[0]['x']==0){
                return false;
            }
            return $box;            
        //}

    }
}