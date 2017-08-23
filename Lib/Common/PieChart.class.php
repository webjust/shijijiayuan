<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PieChart
 *
 * @author listen
 */
define("FONT_USED", "Public/font/FZSTK.TTF"); 
define("ANGLE_STEP", 3);
class PieChart {
   //求$clr对应的暗色 
   public function draw_getdarkcolor($img,$clr) 
    { 
        $rgb = imagecolorsforindex($img,$clr); 
        return array($rgb["red"]/2,$rgb["green"]/2,$rgb["blue"]/2); 
    } 
    //求角度$d对应的椭圆上的点坐标 
   public function draw_getexy($a, $b, $d) 
    { 
        $d = deg2rad($d); 
        return array(round($a*Cos($d)), round($b*Sin($d))); 
    } 
    public function draw_arc($img,$ox,$oy,$a,$b,$sd,$ed,$clr) //椭圆弧函数 
    { 
        $n = ceil(($ed-$sd)/ANGLE_STEP); 
        $d = $sd; 
        list($x0,$y0) = $this->draw_getexy($a,$b,$d); 
        for($i=0; $i<$n; $i++) 
        { 
            $d = ($d+ANGLE_STEP)>$ed?$ed:($d+ANGLE_STEP); 
            list($x, $y) = $this->draw_getexy($a, $b, $d); 
            imageline($img, $x0+$ox, $y0+$oy, $x+$ox, $y+$oy, $clr); 
            $x0 = $x; 
            $y0 = $y; 
        } 
    }
    public function draw_sector($img, $ox, $oy, $a, $b, $sd, $ed, $clr) //画扇面 
    { 
        $n = ceil(($ed-$sd)/ANGLE_STEP); 
        $d = $sd; 
        list($x0,$y0) = $this->draw_getexy($a, $b, $d); 
        imageline($img, $x0+$ox, $y0+$oy, $ox, $oy, $clr); 
        for($i=0; $i<$n; $i++) 
        { 
            $d = ($d+ANGLE_STEP)>$ed?$ed:($d+ANGLE_STEP); 
            list($x, $y) = $this->draw_getexy($a, $b, $d); 
            imageline($img, $x0+$ox, $y0+$oy, $x+$ox, $y+$oy, $clr); 
            $x0 = $x; 
            $y0 = $y; 
        } 
        imageline($img, $x0+$ox, $y0+$oy, $ox, $oy, $clr); 
        list($x, $y) = $this->draw_getexy($a/2, $b/2, ($d+$sd)/2); 
        imagefill($img, $x+$ox, $y+$oy, $clr); 
    }
    public function draw_sector3d($img, $ox, $oy, $a, $b, $v, $sd, $ed, $clr) //3d扇面 
        { 
            $this->draw_sector($img, $ox, $oy, $a, $b, $sd, $ed, $clr); 
            if($sd<180) 
            { 
                list($R, $G, $B) = $this->draw_getdarkcolor($img, $clr); 
                $clr=imagecolorallocate($img, $R, $G, $B); 
                if($ed>180) $ed = 180; 
                list($sx, $sy) = $this->draw_getexy($a,$b,$sd); 
                $sx += $ox; 
                $sy += $oy; 
                list($ex, $ey) = $this->draw_getexy($a, $b, $ed); 
                $ex += $ox; 
                $ey += $oy; 
                imageline($img, $sx, $sy, $sx, $sy+$v, $clr); 
                imageline($img, $ex, $ey, $ex, $ey+$v, $clr); 
                $this->draw_arc($img, $ox, $oy+$v, $a, $b, $sd, $ed, $clr); 
                list($sx, $sy) = $this->draw_getexy($a, $b, ($sd+$ed)/2); 
                $sy += $oy+$v/2; 
                $sx += $ox; 
                imagefill($img, $sx, $sy, $clr); 
            } 
        } 
    public function draw_getindexcolor($img, $clr) //RBG转索引色 
    { 
        $R = ($clr>>16) & 0xff; 
        $G = ($clr>>8)& 0xff; 
        $B = ($clr) & 0xff; 
        return imagecolorallocate($img, $R, $G, $B); 
    }
    // 绘图主函数，并输出图片 
    // $datLst 为数据数组, $datLst 为标签数组, $datLst 为颜色数组 
    // 以上三个数组的维数应该相等 
  public function draw_img($datLst,$labLst,$clrLst,$a=200,$b=90,$v=20,$font=15) 
    { 
        $ox = 5+$a; 
        $oy = 5+$b; 
        $fw = imagefontwidth($font); 
        $fh = imagefontheight($font); 
        $n = count($datLst);//数据项个数 
        $w = 10+$a*2; 
        $h = 10+$b*2+$v+($fh+2)*$n; 
        $img = imagecreate($w, $h); 
        $jpeg_quality = 75; //jpeg图片的质量
        $savepath ='Public/pie/';
        $output_image_file =$savepath.'abc.jpeg';//图片保存的地址
        if(!is_dir($savepath)){
                @mkdir($savepath,0777,1);
        }
     
        for($i=0; $i<$n; $i++) 
            $clrLst[$i] =$this->draw_getindexcolor($img,$clrLst[$i]); 
            $clrbk = imagecolorallocate($img, 0xff, 0xff, 0xff); 
            $clrt = imagecolorallocate($img, 0x00, 0x00, 0x00); 
            //填充背景色 
            imagefill($img, 0, 0, $clrbk); 
            //求和 
            $tot = 0; 
            for($i=0; $i<$n; $i++) 
                $tot += $datLst[$i]; 
                $sd = 0; 
                $ed = 0; 
                $ly = 10+$b*2+$v; 
                for($i=0; $i<$n; $i++) 
                { 
                    $sd = $ed; 
                    $ed += $datLst[$i]/$tot*360; 
                    //画圆饼 
                    $this->draw_sector3d($img, $ox, $oy, $a, $b, $v, $sd, $ed, $clrLst[$i]); //$sd,$ed,$clrLst[$i]); 
                    //画标签 
                    imagefilledrectangle($img, 5, $ly, 5+$fw, $ly+$fh, $clrLst[$i]); 
                    imagerectangle($img, 5, $ly, 5+$fw, $ly+$fh, $clrt); 
                    //imagestring($img, $font, 5+2*$fw, $ly, $labLst[$i].":".$datLst[$i]."(".(round(10000*($datLst[$i]/$tot))/100)."%)", $clrt); 
                    $str = $labLst[$i];//iconv("UTF-8", "GB2312", $labLst[$i]); 
                    //dump($labLst[$i]) ;
                    
                    ImageTTFText($img, $font, 0, 5+2*$fw, $ly+13, $clrt, FONT_USED, $str.":"."(".(round(10000*($datLst[$i]/$tot))/100)."%)"); 
                    $ly += $fh+2; 
                    
                }
                //输出图形 
                //exit;
                //header("Content-type: image/png"); 
                //输出生成的图片 
                imagejpeg($img,$output_image_file,$jpeg_quality);
                
                //imagepng($img); 
               return $output_image_file;
    }
    //随机生成颜色
    public function roundColor($number){
       
       $clrLst = array(
           0x99FFFF,0x33CCCC, 0x00CC99, 0x99FF99,
           0x66CCCC,0x66FFCC,0x66FF66,0x009933,0x00CC33,
           0x66FF00,0x336600,0x33300,0x33FFFF,0x339999,
           0x99FFCC,0x339933,0x33FF66,0x33CC33,0x99FF00,
           0x669900,0x666600,0x00FFFF,0x336666,0x00FF99,0x99CC99,
           0x00FF66,0x66FF33,0x66CC00,0x99CC00,0x999933,0x00CCCC,0x006666,
           0x339966,0x66FF99,0xCCFFCC,0x00FF00,0xFFCC00,0x663300,0xFF6600,
           0x663333,0xCC6666,0xFF6666,0xFF0000,0xFFFF99,0xFFCC66,0xFF9900,
           0xFF9966,0xCC3300,0x996666,0xFFCCCC,0x660000,0xFF3300,0xFF6666,
           0xFFCC33,0xCC6600,0xFF6633,0x996633,0xCC9999,0xFF3333,0x990000,
           0xCC9966,0xFFFF33,0xCC9933,0x993300,0xFF9933,0x330000,0x993333,
           0xCC3333,0xCC0000,0xFFCC99,0xFFFF00,0x996600,0xCC6633
           ); 
       $ary_data =  array_rand($clrLst,$number);
      // echo"<pre>"; print_r($number);exit;
       $ary_res = array();
       if($number>1){
           foreach($ary_data as $k=>$v){
               $ary_res[] = $clrLst[$v];
           }
       }else if($number<=1){
           $ary_res =$ary_data;
       }
       // echo"<pre>"; print_r($ary_res);exit;
       return $ary_res;
    }
}

?>
