    
	/* ��Ʒ�б�ҳ����JS	
	 *add by <zhuwenwei@guanyisoft.com>
	 *date 2015-09-30
	*/
	var swiper = new Swiper('#swiper-container2', {
		autoplay:3000,
        slidesPerView: 1,
		pagination:".swiper-pagination",
        paginationClickable: true,
        spaceBetween: 0,
        freeMode: true,
		prevButton:'.swiper-button-prev',
		nextButton:'.swiper-button-next',
    });
	
    $.extend({
        //��ȡ���е�url����
        getUrlVars: function(){
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        },
        //��ȡĳ��url����
        getUrlVar: function(name){
            return $.getUrlVars()[name];
        }
    });
    $(function(){
        //��Ʒ����ɸѡ
        $('[item="goodscate"]').click(function(){
            if($(this).hasClass('on')){
                $(this).removeClass('on');
                $(':hidden[name="cid"]').val('');
            }else{
                $('[item="goodscate"]').removeClass('on');
                $(this).addClass('on');
                $(':hidden[name="cid"]').val($(this).attr('cid'));
            }
            return false;
        });
        //��ƷƷ��ɸѡ
        $('[item="goodsbrand"]').click(function(){
            if($(this).hasClass('on')){
                $(this).removeClass('on');
                $(':hidden[name="bid"]').val('');
            }else{
                $('[item="goodsbrand"]').removeClass('on');
                $(this).addClass('on');
                $(':hidden[name="bid"]').val($(this).attr('bid'));
            }
            return false;
        });
        //�۸�����ɸѡ
        $("[item='goodspRank']").click(function(){
            if($(this).hasClass('on')){
                $(this).removeClass('on');
                $(':hidden[name="startPrice"]').val('');
                $(':hidden[name="endPrice"]').val('');
            }else{
                $("[item='goodspRank']").removeClass('on');
                $(this).addClass('on');
                $(':hidden[name="startPrice"]').val($(this).attr('startPrice'));
                $(':hidden[name="endPrice"]').val($(this).attr('endPrice'));
            }
            return false;
        });
    });